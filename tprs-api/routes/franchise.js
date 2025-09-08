const express = require('express');
const router = express.Router();
const db = require('../config/database');
const { authenticateToken, optionalAuth, authorize } = require('../middleware/auth');
const { validatePagination, validateId, validateFranchise, validateDateRange } = require('../middleware/validation');

// Franchise endpoints

// GET /api/v1/franchise/applications - Get all franchise applications with pagination
router.get('/applications', validatePagination, async (req, res) => {
    try {
        const { page = 1, limit = 10, sort = 'created_at', order = 'desc', search, status } = req.query;
        const offset = (page - 1) * limit;
        
        let whereClause = '';
        let queryParams = [];
        
        const conditions = [];
        
        if (search) {
            conditions.push('(f.franchise_number LIKE ? OR f.route_assigned LIKE ? OR CONCAT(o.first_name, " ", o.last_name) LIKE ?)');
            const searchTerm = `%${search}%`;
            queryParams.push(searchTerm, searchTerm, searchTerm);
        }
        
        if (status) {
            conditions.push('f.status = ?');
            queryParams.push(status);
        }
        
        if (conditions.length > 0) {
            whereClause = 'WHERE ' + conditions.join(' AND ');
        }
        
        const countQuery = `
            SELECT COUNT(*) as total 
            FROM franchises f 
            LEFT JOIN operators o ON f.operator_id = o.id 
            ${whereClause}
        `;
        
        const dataQuery = `
            SELECT f.id, f.franchise_number, f.operator_id, f.route_assigned, 
                   f.issue_date, f.expiry_date, f.status, f.created_at, f.updated_at,
                   CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                   o.contact_number as operator_contact,
                   DATEDIFF(f.expiry_date, CURDATE()) as days_until_expiry
            FROM franchises f 
            LEFT JOIN operators o ON f.operator_id = o.id 
            ${whereClause}
            ORDER BY f.${sort} ${order}
            LIMIT ? OFFSET ?
        `;
        
        const [countResult] = await db.query(countQuery, queryParams);
        const total = countResult[0].total;
        
        const franchises = await db.query(dataQuery, [...queryParams, parseInt(limit), parseInt(offset)]);
        
        res.json({
            success: true,
            data: franchises,
            pagination: {
                page: parseInt(page),
                limit: parseInt(limit),
                total,
                pages: Math.ceil(total / limit)
            }
        });
    } catch (error) {
        console.error('Error fetching franchise applications:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch franchise applications',
            code: 'FETCH_ERROR'
        });
    }
});

// GET /api/v1/franchise/applications/:id - Get franchise application by ID
router.get('/applications/:id', validateId, async (req, res) => {
    try {
        const { id } = req.params;
        
        const query = `
            SELECT f.id, f.franchise_number, f.operator_id, f.route_assigned, 
                   f.issue_date, f.expiry_date, f.status, f.created_at, f.updated_at,
                   CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                   o.contact_number as operator_contact,
                   o.email as operator_email,
                   o.address as operator_address,
                   o.license_number as operator_license,
                   DATEDIFF(f.expiry_date, CURDATE()) as days_until_expiry
            FROM franchises f 
            LEFT JOIN operators o ON f.operator_id = o.id 
            WHERE f.id = ?
        `;
        
        const franchises = await db.query(query, [id]);
        
        if (franchises.length === 0) {
            return res.status(404).json({
                success: false,
                error: 'Franchise application not found',
                code: 'NOT_FOUND'
            });
        }
        
        res.json({
            success: true,
            data: franchises[0]
        });
    } catch (error) {
        console.error('Error fetching franchise application:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch franchise application',
            code: 'FETCH_ERROR'
        });
    }
});

// POST /api/v1/franchise/applications - Create new franchise application
router.post('/applications', authenticateToken, authorize(['admin', 'staff']), validateFranchise, async (req, res) => {
    try {
        const {
            franchise_number, operator_id, route_assigned, 
            issue_date, expiry_date, status
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
        
        const query = `
            INSERT INTO franchises (
                franchise_number, operator_id, route_assigned, 
                issue_date, expiry_date, status
            ) VALUES (?, ?, ?, ?, ?, ?)
        `;
        
        const result = await db.query(query, [
            franchise_number, operator_id, route_assigned, 
            issue_date, expiry_date, status || 'valid'
        ]);
        
        res.status(201).json({
            success: true,
            message: 'Franchise application created successfully',
            data: {
                id: result.insertId,
                franchise_number, operator_id, route_assigned, 
                issue_date, expiry_date, status: status || 'valid'
            }
        });
    } catch (error) {
        console.error('Error creating franchise application:', error);
        if (error.code === 'ER_DUP_ENTRY') {
            return res.status(409).json({
                success: false,
                error: 'Franchise number already exists',
                code: 'DUPLICATE_FRANCHISE'
            });
        }
        res.status(500).json({
            success: false,
            error: 'Failed to create franchise application',
            code: 'CREATE_ERROR'
        });
    }
});

// PUT /api/v1/franchise/applications/:id - Update franchise application
router.put('/applications/:id', authenticateToken, authorize(['admin', 'staff']), validateId, validateFranchise, async (req, res) => {
    try {
        const { id } = req.params;
        const {
            franchise_number, operator_id, route_assigned, 
            issue_date, expiry_date, status
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
        
        const query = `
            UPDATE franchises SET
                franchise_number = ?, operator_id = ?, route_assigned = ?, 
                issue_date = ?, expiry_date = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        `;
        
        const result = await db.query(query, [
            franchise_number, operator_id, route_assigned, 
            issue_date, expiry_date, status, id
        ]);
        
        if (result.affectedRows === 0) {
            return res.status(404).json({
                success: false,
                error: 'Franchise application not found',
                code: 'NOT_FOUND'
            });
        }
        
        res.json({
            success: true,
            message: 'Franchise application updated successfully'
        });
    } catch (error) {
        console.error('Error updating franchise application:', error);
        if (error.code === 'ER_DUP_ENTRY') {
            return res.status(409).json({
                success: false,
                error: 'Franchise number already exists',
                code: 'DUPLICATE_FRANCHISE'
            });
        }
        res.status(500).json({
            success: false,
            error: 'Failed to update franchise application',
            code: 'UPDATE_ERROR'
        });
    }
});

// DELETE /api/v1/franchise/applications/:id - Delete franchise application
router.delete('/applications/:id', authenticateToken, authorize(['admin']), validateId, async (req, res) => {
    try {
        const { id } = req.params;
        
        const query = 'DELETE FROM franchises WHERE id = ?';
        const result = await db.query(query, [id]);
        
        if (result.affectedRows === 0) {
            return res.status(404).json({
                success: false,
                error: 'Franchise application not found',
                code: 'NOT_FOUND'
            });
        }
        
        res.json({
            success: true,
            message: 'Franchise application deleted successfully'
        });
    } catch (error) {
        console.error('Error deleting franchise application:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to delete franchise application',
            code: 'DELETE_ERROR'
        });
    }
});

// Franchise lifecycle endpoints

// GET /api/v1/franchise/lifecycle/summary - Get franchise lifecycle summary
router.get('/lifecycle/summary', optionalAuth, async (req, res) => {
    try {
        const summaryQuery = `
            SELECT 
                COUNT(CASE WHEN status = 'valid' THEN 1 END) as valid_franchises,
                COUNT(CASE WHEN status = 'expired' THEN 1 END) as expired_franchises,
                COUNT(CASE WHEN status = 'suspended' THEN 1 END) as suspended_franchises,
                COUNT(CASE WHEN status = 'revoked' THEN 1 END) as revoked_franchises,
                COUNT(CASE WHEN expiry_date < CURDATE() AND status = 'valid' THEN 1 END) as overdue_renewals,
                COUNT(CASE WHEN expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as expiring_soon
            FROM franchises
        `;
        
        const [summary] = await db.query(summaryQuery);
        
        res.json({
            success: true,
            data: summary[0]
        });
    } catch (error) {
        console.error('Error fetching franchise lifecycle summary:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch franchise lifecycle summary',
            code: 'FETCH_ERROR'
        });
    }
});

// GET /api/v1/franchise/lifecycle/expiring - Get franchises expiring soon
router.get('/lifecycle/expiring', validatePagination, async (req, res) => {
    try {
        const { page = 1, limit = 10, days = 30 } = req.query;
        const offset = (page - 1) * limit;
        
        const countQuery = `
            SELECT COUNT(*) as total 
            FROM franchises f
            LEFT JOIN operators o ON f.operator_id = o.id
            WHERE f.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
            AND f.status = 'valid'
        `;
        
        const dataQuery = `
            SELECT f.id, f.franchise_number, f.route_assigned, f.expiry_date,
                   CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                   o.contact_number as operator_contact,
                   DATEDIFF(f.expiry_date, CURDATE()) as days_until_expiry
            FROM franchises f
            LEFT JOIN operators o ON f.operator_id = o.id
            WHERE f.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
            AND f.status = 'valid'
            ORDER BY f.expiry_date ASC
            LIMIT ? OFFSET ?
        `;
        
        const [countResult] = await db.query(countQuery, [days]);
        const total = countResult[0].total;
        
        const expiringFranchises = await db.query(dataQuery, [days, parseInt(limit), parseInt(offset)]);
        
        res.json({
            success: true,
            data: expiringFranchises,
            pagination: {
                page: parseInt(page),
                limit: parseInt(limit),
                total,
                pages: Math.ceil(total / limit)
            }
        });
    } catch (error) {
        console.error('Error fetching expiring franchises:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch expiring franchises',
            code: 'FETCH_ERROR'
        });
    }
});

// PUT /api/v1/franchise/lifecycle/:id/renew - Renew franchise
router.put('/lifecycle/:id/renew', authenticateToken, authorize(['admin', 'staff']), validateId, async (req, res) => {
    try {
        const { id } = req.params;
        const { new_expiry_date, notes } = req.body;
        
        if (!new_expiry_date) {
            return res.status(400).json({
                success: false,
                error: 'New expiry date is required',
                code: 'MISSING_EXPIRY_DATE'
            });
        }
        
        const query = `
            UPDATE franchises SET
                expiry_date = ?, status = 'valid', updated_at = NOW()
            WHERE id = ?
        `;
        
        const result = await db.query(query, [new_expiry_date, id]);
        
        if (result.affectedRows === 0) {
            return res.status(404).json({
                success: false,
                error: 'Franchise not found',
                code: 'NOT_FOUND'
            });
        }
        
        // Log the renewal action
        const logQuery = `
            INSERT INTO franchise_logs (franchise_id, action, notes, created_by, created_at)
            VALUES (?, 'renewed', ?, ?, NOW())
        `;
        
        await db.query(logQuery, [id, notes || 'Franchise renewed', req.user?.id || null]);
        
        res.json({
            success: true,
            message: 'Franchise renewed successfully'
        });
    } catch (error) {
        console.error('Error renewing franchise:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to renew franchise',
            code: 'RENEW_ERROR'
        });
    }
});

// PUT /api/v1/franchise/lifecycle/:id/suspend - Suspend franchise
router.put('/lifecycle/:id/suspend', authenticateToken, authorize(['admin', 'staff']), validateId, async (req, res) => {
    try {
        const { id } = req.params;
        const { reason } = req.body;
        
        if (!reason) {
            return res.status(400).json({
                success: false,
                error: 'Suspension reason is required',
                code: 'MISSING_REASON'
            });
        }
        
        const query = `
            UPDATE franchises SET
                status = 'suspended', updated_at = NOW()
            WHERE id = ?
        `;
        
        const result = await db.query(query, [id]);
        
        if (result.affectedRows === 0) {
            return res.status(404).json({
                success: false,
                error: 'Franchise not found',
                code: 'NOT_FOUND'
            });
        }
        
        // Log the suspension action
        const logQuery = `
            INSERT INTO franchise_logs (franchise_id, action, notes, created_by, created_at)
            VALUES (?, 'suspended', ?, ?, NOW())
        `;
        
        await db.query(logQuery, [id, reason, req.user?.id || null]);
        
        res.json({
            success: true,
            message: 'Franchise suspended successfully'
        });
    } catch (error) {
        console.error('Error suspending franchise:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to suspend franchise',
            code: 'SUSPEND_ERROR'
        });
    }
});

// Route management endpoints

// GET /api/v1/franchise/routes - Get all routes with franchise assignments
router.get('/routes', validatePagination, async (req, res) => {
    try {
        const { page = 1, limit = 10, search } = req.query;
        const offset = (page - 1) * limit;
        
        let whereClause = '';
        let queryParams = [];
        
        if (search) {
            whereClause = 'WHERE f.route_assigned LIKE ?';
            queryParams.push(`%${search}%`);
        }
        
        const countQuery = `
            SELECT COUNT(DISTINCT f.route_assigned) as total 
            FROM franchises f 
            ${whereClause}
        `;
        
        const dataQuery = `
            SELECT 
                f.route_assigned as route_name,
                COUNT(f.id) as total_franchises,
                COUNT(CASE WHEN f.status = 'valid' THEN 1 END) as active_franchises,
                COUNT(CASE WHEN f.status = 'expired' THEN 1 END) as expired_franchises,
                COUNT(CASE WHEN f.status = 'suspended' THEN 1 END) as suspended_franchises,
                MIN(f.issue_date) as first_issued,
                MAX(f.expiry_date) as latest_expiry
            FROM franchises f 
            ${whereClause}
            GROUP BY f.route_assigned
            ORDER BY f.route_assigned ASC
            LIMIT ? OFFSET ?
        `;
        
        const [countResult] = await db.query(countQuery, queryParams);
        const total = countResult[0].total;
        
        const routes = await db.query(dataQuery, [...queryParams, parseInt(limit), parseInt(offset)]);
        
        res.json({
            success: true,
            data: routes,
            pagination: {
                page: parseInt(page),
                limit: parseInt(limit),
                total,
                pages: Math.ceil(total / limit)
            }
        });
    } catch (error) {
        console.error('Error fetching routes:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch routes',
            code: 'FETCH_ERROR'
        });
    }
});

// GET /api/v1/franchise/routes/:route/franchises - Get franchises for specific route
router.get('/routes/:route/franchises', validatePagination, async (req, res) => {
    try {
        const { route } = req.params;
        const { page = 1, limit = 10, status } = req.query;
        const offset = (page - 1) * limit;
        
        let whereClause = 'WHERE f.route_assigned = ?';
        let queryParams = [route];
        
        if (status) {
            whereClause += ' AND f.status = ?';
            queryParams.push(status);
        }
        
        const countQuery = `
            SELECT COUNT(*) as total 
            FROM franchises f 
            LEFT JOIN operators o ON f.operator_id = o.id 
            ${whereClause}
        `;
        
        const dataQuery = `
            SELECT f.id, f.franchise_number, f.issue_date, f.expiry_date, f.status,
                   CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                   o.contact_number as operator_contact,
                   DATEDIFF(f.expiry_date, CURDATE()) as days_until_expiry
            FROM franchises f 
            LEFT JOIN operators o ON f.operator_id = o.id 
            ${whereClause}
            ORDER BY f.franchise_number ASC
            LIMIT ? OFFSET ?
        `;
        
        const [countResult] = await db.query(countQuery, queryParams);
        const total = countResult[0].total;
        
        const franchises = await db.query(dataQuery, [...queryParams, parseInt(limit), parseInt(offset)]);
        
        res.json({
            success: true,
            data: {
                route_name: route,
                franchises
            },
            pagination: {
                page: parseInt(page),
                limit: parseInt(limit),
                total,
                pages: Math.ceil(total / limit)
            }
        });
    } catch (error) {
        console.error('Error fetching route franchises:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch route franchises',
            code: 'FETCH_ERROR'
        });
    }
});

module.exports = router;