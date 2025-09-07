const express = require('express');
const router = express.Router();
const { executeQuery } = require('../config/database');
const { body, validationResult } = require('express-validator');

// Validation middleware
const handleValidationErrors = (req, res, next) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
        return res.status(400).json({
            success: false,
            message: 'Validation errors',
            errors: errors.array()
        });
    }
    next();
};

// GET /api/traffic-violation/violations - Get all traffic violations
router.get('/violations', async (req, res) => {
    try {
        const { page = 1, limit = 10, status, search, date_from, date_to } = req.query;
        const offset = (page - 1) * limit;
        
        let query = `
            SELECT 
                tv.*,
                v.plate_number,
                v.vehicle_type,
                CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                DATE_FORMAT(tv.violation_date, '%Y-%m-%d %H:%i:%s') as formatted_date,
                DATE_FORMAT(tv.created_at, '%Y-%m-%d %H:%i:%s') as created_date
            FROM traffic_violations tv
            LEFT JOIN vehicles v ON tv.vehicle_id = v.vehicle_id
            LEFT JOIN operators o ON v.operator_id = o.operator_id
            WHERE 1=1
        `;
        const params = [];
        
        if (status) {
            query += ' AND tv.status = ?';
            params.push(status);
        }
        
        if (search) {
            query += ' AND (tv.ticket_number LIKE ? OR v.plate_number LIKE ? OR tv.violation_type LIKE ?)';
            params.push(`%${search}%`, `%${search}%`, `%${search}%`);
        }
        
        if (date_from) {
            query += ' AND DATE(tv.violation_date) >= ?';
            params.push(date_from);
        }
        
        if (date_to) {
            query += ' AND DATE(tv.violation_date) <= ?';
            params.push(date_to);
        }
        
        query += ' ORDER BY tv.violation_date DESC LIMIT ? OFFSET ?';
        params.push(parseInt(limit), parseInt(offset));
        
        const result = await executeQuery(query, params);
        
        if (!result.success) {
            return res.status(500).json({
                success: false,
                message: 'Failed to fetch traffic violations',
                error: result.error
            });
        }
        
        // Get total count
        let countQuery = `
            SELECT COUNT(*) as total 
            FROM traffic_violations tv
            LEFT JOIN vehicles v ON tv.vehicle_id = v.vehicle_id
            WHERE 1=1
        `;
        const countParams = [];
        
        if (status) {
            countQuery += ' AND tv.status = ?';
            countParams.push(status);
        }
        
        if (search) {
            countQuery += ' AND (tv.ticket_number LIKE ? OR v.plate_number LIKE ? OR tv.violation_type LIKE ?)';
            countParams.push(`%${search}%`, `%${search}%`, `%${search}%`);
        }
        
        if (date_from) {
            countQuery += ' AND DATE(tv.violation_date) >= ?';
            countParams.push(date_from);
        }
        
        if (date_to) {
            countQuery += ' AND DATE(tv.violation_date) <= ?';
            countParams.push(date_to);
        }
        
        const countResult = await executeQuery(countQuery, countParams);
        const total = countResult.success ? countResult.data[0].total : 0;
        
        res.json({
            success: true,
            data: result.data,
            pagination: {
                page: parseInt(page),
                limit: parseInt(limit),
                total,
                pages: Math.ceil(total / limit)
            }
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: 'Internal server error',
            error: error.message
        });
    }
});

// GET /api/traffic-violation/violations/:id - Get specific violation
router.get('/violations/:id', async (req, res) => {
    try {
        const { id } = req.params;
        
        const query = `
            SELECT 
                tv.*,
                v.plate_number,
                v.vehicle_type,
                CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                o.email as operator_email,
                o.phone as operator_phone,
                DATE_FORMAT(tv.violation_date, '%Y-%m-%d %H:%i:%s') as formatted_date,
                DATE_FORMAT(tv.created_at, '%Y-%m-%d %H:%i:%s') as created_date
            FROM traffic_violations tv
            LEFT JOIN vehicles v ON tv.vehicle_id = v.vehicle_id
            LEFT JOIN operators o ON v.operator_id = o.operator_id
            WHERE tv.id = ?
        `;
        
        const result = await executeQuery(query, [id]);
        
        if (!result.success) {
            return res.status(500).json({
                success: false,
                message: 'Failed to fetch violation',
                error: result.error
            });
        }
        
        if (result.data.length === 0) {
            return res.status(404).json({
                success: false,
                message: 'Traffic violation not found'
            });
        }
        
        res.json({
            success: true,
            data: result.data[0]
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: 'Internal server error',
            error: error.message
        });
    }
});

// POST /api/traffic-violation/violations - Create new violation
router.post('/violations', [
    body('ticket_number').notEmpty().withMessage('Ticket number is required'),
    body('vehicle_id').isInt().withMessage('Valid vehicle ID is required'),
    body('violation_type').notEmpty().withMessage('Violation type is required'),
    body('violation_date').isISO8601().withMessage('Valid violation date is required'),
    body('fine_amount').isFloat({ min: 0 }).withMessage('Fine amount must be a positive number'),
    body('location').notEmpty().withMessage('Location is required')
], handleValidationErrors, async (req, res) => {
    try {
        const {
            ticket_number,
            vehicle_id,
            violation_type,
            violation_date,
            fine_amount,
            location,
            description,
            officer_name
        } = req.body;
        
        // Check if ticket number already exists
        const checkQuery = 'SELECT id FROM traffic_violations WHERE ticket_number = ?';
        const checkResult = await executeQuery(checkQuery, [ticket_number]);
        
        if (checkResult.success && checkResult.data.length > 0) {
            return res.status(400).json({
                success: false,
                message: 'Violation with this ticket number already exists'
            });
        }
        
        const query = `
            INSERT INTO traffic_violations (
                ticket_number, vehicle_id, violation_type, violation_date,
                fine_amount, location, description, officer_name,
                status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        `;
        
        const result = await executeQuery(query, [
            ticket_number, vehicle_id, violation_type, violation_date,
            fine_amount, location, description, officer_name
        ]);
        
        if (!result.success) {
            return res.status(500).json({
                success: false,
                message: 'Failed to create violation record',
                error: result.error
            });
        }
        
        res.status(201).json({
            success: true,
            message: 'Traffic violation recorded successfully',
            data: {
                id: result.data.insertId,
                ticket_number,
                status: 'pending'
            }
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: 'Internal server error',
            error: error.message
        });
    }
});

// PUT /api/traffic-violation/violations/:id/status - Update violation status
router.put('/violations/:id/status', [
    body('status').isIn(['pending', 'paid', 'contested', 'dismissed']).withMessage('Invalid status')
], handleValidationErrors, async (req, res) => {
    try {
        const { id } = req.params;
        const { status, payment_date, payment_method, remarks } = req.body;
        
        let query = 'UPDATE traffic_violations SET status = ?, updated_at = NOW()';
        const params = [status];
        
        if (status === 'paid' && payment_date) {
            query += ', payment_date = ?, payment_method = ?';
            params.push(payment_date, payment_method);
        }
        
        if (remarks) {
            query += ', remarks = ?';
            params.push(remarks);
        }
        
        query += ' WHERE id = ?';
        params.push(id);
        
        const result = await executeQuery(query, params);
        
        if (!result.success) {
            return res.status(500).json({
                success: false,
                message: 'Failed to update violation status',
                error: result.error
            });
        }
        
        if (result.data.affectedRows === 0) {
            return res.status(404).json({
                success: false,
                message: 'Traffic violation not found'
            });
        }
        
        res.json({
            success: true,
            message: 'Violation status updated successfully'
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: 'Internal server error',
            error: error.message
        });
    }
});

// GET /api/traffic-violation/revenue - Get revenue analytics
router.get('/revenue', async (req, res) => {
    try {
        const { period = 'month', year = new Date().getFullYear() } = req.query;
        
        let query;
        if (period === 'month') {
            query = `
                SELECT 
                    MONTH(violation_date) as period,
                    MONTHNAME(violation_date) as period_name,
                    SUM(fine_amount) as total_revenue,
                    COUNT(*) as total_violations
                FROM traffic_violations 
                GROUP BY MONTH(violation_date), MONTHNAME(violation_date)
                ORDER BY MONTH(violation_date)
            `;
        } else {
            query = `
                SELECT 
                    YEAR(violation_date) as period,
                    YEAR(violation_date) as period_name,
                    SUM(fine_amount) as total_revenue,
                    COUNT(*) as total_violations
                FROM traffic_violations 
                GROUP BY YEAR(violation_date)
                ORDER BY YEAR(violation_date)
            `;
        }
        
        const result = await executeQuery(query, []);
        
        if (!result.success) {
            return res.status(500).json({
                success: false,
                message: 'Failed to fetch revenue data',
                error: result.error
            });
        }
        
        res.json({
            success: true,
            data: result.data
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: 'Internal server error',
            error: error.message
        });
    }
});

// GET /api/traffic-violation/statistics - Get violation statistics
router.get('/statistics', async (req, res) => {
    try {
        const queries = {
            total: 'SELECT COUNT(*) as count FROM traffic_violations',
            pending: 'SELECT COUNT(*) as count FROM traffic_violations WHERE status = "pending"',
            paid: 'SELECT COUNT(*) as count FROM traffic_violations WHERE status = "paid"',
            contested: 'SELECT COUNT(*) as count FROM traffic_violations WHERE status = "contested"',
            totalRevenue: 'SELECT COALESCE(SUM(fine_amount), 0) as count FROM traffic_violations WHERE status = "paid"',
            pendingRevenue: 'SELECT COALESCE(SUM(fine_amount), 0) as count FROM traffic_violations WHERE status = "pending"'
        };
        
        const results = {};
        
        for (const [key, query] of Object.entries(queries)) {
            const result = await executeQuery(query);
            results[key] = result.success ? result.data[0].count : 0;
        }
        
        res.json({
            success: true,
            data: results
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: 'Internal server error',
            error: error.message
        });
    }
});

module.exports = router;