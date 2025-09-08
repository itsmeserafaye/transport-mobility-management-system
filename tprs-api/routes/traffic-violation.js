const express = require('express');
const router = express.Router();
const db = require('../config/database');
const { authenticateToken, optionalAuth, authorize } = require('../middleware/auth');
const { validatePagination, validateId, validateViolation, validateDateRange } = require('../middleware/validation');

// Violation endpoints

// GET /api/v1/traffic-violation/violations - Get all violations with pagination
router.get('/violations', validatePagination, async (req, res) => {
    try {
        const { page = 1, limit = 10, sort = 'violation_date', order = 'desc', search, status, start_date, end_date } = req.query;
        const offset = (page - 1) * limit;
        
        let whereClause = '';
        let queryParams = [];
        
        const conditions = [];
        
        if (search) {
            conditions.push('(v.ticket_number LIKE ? OR v.violation_type LIKE ? OR v.location LIKE ? OR CONCAT(o.first_name, " ", o.last_name) LIKE ?)');
            const searchTerm = `%${search}%`;
            queryParams.push(searchTerm, searchTerm, searchTerm, searchTerm);
        }
        
        if (status) {
            conditions.push('v.settlement_status = ?');
            queryParams.push(status);
        }
        
        if (start_date) {
            conditions.push('v.violation_date >= ?');
            queryParams.push(start_date);
        }
        
        if (end_date) {
            conditions.push('v.violation_date <= ?');
            queryParams.push(end_date);
        }
        
        if (conditions.length > 0) {
            whereClause = 'WHERE ' + conditions.join(' AND ');
        }
        
        const countQuery = `
            SELECT COUNT(*) as total 
            FROM violations v 
            LEFT JOIN operators o ON v.operator_id = o.id 
            LEFT JOIN vehicles vh ON v.vehicle_id = vh.id 
            ${whereClause}
        `;
        
        const dataQuery = `
            SELECT v.id, v.ticket_number, v.operator_id, v.vehicle_id, v.violation_type, 
                   v.violation_date, v.location, v.fine_amount, v.officer_name, 
                   v.description, v.settlement_status, v.created_at, v.updated_at,
                   CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                   o.contact_number as operator_contact,
                   vh.plate_number as vehicle_plate
            FROM violations v 
            LEFT JOIN operators o ON v.operator_id = o.id 
            LEFT JOIN vehicles vh ON v.vehicle_id = vh.id 
            ${whereClause}
            ORDER BY v.${sort} ${order}
            LIMIT ? OFFSET ?
        `;
        
        const [countResult] = await db.query(countQuery, queryParams);
        const total = countResult[0].total;
        
        const violations = await db.query(dataQuery, [...queryParams, parseInt(limit), parseInt(offset)]);
        
        res.json({
            success: true,
            data: violations,
            pagination: {
                page: parseInt(page),
                limit: parseInt(limit),
                total,
                pages: Math.ceil(total / limit)
            }
        });
    } catch (error) {
        console.error('Error fetching violations:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch violations',
            code: 'FETCH_ERROR'
        });
    }
});

// GET /api/v1/traffic-violation/violations/:id - Get violation by ID
router.get('/violations/:id', validateId, async (req, res) => {
    try {
        const { id } = req.params;
        
        const query = `
            SELECT v.id, v.ticket_number, v.operator_id, v.vehicle_id, v.violation_type, 
                   v.violation_date, v.location, v.fine_amount, v.officer_name, 
                   v.description, v.settlement_status, v.created_at, v.updated_at,
                   CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                   o.contact_number as operator_contact,
                   o.email as operator_email,
                   vh.plate_number as vehicle_plate,
                   vh.make as vehicle_make,
                   vh.model as vehicle_model
            FROM violations v 
            LEFT JOIN operators o ON v.operator_id = o.id 
            LEFT JOIN vehicles vh ON v.vehicle_id = vh.id 
            WHERE v.id = ?
        `;
        
        const violations = await db.query(query, [id]);
        
        if (violations.length === 0) {
            return res.status(404).json({
                success: false,
                error: 'Violation not found',
                code: 'NOT_FOUND'
            });
        }
        
        res.json({
            success: true,
            data: violations[0]
        });
    } catch (error) {
        console.error('Error fetching violation:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch violation',
            code: 'FETCH_ERROR'
        });
    }
});

// POST /api/v1/traffic-violation/violations - Create new violation
router.post('/violations', authenticateToken, authorize(['admin', 'staff', 'officer']), validateViolation, async (req, res) => {
    try {
        const {
            ticket_number, operator_id, vehicle_id, violation_type, 
            violation_date, location, fine_amount, officer_name, 
            description, settlement_status
        } = req.body;
        
        // Check if operator exists
        const operatorCheck = await db.query('SELECT id FROM operators WHERE id = ?', [operator_id]);
        if (operatorCheck.length === 0) {
            return res.status(400).json({
                success: false,
                error: 'Operator not found',
                code: 'OPERATOR_NOT_FOUND'
            });
        }
        
        // Check if vehicle exists (if provided)
        if (vehicle_id) {
            const vehicleCheck = await db.query('SELECT id FROM vehicles WHERE id = ?', [vehicle_id]);
            if (vehicleCheck.length === 0) {
                return res.status(400).json({
                    success: false,
                    error: 'Vehicle not found',
                    code: 'VEHICLE_NOT_FOUND'
                });
            }
        }
        
        const query = `
            INSERT INTO violations (
                ticket_number, operator_id, vehicle_id, violation_type, 
                violation_date, location, fine_amount, officer_name, 
                description, settlement_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        `;
        
        const result = await db.query(query, [
            ticket_number, operator_id, vehicle_id || null, violation_type, 
            violation_date, location, fine_amount, officer_name, 
            description || null, settlement_status || 'unpaid'
        ]);
        
        res.status(201).json({
            success: true,
            message: 'Violation created successfully',
            data: {
                id: result.insertId,
                ticket_number, operator_id, vehicle_id, violation_type, 
                violation_date, location, fine_amount, officer_name, 
                description, settlement_status: settlement_status || 'unpaid'
            }
        });
    } catch (error) {
        console.error('Error creating violation:', error);
        if (error.code === 'ER_DUP_ENTRY') {
            return res.status(409).json({
                success: false,
                error: 'Ticket number already exists',
                code: 'DUPLICATE_TICKET'
            });
        }
        res.status(500).json({
            success: false,
            error: 'Failed to create violation',
            code: 'CREATE_ERROR'
        });
    }
});

// PUT /api/v1/traffic-violation/violations/:id - Update violation
router.put('/violations/:id', authenticateToken, authorize(['admin', 'staff']), validateId, validateViolation, async (req, res) => {
    try {
        const { id } = req.params;
        const {
            ticket_number, operator_id, vehicle_id, violation_type, 
            violation_date, location, fine_amount, officer_name, 
            description, settlement_status
        } = req.body;
        
        // Check if operator exists
        const operatorCheck = await db.query('SELECT id FROM operators WHERE id = ?', [operator_id]);
        if (operatorCheck.length === 0) {
            return res.status(400).json({
                success: false,
                error: 'Operator not found',
                code: 'OPERATOR_NOT_FOUND'
            });
        }
        
        // Check if vehicle exists (if provided)
        if (vehicle_id) {
            const vehicleCheck = await db.query('SELECT id FROM vehicles WHERE id = ?', [vehicle_id]);
            if (vehicleCheck.length === 0) {
                return res.status(400).json({
                    success: false,
                    error: 'Vehicle not found',
                    code: 'VEHICLE_NOT_FOUND'
                });
            }
        }
        
        const query = `
            UPDATE violations SET
                ticket_number = ?, operator_id = ?, vehicle_id = ?, violation_type = ?, 
                violation_date = ?, location = ?, fine_amount = ?, officer_name = ?, 
                description = ?, settlement_status = ?, updated_at = NOW()
            WHERE id = ?
        `;
        
        const result = await db.query(query, [
            ticket_number, operator_id, vehicle_id || null, violation_type, 
            violation_date, location, fine_amount, officer_name, 
            description || null, settlement_status, id
        ]);
        
        if (result.affectedRows === 0) {
            return res.status(404).json({
                success: false,
                error: 'Violation not found',
                code: 'NOT_FOUND'
            });
        }
        
        res.json({
            success: true,
            message: 'Violation updated successfully'
        });
    } catch (error) {
        console.error('Error updating violation:', error);
        if (error.code === 'ER_DUP_ENTRY') {
            return res.status(409).json({
                success: false,
                error: 'Ticket number already exists',
                code: 'DUPLICATE_TICKET'
            });
        }
        res.status(500).json({
            success: false,
            error: 'Failed to update violation',
            code: 'UPDATE_ERROR'
        });
    }
});

// PUT /api/v1/traffic-violation/violations/:id/settle - Settle violation payment
router.put('/violations/:id/settle', authenticateToken, authorize(['admin', 'staff', 'cashier']), validateId, async (req, res) => {
    try {
        const { id } = req.params;
        const { payment_method, payment_reference, notes } = req.body;
        
        const query = `
            UPDATE violations SET
                settlement_status = 'paid', 
                payment_date = NOW(),
                payment_method = ?,
                payment_reference = ?,
                settlement_notes = ?,
                updated_at = NOW()
            WHERE id = ? AND settlement_status = 'unpaid'
        `;
        
        const result = await db.query(query, [payment_method || null, payment_reference || null, notes || null, id]);
        
        if (result.affectedRows === 0) {
            return res.status(404).json({
                success: false,
                error: 'Violation not found or already settled',
                code: 'NOT_FOUND_OR_SETTLED'
            });
        }
        
        res.json({
            success: true,
            message: 'Violation payment settled successfully'
        });
    } catch (error) {
        console.error('Error settling violation:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to settle violation payment',
            code: 'SETTLE_ERROR'
        });
    }
});

// DELETE /api/v1/traffic-violation/violations/:id - Delete violation
router.delete('/violations/:id', authenticateToken, authorize(['admin']), validateId, async (req, res) => {
    try {
        const { id } = req.params;
        
        const query = 'DELETE FROM violations WHERE id = ?';
        const result = await db.query(query, [id]);
        
        if (result.affectedRows === 0) {
            return res.status(404).json({
                success: false,
                error: 'Violation not found',
                code: 'NOT_FOUND'
            });
        }
        
        res.json({
            success: true,
            message: 'Violation deleted successfully'
        });
    } catch (error) {
        console.error('Error deleting violation:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to delete violation',
            code: 'DELETE_ERROR'
        });
    }
});

// Revenue endpoints

// GET /api/v1/traffic-violation/revenue/summary - Get revenue summary
router.get('/revenue/summary', optionalAuth, validateDateRange, async (req, res) => {
    try {
        const { start_date, end_date, group_by = 'day' } = req.query;
        
        let dateFilter = '';
        let queryParams = [];
        
        if (start_date && end_date) {
            dateFilter = 'WHERE v.payment_date BETWEEN ? AND ?';
            queryParams = [start_date, end_date];
        } else if (start_date) {
            dateFilter = 'WHERE v.payment_date >= ?';
            queryParams = [start_date];
        } else if (end_date) {
            dateFilter = 'WHERE v.payment_date <= ?';
            queryParams = [end_date];
        }
        
        let groupByClause = '';
        let selectClause = '';
        
        switch (group_by) {
            case 'month':
                selectClause = 'DATE_FORMAT(v.payment_date, "%Y-%m") as period';
                groupByClause = 'GROUP BY DATE_FORMAT(v.payment_date, "%Y-%m")';
                break;
            case 'year':
                selectClause = 'YEAR(v.payment_date) as period';
                groupByClause = 'GROUP BY YEAR(v.payment_date)';
                break;
            default: // day
                selectClause = 'DATE(v.payment_date) as period';
                groupByClause = 'GROUP BY DATE(v.payment_date)';
        }
        
        const summaryQuery = `
            SELECT 
                ${selectClause},
                COUNT(*) as total_violations,
                SUM(v.fine_amount) as total_revenue,
                AVG(v.fine_amount) as average_fine
            FROM violations v 
            ${dateFilter} AND v.settlement_status = 'paid'
            ${groupByClause}
            ORDER BY period DESC
        `;
        
        const overallQuery = `
            SELECT 
                COUNT(*) as total_violations,
                COUNT(CASE WHEN settlement_status = 'paid' THEN 1 END) as paid_violations,
                COUNT(CASE WHEN settlement_status = 'unpaid' THEN 1 END) as unpaid_violations,
                COUNT(CASE WHEN settlement_status = 'contested' THEN 1 END) as contested_violations,
                SUM(CASE WHEN settlement_status = 'paid' THEN fine_amount ELSE 0 END) as total_collected,
                SUM(CASE WHEN settlement_status = 'unpaid' THEN fine_amount ELSE 0 END) as total_outstanding,
                AVG(CASE WHEN settlement_status = 'paid' THEN fine_amount END) as average_fine
            FROM violations v 
            ${dateFilter.replace('payment_date', 'violation_date')}
        `;
        
        const summary = await db.query(summaryQuery, queryParams);
        const [overall] = await db.query(overallQuery, queryParams);
        
        res.json({
            success: true,
            data: {
                overall: overall[0],
                breakdown: summary
            }
        });
    } catch (error) {
        console.error('Error fetching revenue summary:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch revenue summary',
            code: 'FETCH_ERROR'
        });
    }
});

// GET /api/v1/traffic-violation/revenue/top-violations - Get top violation types by revenue
router.get('/revenue/top-violations', optionalAuth, validateDateRange, async (req, res) => {
    try {
        const { start_date, end_date, limit = 10 } = req.query;
        
        let dateFilter = '';
        let queryParams = [];
        
        if (start_date && end_date) {
            dateFilter = 'WHERE v.payment_date BETWEEN ? AND ?';
            queryParams = [start_date, end_date];
        } else if (start_date) {
            dateFilter = 'WHERE v.payment_date >= ?';
            queryParams = [start_date];
        } else if (end_date) {
            dateFilter = 'WHERE v.payment_date <= ?';
            queryParams = [end_date];
        }
        
        const query = `
            SELECT 
                v.violation_type,
                COUNT(*) as violation_count,
                SUM(v.fine_amount) as total_revenue,
                AVG(v.fine_amount) as average_fine,
                MIN(v.fine_amount) as min_fine,
                MAX(v.fine_amount) as max_fine
            FROM violations v 
            ${dateFilter} AND v.settlement_status = 'paid'
            GROUP BY v.violation_type
            ORDER BY total_revenue DESC
            LIMIT ?
        `;
        
        queryParams.push(parseInt(limit));
        
        const topViolations = await db.query(query, queryParams);
        
        res.json({
            success: true,
            data: topViolations
        });
    } catch (error) {
        console.error('Error fetching top violations:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch top violations',
            code: 'FETCH_ERROR'
        });
    }
});

// GET /api/v1/traffic-violation/revenue/by-location - Get revenue by location
router.get('/revenue/by-location', optionalAuth, validateDateRange, async (req, res) => {
    try {
        const { start_date, end_date, limit = 10 } = req.query;
        
        let dateFilter = '';
        let queryParams = [];
        
        if (start_date && end_date) {
            dateFilter = 'WHERE v.payment_date BETWEEN ? AND ?';
            queryParams = [start_date, end_date];
        } else if (start_date) {
            dateFilter = 'WHERE v.payment_date >= ?';
            queryParams = [start_date];
        } else if (end_date) {
            dateFilter = 'WHERE v.payment_date <= ?';
            queryParams = [end_date];
        }
        
        const query = `
            SELECT 
                v.location,
                COUNT(*) as violation_count,
                SUM(v.fine_amount) as total_revenue,
                AVG(v.fine_amount) as average_fine
            FROM violations v 
            ${dateFilter} AND v.settlement_status = 'paid'
            GROUP BY v.location
            ORDER BY total_revenue DESC
            LIMIT ?
        `;
        
        queryParams.push(parseInt(limit));
        
        const locationRevenue = await db.query(query, queryParams);
        
        res.json({
            success: true,
            data: locationRevenue
        });
    } catch (error) {
        console.error('Error fetching location revenue:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch location revenue',
            code: 'FETCH_ERROR'
        });
    }
});

// GET /api/v1/traffic-violation/revenue/outstanding - Get outstanding payments
router.get('/revenue/outstanding', validatePagination, async (req, res) => {
    try {
        const { page = 1, limit = 10, sort = 'violation_date', order = 'desc' } = req.query;
        const offset = (page - 1) * limit;
        
        const countQuery = `
            SELECT COUNT(*) as total 
            FROM violations v 
            LEFT JOIN operators o ON v.operator_id = o.id 
            WHERE v.settlement_status = 'unpaid'
        `;
        
        const dataQuery = `
            SELECT v.id, v.ticket_number, v.violation_type, v.violation_date, 
                   v.location, v.fine_amount, v.officer_name,
                   CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                   o.contact_number as operator_contact,
                   DATEDIFF(CURDATE(), v.violation_date) as days_overdue
            FROM violations v 
            LEFT JOIN operators o ON v.operator_id = o.id 
            WHERE v.settlement_status = 'unpaid'
            ORDER BY v.${sort} ${order}
            LIMIT ? OFFSET ?
        `;
        
        const [countResult] = await db.query(countQuery);
        const total = countResult[0].total;
        
        const outstanding = await db.query(dataQuery, [parseInt(limit), parseInt(offset)]);
        
        // Calculate total outstanding amount
        const totalQuery = `
            SELECT SUM(fine_amount) as total_outstanding 
            FROM violations 
            WHERE settlement_status = 'unpaid'
        `;
        
        const [totalResult] = await db.query(totalQuery);
        
        res.json({
            success: true,
            data: outstanding,
            summary: {
                total_outstanding_amount: totalResult[0].total_outstanding || 0,
                total_outstanding_count: total
            },
            pagination: {
                page: parseInt(page),
                limit: parseInt(limit),
                total,
                pages: Math.ceil(total / limit)
            }
        });
    } catch (error) {
        console.error('Error fetching outstanding payments:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch outstanding payments',
            code: 'FETCH_ERROR'
        });
    }
});

module.exports = router;