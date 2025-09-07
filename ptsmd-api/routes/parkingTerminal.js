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

// GET /api/parking-terminal/terminals - Get all terminals
router.get('/terminals', async (req, res) => {
    try {
        const { page = 1, limit = 10, status, search } = req.query;
        const offset = (page - 1) * limit;
        
        let query = `
            SELECT 
                t.*,
                COUNT(ta.assignment_id) as assigned_vehicles,
                DATE_FORMAT(t.created_at, '%Y-%m-%d %H:%i:%s') as formatted_date
            FROM terminals t
            LEFT JOIN terminal_assignments ta ON t.terminal_id = ta.terminal_id AND ta.status = 'active'
            WHERE 1=1
        `;
        const params = [];
        
        if (status) {
            query += ' AND t.status = ?';
            params.push(status);
        }
        
        if (search) {
            query += ' AND (t.terminal_name LIKE ? OR t.location LIKE ? OR t.address LIKE ?)';
            params.push(`%${search}%`, `%${search}%`, `%${search}%`);
        }
        
        query += ' GROUP BY t.terminal_id ORDER BY t.created_at DESC LIMIT ? OFFSET ?';
        params.push(parseInt(limit), parseInt(offset));
        
        const result = await executeQuery(query, params);
        
        if (!result.success) {
            return res.status(500).json({
                success: false,
                message: 'Failed to fetch terminals',
                error: result.error
            });
        }
        
        // Get total count
        let countQuery = 'SELECT COUNT(*) as total FROM terminals WHERE 1=1';
        const countParams = [];
        
        if (status) {
            countQuery += ' AND status = ?';
            countParams.push(status);
        }
        
        if (search) {
            countQuery += ' AND (terminal_name LIKE ? OR location LIKE ? OR address LIKE ?)';
            countParams.push(`%${search}%`, `%${search}%`, `%${search}%`);
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

// GET /api/parking-terminal/terminals/:id - Get specific terminal
router.get('/terminals/:id', async (req, res) => {
    try {
        const { id } = req.params;
        
        const query = `
            SELECT 
                t.*,
                COUNT(ta.id) as assigned_vehicles,
                DATE_FORMAT(t.created_at, '%Y-%m-%d %H:%i:%s') as formatted_date
            FROM terminals t
            LEFT JOIN terminal_assignments ta ON t.id = ta.terminal_id AND ta.status = 'active'
            WHERE t.id = ?
            GROUP BY t.id
        `;
        
        const result = await executeQuery(query, [id]);
        
        if (!result.success) {
            return res.status(500).json({
                success: false,
                message: 'Failed to fetch terminal',
                error: result.error
            });
        }
        
        if (result.data.length === 0) {
            return res.status(404).json({
                success: false,
                message: 'Terminal not found'
            });
        }
        
        // Get assigned vehicles for this terminal
        const vehiclesQuery = `
            SELECT 
                ta.*,
                v.plate_number,
                v.vehicle_type,
                CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                DATE_FORMAT(ta.assigned_date, '%Y-%m-%d %H:%i:%s') as assignment_date
            FROM terminal_assignments ta
            JOIN vehicles v ON ta.vehicle_id = v.id
            JOIN operators o ON v.operator_id = o.id
            WHERE ta.terminal_id = ? AND ta.status = 'active'
            ORDER BY ta.assigned_date DESC
        `;
        
        const vehiclesResult = await executeQuery(vehiclesQuery, [id]);
        
        res.json({
            success: true,
            data: {
                ...result.data[0],
                assigned_vehicles_list: vehiclesResult.success ? vehiclesResult.data : []
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

// POST /api/parking-terminal/terminals - Create new terminal
router.post('/terminals', [
    body('terminal_name').notEmpty().withMessage('Terminal name is required'),
    body('location').notEmpty().withMessage('Location is required'),
    body('address').notEmpty().withMessage('Address is required'),
    body('capacity').isInt({ min: 1 }).withMessage('Capacity must be a positive integer')
], handleValidationErrors, async (req, res) => {
    try {
        const {
            terminal_name,
            location,
            address,
            capacity,
            operating_hours,
            contact_person,
            contact_number,
            facilities
        } = req.body;
        
        const query = `
            INSERT INTO terminals (
                terminal_name, location, address, capacity,
                operating_hours, contact_person, contact_number, facilities,
                status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
        `;
        
        const result = await executeQuery(query, [
            terminal_name, location, address, capacity,
            operating_hours, contact_person, contact_number, facilities
        ]);
        
        if (!result.success) {
            return res.status(500).json({
                success: false,
                message: 'Failed to create terminal',
                error: result.error
            });
        }
        
        res.status(201).json({
            success: true,
            message: 'Terminal created successfully',
            data: {
                id: result.data.insertId,
                terminal_name,
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

// GET /api/parking-terminal/assignments - Get terminal assignments
router.get('/assignments', async (req, res) => {
    try {
        const { page = 1, limit = 10, terminal_id, status } = req.query;
        const offset = (page - 1) * limit;
        
        let query = `
            SELECT 
                ta.*,
                t.terminal_name,
                t.location as terminal_location,
                v.plate_number,
                v.vehicle_type,
                CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                DATE_FORMAT(ta.assigned_date, '%Y-%m-%d %H:%i:%s') as assignment_date
            FROM terminal_assignments ta
            JOIN terminals t ON ta.terminal_id = t.id
            JOIN vehicles v ON ta.vehicle_id = v.id
            JOIN operators o ON v.operator_id = o.id
            WHERE 1=1
        `;
        const params = [];
        
        if (terminal_id) {
            query += ' AND ta.terminal_id = ?';
            params.push(terminal_id);
        }
        
        if (status) {
            query += ' AND ta.status = ?';
            params.push(status);
        }
        
        query += ' ORDER BY ta.assigned_date DESC LIMIT ? OFFSET ?';
        params.push(parseInt(limit), parseInt(offset));
        
        const result = await executeQuery(query, params);
        
        if (!result.success) {
            return res.status(500).json({
                success: false,
                message: 'Failed to fetch terminal assignments',
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

// POST /api/parking-terminal/assignments - Create terminal assignment
router.post('/assignments', [
    body('terminal_id').isInt().withMessage('Valid terminal ID is required'),
    body('vehicle_id').isInt().withMessage('Valid vehicle ID is required'),
    body('assigned_date').isISO8601().withMessage('Valid assignment date is required')
], handleValidationErrors, async (req, res) => {
    try {
        const { terminal_id, vehicle_id, assigned_date, shift_schedule, remarks } = req.body;
        
        // Check if vehicle is already assigned to another terminal
        const checkQuery = `
            SELECT ta.id, t.terminal_name 
            FROM terminal_assignments ta
            JOIN terminals t ON ta.terminal_id = t.id
            WHERE ta.vehicle_id = ? AND ta.status = 'active'
        `;
        
        const checkResult = await executeQuery(checkQuery, [vehicle_id]);
        
        if (checkResult.success && checkResult.data.length > 0) {
            return res.status(400).json({
                success: false,
                message: `Vehicle is already assigned to ${checkResult.data[0].terminal_name}`
            });
        }
        
        // Check terminal capacity
        const capacityQuery = `
            SELECT 
                t.capacity,
                COUNT(ta.id) as current_assignments
            FROM terminals t
            LEFT JOIN terminal_assignments ta ON t.id = ta.terminal_id AND ta.status = 'active'
            WHERE t.id = ?
            GROUP BY t.id, t.capacity
        `;
        
        const capacityResult = await executeQuery(capacityQuery, [terminal_id]);
        
        if (capacityResult.success && capacityResult.data.length > 0) {
            const { capacity, current_assignments } = capacityResult.data[0];
            if (current_assignments >= capacity) {
                return res.status(400).json({
                    success: false,
                    message: 'Terminal has reached maximum capacity'
                });
            }
        }
        
        const query = `
            INSERT INTO terminal_assignments (
                terminal_id, vehicle_id, assigned_date, shift_schedule, remarks,
                status, created_at
            ) VALUES (?, ?, ?, ?, ?, 'active', NOW())
        `;
        
        const result = await executeQuery(query, [
            terminal_id, vehicle_id, assigned_date, shift_schedule, remarks
        ]);
        
        if (!result.success) {
            return res.status(500).json({
                success: false,
                message: 'Failed to create terminal assignment',
                error: result.error
            });
        }
        
        res.status(201).json({
            success: true,
            message: 'Terminal assignment created successfully',
            data: {
                id: result.data.insertId,
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

// GET /api/parking-terminal/statistics - Get parking/terminal statistics
router.get('/statistics', async (req, res) => {
    try {
        const queries = {
            totalTerminals: 'SELECT COUNT(*) as count FROM terminals',
            activeTerminals: 'SELECT COUNT(*) as count FROM terminals WHERE status = "active"',
            totalAssignments: 'SELECT COUNT(*) as count FROM terminal_assignments WHERE status = "active"',
            totalCapacity: 'SELECT SUM(capacity) as count FROM terminals WHERE status = "active"',
            utilizationRate: `
                SELECT 
                    ROUND(
                        (COUNT(ta.id) * 100.0 / NULLIF(SUM(t.capacity), 0)), 2
                    ) as count
                FROM terminals t
                LEFT JOIN terminal_assignments ta ON t.id = ta.terminal_id AND ta.status = 'active'
                WHERE t.status = 'active'
            `
        };
        
        const results = {};
        
        for (const [key, query] of Object.entries(queries)) {
            const result = await executeQuery(query);
            results[key] = result.success ? (result.data[0].count || 0) : 0;
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