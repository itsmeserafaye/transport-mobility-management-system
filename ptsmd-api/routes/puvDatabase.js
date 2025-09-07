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

// GET /api/puv/vehicles - Get all vehicles
router.get('/vehicles', async (req, res) => {
    try {
        const { page = 1, limit = 10, status, search } = req.query;
        const offset = (page - 1) * limit;
        
        let query = `
            SELECT 
                v.*,
                o.first_name,
                o.last_name,
                CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                DATE_FORMAT(v.date_registered, '%Y-%m-%d %H:%i:%s') as formatted_date
            FROM vehicles v
            LEFT JOIN operators o ON v.operator_id = o.operator_id
            WHERE 1=1
        `;
        const params = [];
        
        if (status) {
            query += ' AND v.status = ?';
            params.push(status);
        }
        
        if (search) {
            query += ' AND (v.plate_number LIKE ? OR v.vehicle_type LIKE ? OR o.first_name LIKE ? OR o.last_name LIKE ?)';
            params.push(`%${search}%`, `%${search}%`, `%${search}%`, `%${search}%`);
        }
        
        query += ' ORDER BY v.date_registered DESC LIMIT ? OFFSET ?';
        params.push(parseInt(limit), parseInt(offset));
        
        const result = await executeQuery(query, params);
        
        if (!result.success) {
            return res.status(500).json({
                success: false,
                message: 'Failed to fetch vehicles',
                error: result.error
            });
        }
        
        // Get total count
        let countQuery = `
            SELECT COUNT(*) as total 
            FROM vehicles v
            LEFT JOIN operators o ON v.operator_id = o.operator_id
            WHERE 1=1
        `;
        const countParams = [];
        
        if (status) {
            countQuery += ' AND v.status = ?';
            countParams.push(status);
        }
        
        if (search) {
            countQuery += ' AND (v.plate_number LIKE ? OR v.vehicle_type LIKE ? OR o.first_name LIKE ? OR o.last_name LIKE ?)';
            countParams.push(`%${search}%`, `%${search}%`, `%${search}%`, `%${search}%`);
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

// GET /api/puv/vehicles/:id - Get specific vehicle
router.get('/vehicles/:id', async (req, res) => {
    try {
        const { id } = req.params;
        
        const query = `
            SELECT 
                v.*,
                o.first_name,
                o.last_name,
                CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                o.email as operator_email,
                o.phone as operator_phone,
                DATE_FORMAT(v.date_registered, '%Y-%m-%d %H:%i:%s') as formatted_date
            FROM vehicles v
            LEFT JOIN operators o ON v.operator_id = o.operator_id
            WHERE v.vehicle_id = ?
        `;
        
        const result = await executeQuery(query, [id]);
        
        if (!result.success) {
            return res.status(500).json({
                success: false,
                message: 'Failed to fetch vehicle',
                error: result.error
            });
        }
        
        if (result.data.length === 0) {
            return res.status(404).json({
                success: false,
                message: 'Vehicle not found'
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

// POST /api/puv/vehicles - Register new vehicle
router.post('/vehicles', [
    body('plate_number').notEmpty().withMessage('Plate number is required'),
    body('vehicle_type').notEmpty().withMessage('Vehicle type is required'),
    body('operator_id').isInt().withMessage('Valid operator ID is required'),
    body('route_id').optional().isInt().withMessage('Route ID must be an integer')
], handleValidationErrors, async (req, res) => {
    try {
        const {
            plate_number,
            vehicle_type,
            operator_id,
            route_id,
            engine_number,
            chassis_number,
            year_model,
            color
        } = req.body;
        
        // Check if plate number already exists
        const checkQuery = 'SELECT id FROM vehicles WHERE plate_number = ?';
        const checkResult = await executeQuery(checkQuery, [plate_number]);
        
        if (checkResult.success && checkResult.data.length > 0) {
            return res.status(400).json({
                success: false,
                message: 'Vehicle with this plate number already exists'
            });
        }
        
        const query = `
            INSERT INTO vehicles (
                plate_number, vehicle_type, operator_id, route_id,
                engine_number, chassis_number, year_model, color,
                status, date_registered
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
        `;
        
        const result = await executeQuery(query, [
            plate_number, vehicle_type, operator_id, route_id,
            engine_number, chassis_number, year_model, color
        ]);
        
        if (!result.success) {
            return res.status(500).json({
                success: false,
                message: 'Failed to register vehicle',
                error: result.error
            });
        }
        
        res.status(201).json({
            success: true,
            message: 'Vehicle registered successfully',
            data: {
                id: result.data.insertId,
                plate_number,
                status: 'active'
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

// GET /api/puv/operators - Get all operators
router.get('/operators', async (req, res) => {
    try {
        const { page = 1, limit = 10, search } = req.query;
        const offset = (page - 1) * limit;
        
        let query = `
            SELECT 
                o.*,
                CONCAT(o.first_name, ' ', o.last_name) as full_name,
                COUNT(v.id) as vehicle_count,
                DATE_FORMAT(o.date_registered, '%Y-%m-%d %H:%i:%s') as formatted_date
            FROM operators o
            LEFT JOIN vehicles v ON o.operator_id = v.operator_id
            WHERE 1=1
        `;
        const params = [];
        
        if (search) {
            query += ' AND (o.first_name LIKE ? OR o.last_name LIKE ? OR o.email LIKE ? OR o.license_number LIKE ?)';
            params.push(`%${search}%`, `%${search}%`, `%${search}%`, `%${search}%`);
        }
        
        query += ' GROUP BY o.operator_id ORDER BY o.date_registered DESC LIMIT ? OFFSET ?';
        params.push(parseInt(limit), parseInt(offset));
        
        const result = await executeQuery(query, params);
        
        if (!result.success) {
            return res.status(500).json({
                success: false,
                message: 'Failed to fetch operators',
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

// GET /api/puv/compliance - Get compliance status
router.get('/compliance', async (req, res) => {
    try {
        const { page = 1, limit = 10, status } = req.query;
        const offset = (page - 1) * limit;
        
        let query = `
            SELECT 
                c.*,
                v.plate_number,
                v.vehicle_type,
                CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                DATE_FORMAT(c.last_updated, '%Y-%m-%d %H:%i:%s') as formatted_date
            FROM compliance_status c
            JOIN vehicles v ON c.vehicle_id = v.id
            JOIN operators o ON v.operator_id = o.id
            WHERE 1=1
        `;
        const params = [];
        
        if (status) {
            query += ' AND c.overall_status = ?';
            params.push(status);
        }
        
        query += ' ORDER BY c.last_updated DESC LIMIT ? OFFSET ?';
        params.push(parseInt(limit), parseInt(offset));
        
        const result = await executeQuery(query, params);
        
        if (!result.success) {
            return res.status(500).json({
                success: false,
                message: 'Failed to fetch compliance data',
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

// GET /api/puv/statistics - Get PUV statistics
router.get('/statistics', async (req, res) => {
    try {
        const queries = {
            totalVehicles: 'SELECT COUNT(*) as count FROM vehicles',
            activeVehicles: 'SELECT COUNT(*) as count FROM vehicles WHERE status = "active"',
            totalOperators: 'SELECT COUNT(*) as count FROM operators',
            compliantVehicles: 'SELECT COUNT(*) as count FROM compliance_status WHERE overall_status = "compliant"',
            nonCompliantVehicles: 'SELECT COUNT(*) as count FROM compliance_status WHERE overall_status = "non_compliant"'
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