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

// GET /api/franchise/applications - Get all franchise applications
router.get('/applications', async (req, res) => {
    try {
        const { page = 1, limit = 10, status, search } = req.query;
        const offset = (page - 1) * limit;
        
        let query = `
            SELECT 
                fa.*,
                CONCAT(o.first_name, ' ', o.last_name) as full_name,
                o.first_name,
                o.last_name,
                DATE_FORMAT(fa.created_at, '%Y-%m-%d %H:%i:%s') as formatted_date
            FROM franchise_applications fa
            LEFT JOIN operators o ON fa.operator_id = o.operator_id
            WHERE 1=1
        `;
        const params = [];
        
        if (status) {
            query += ' AND fa.status = ?';
            params.push(status);
        }
        
        if (search) {
            query += ' AND (CONCAT(o.first_name, " ", o.last_name) LIKE ? OR fa.email LIKE ?)';
            params.push(`%${search}%`, `%${search}%`);
        }
        
        query += ' ORDER BY fa.created_at DESC LIMIT ? OFFSET ?';
        params.push(parseInt(limit), parseInt(offset));
        
        const result = await executeQuery(query, params);
        
        if (!result.success) {
            return res.status(500).json({
                success: false,
                message: 'Failed to fetch franchise applications',
                error: result.error
            });
        }
        
        // Get total count
        let countQuery = 'SELECT COUNT(*) as total FROM franchise_applications fa LEFT JOIN operators o ON fa.operator_id = o.operator_id WHERE 1=1';
        const countParams = [];
        
        if (status) {
            countQuery += ' AND status = ?';
            countParams.push(status);
        }
        
        if (search) {
            countQuery += ' AND (CONCAT(o.first_name, " ", o.last_name) LIKE ? OR fa.email LIKE ?)';
            countParams.push(`%${search}%`, `%${search}%`);
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

// GET /api/franchise/applications/:id - Get specific franchise application
router.get('/applications/:id', async (req, res) => {
    try {
        const { id } = req.params;
        
        const query = `
            SELECT 
                fa.*,
                CONCAT(o.first_name, ' ', o.last_name) as full_name,
                o.first_name,
                o.last_name,
                DATE_FORMAT(fa.created_at, '%Y-%m-%d %H:%i:%s') as formatted_date
            FROM franchise_applications fa
            LEFT JOIN operators o ON fa.operator_id = o.operator_id
            WHERE fa.application_id = ?
        `;
        
        const result = await executeQuery(query, [id]);
        
        if (!result.success) {
            return res.status(500).json({
                success: false,
                message: 'Failed to fetch franchise application',
                error: result.error
            });
        }
        
        if (result.data.length === 0) {
            return res.status(404).json({
                success: false,
                message: 'Franchise application not found'
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

// POST /api/franchise/applications - Create new franchise application
router.post('/applications', [
    body('operator_id').notEmpty().withMessage('Operator ID is required'),
    body('email').isEmail().withMessage('Valid email is required'),
    body('phone').notEmpty().withMessage('Phone number is required'),
    body('address').notEmpty().withMessage('Address is required'),
    body('route_description').notEmpty().withMessage('Route description is required')
], handleValidationErrors, async (req, res) => {
    try {
        const {
            operator_id,
            email,
            phone,
            address,
            route_description,
            vehicle_type,
            license_number
        } = req.body;
        
        const query = `
            INSERT INTO franchise_applications (
                operator_id, email, phone, address,
                route_description, vehicle_type, license_number,
                status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        `;
        
        const result = await executeQuery(query, [
            operator_id, email, phone, address,
            route_description, vehicle_type, license_number
        ]);
        
        if (!result.success) {
            return res.status(500).json({
                success: false,
                message: 'Failed to create franchise application',
                error: result.error
            });
        }
        
        res.status(201).json({
            success: true,
            message: 'Franchise application created successfully',
            data: {
                id: result.data.insertId,
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

// PUT /api/franchise/applications/:id/status - Update application status
router.put('/applications/:id/status', [
    body('status').isIn(['pending', 'approved', 'rejected', 'under_review']).withMessage('Invalid status')
], handleValidationErrors, async (req, res) => {
    try {
        const { id } = req.params;
        const { status, remarks } = req.body;
        
        const query = `
            UPDATE franchise_applications 
            SET status = ?, remarks = ?, updated_at = NOW()
            WHERE application_id = ?
        `;
        
        const result = await executeQuery(query, [status, remarks, id]);
        
        if (!result.success) {
            return res.status(500).json({
                success: false,
                message: 'Failed to update application status',
                error: result.error
            });
        }
        
        if (result.data.affectedRows === 0) {
            return res.status(404).json({
                success: false,
                message: 'Franchise application not found'
            });
        }
        
        res.json({
            success: true,
            message: 'Application status updated successfully'
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: 'Internal server error',
            error: error.message
        });
    }
});

// GET /api/franchise/routes - Get all routes
router.get('/routes', async (req, res) => {
    try {
        const query = `
            SELECT 
                r.*,
                DATE_FORMAT(r.created_at, '%Y-%m-%d %H:%i:%s') as formatted_date
            FROM routes r
            ORDER BY r.route_name ASC
        `;
        
        const result = await executeQuery(query);
        
        if (!result.success) {
            return res.status(500).json({
                success: false,
                message: 'Failed to fetch routes',
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

// GET /api/franchise/statistics - Get franchise statistics
router.get('/statistics', async (req, res) => {
    try {
        const queries = {
            total: 'SELECT COUNT(*) as count FROM franchise_applications',
            pending: 'SELECT COUNT(*) as count FROM franchise_applications WHERE status = "pending"',
            approved: 'SELECT COUNT(*) as count FROM franchise_applications WHERE status = "approved"',
            rejected: 'SELECT COUNT(*) as count FROM franchise_applications WHERE status = "rejected"'
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