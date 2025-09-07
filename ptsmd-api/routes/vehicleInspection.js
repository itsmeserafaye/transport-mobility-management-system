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

// GET /api/vehicle-inspection/inspections - Get all inspections
router.get('/inspections', async (req, res) => {
    try {
        const { page = 1, limit = 10, status, vehicle_id, search, date_from, date_to } = req.query;
        const offset = (page - 1) * limit;
        
        let query = `
            SELECT 
                vi.*,
                v.plate_number,
                v.vehicle_type,
                CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                DATE_FORMAT(vi.inspection_date, '%Y-%m-%d') as formatted_inspection_date,
                DATE_FORMAT(vi.next_inspection_date, '%Y-%m-%d') as formatted_next_inspection_date,
                DATE_FORMAT(vi.created_at, '%Y-%m-%d %H:%i:%s') as formatted_created_date
            FROM vehicle_inspections vi
            JOIN vehicles v ON vi.vehicle_id = v.vehicle_id
            JOIN operators o ON v.operator_id = o.operator_id
            WHERE 1=1
        `;
        const params = [];
        
        if (status) {
            query += ' AND vi.status = ?';
            params.push(status);
        }
        
        if (vehicle_id) {
            query += ' AND vi.vehicle_id = ?';
            params.push(vehicle_id);
        }
        
        if (search) {
            query += ' AND (v.plate_number LIKE ? OR CONCAT(o.first_name, " ", o.last_name) LIKE ? OR vi.inspector_name LIKE ?)';
            params.push(`%${search}%`, `%${search}%`, `%${search}%`);
        }
        
        if (date_from) {
            query += ' AND vi.inspection_date >= ?';
            params.push(date_from);
        }
        
        if (date_to) {
            query += ' AND vi.inspection_date <= ?';
            params.push(date_to);
        }
        
        query += ' ORDER BY vi.inspection_date DESC LIMIT ? OFFSET ?';
        params.push(parseInt(limit), parseInt(offset));
        
        const result = await executeQuery(query, params);
        
        if (!result.success) {
            return res.status(500).json({
                success: false,
                message: 'Failed to fetch inspections',
                error: result.error
            });
        }
        
        // Get total count
        let countQuery = `
            SELECT COUNT(*) as total 
            FROM vehicle_inspections vi
            JOIN vehicles v ON vi.vehicle_id = v.id
            JOIN operators o ON v.operator_id = o.id
            WHERE 1=1
        `;
        const countParams = [];
        
        if (status) {
            countQuery += ' AND vi.status = ?';
            countParams.push(status);
        }
        
        if (vehicle_id) {
            countQuery += ' AND vi.vehicle_id = ?';
            countParams.push(vehicle_id);
        }
        
        if (search) {
            countQuery += ' AND (v.plate_number LIKE ? OR CONCAT(o.first_name, " ", o.last_name) LIKE ? OR vi.inspector_name LIKE ?)';
            countParams.push(`%${search}%`, `%${search}%`, `%${search}%`);
        }
        
        if (date_from) {
            countQuery += ' AND vi.inspection_date >= ?';
            countParams.push(date_from);
        }
        
        if (date_to) {
            countQuery += ' AND vi.inspection_date <= ?';
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

// GET /api/vehicle-inspection/inspections/:id - Get specific inspection
router.get('/inspections/:id', async (req, res) => {
    try {
        const { id } = req.params;
        
        const query = `
            SELECT 
                vi.*,
                v.plate_number,
                v.vehicle_type,
                v.model,
                v.year,
                CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                o.contact_number as operator_contact,
                DATE_FORMAT(vi.inspection_date, '%Y-%m-%d') as formatted_inspection_date,
                DATE_FORMAT(vi.next_inspection_date, '%Y-%m-%d') as formatted_next_inspection_date,
                DATE_FORMAT(vi.created_at, '%Y-%m-%d %H:%i:%s') as formatted_created_date
            FROM vehicle_inspections vi
            JOIN vehicles v ON vi.vehicle_id = v.id
            JOIN operators o ON v.operator_id = o.id
            WHERE vi.id = ?
        `;
        
        const result = await executeQuery(query, [id]);
        
        if (!result.success) {
            return res.status(500).json({
                success: false,
                message: 'Failed to fetch inspection',
                error: result.error
            });
        }
        
        if (result.data.length === 0) {
            return res.status(404).json({
                success: false,
                message: 'Inspection not found'
            });
        }
        
        // Get inspection items/checklist
        const itemsQuery = `
            SELECT * FROM inspection_items 
            WHERE inspection_id = ?
            ORDER BY item_category, item_name
        `;
        
        const itemsResult = await executeQuery(itemsQuery, [id]);
        
        res.json({
            success: true,
            data: {
                ...result.data[0],
                inspection_items: itemsResult.success ? itemsResult.data : []
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

// POST /api/vehicle-inspection/inspections - Create new inspection
router.post('/inspections', [
    body('vehicle_id').isInt().withMessage('Valid vehicle ID is required'),
    body('inspection_date').isISO8601().withMessage('Valid inspection date is required'),
    body('inspector_name').notEmpty().withMessage('Inspector name is required'),
    body('inspection_type').isIn(['regular', 'special', 'renewal']).withMessage('Valid inspection type is required')
], handleValidationErrors, async (req, res) => {
    try {
        const {
            vehicle_id,
            inspection_date,
            inspector_name,
            inspection_type,
            location,
            remarks,
            inspection_items = []
        } = req.body;
        
        // Calculate next inspection date (1 year from inspection date)
        const nextInspectionDate = new Date(inspection_date);
        nextInspectionDate.setFullYear(nextInspectionDate.getFullYear() + 1);
        
        const query = `
            INSERT INTO vehicle_inspections (
                vehicle_id, inspection_date, next_inspection_date, inspector_name,
                inspection_type, location, remarks, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'scheduled', NOW())
        `;
        
        const result = await executeQuery(query, [
            vehicle_id, inspection_date, nextInspectionDate.toISOString().split('T')[0],
            inspector_name, inspection_type, location, remarks
        ]);
        
        if (!result.success) {
            return res.status(500).json({
                success: false,
                message: 'Failed to create inspection',
                error: result.error
            });
        }
        
        const inspectionId = result.data.insertId;
        
        // Insert inspection items if provided
        if (inspection_items.length > 0) {
            const itemsQuery = `
                INSERT INTO inspection_items (
                    inspection_id, item_category, item_name, status, remarks
                ) VALUES ?
            `;
            
            const itemsData = inspection_items.map(item => [
                inspectionId,
                item.category,
                item.name,
                item.status || 'pending',
                item.remarks || null
            ]);
            
            await executeQuery(itemsQuery, [itemsData]);
        }
        
        res.status(201).json({
            success: true,
            message: 'Inspection scheduled successfully',
            data: {
                id: inspectionId,
                status: 'scheduled'
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

// PUT /api/vehicle-inspection/inspections/:id/status - Update inspection status
router.put('/inspections/:id/status', [
    body('status').isIn(['scheduled', 'in_progress', 'completed', 'failed', 'cancelled']).withMessage('Valid status is required')
], handleValidationErrors, async (req, res) => {
    try {
        const { id } = req.params;
        const { status, completion_date, overall_result, remarks } = req.body;
        
        let query = 'UPDATE vehicle_inspections SET status = ?, updated_at = NOW()';
        const params = [status];
        
        if (status === 'completed' && completion_date) {
            query += ', completion_date = ?';
            params.push(completion_date);
        }
        
        if (overall_result) {
            query += ', overall_result = ?';
            params.push(overall_result);
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
                message: 'Failed to update inspection status',
                error: result.error
            });
        }
        
        if (result.data.affectedRows === 0) {
            return res.status(404).json({
                success: false,
                message: 'Inspection not found'
            });
        }
        
        res.json({
            success: true,
            message: 'Inspection status updated successfully'
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: 'Internal server error',
            error: error.message
        });
    }
});

// GET /api/vehicle-inspection/schedule - Get inspection schedule
router.get('/schedule', async (req, res) => {
    try {
        const { date, month, year } = req.query;
        
        let query = `
            SELECT 
                vi.*,
                v.plate_number,
                v.vehicle_type,
                CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                DATE_FORMAT(vi.inspection_date, '%Y-%m-%d') as formatted_inspection_date,
                TIME_FORMAT(vi.inspection_time, '%H:%i') as formatted_inspection_time
            FROM vehicle_inspections vi
            JOIN vehicles v ON vi.vehicle_id = v.id
            JOIN operators o ON v.operator_id = o.id
            WHERE vi.status IN ('scheduled', 'in_progress')
        `;
        const params = [];
        
        if (date) {
            query += ' AND DATE(vi.inspection_date) = ?';
            params.push(date);
        } else if (month && year) {
            query += ' AND MONTH(vi.inspection_date) = ? AND YEAR(vi.inspection_date) = ?';
            params.push(month, year);
        } else if (year) {
            query += ' AND YEAR(vi.inspection_date) = ?';
            params.push(year);
        }
        
        query += ' ORDER BY vi.inspection_date ASC, vi.inspection_time ASC';
        
        const result = await executeQuery(query, params);
        
        if (!result.success) {
            return res.status(500).json({
                success: false,
                message: 'Failed to fetch inspection schedule',
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

// GET /api/vehicle-inspection/history/:vehicle_id - Get inspection history for a vehicle
router.get('/history/:vehicle_id', async (req, res) => {
    try {
        const { vehicle_id } = req.params;
        const { page = 1, limit = 10 } = req.query;
        const offset = (page - 1) * limit;
        
        const query = `
            SELECT 
                vi.*,
                DATE_FORMAT(vi.inspection_date, '%Y-%m-%d') as formatted_inspection_date,
                DATE_FORMAT(vi.completion_date, '%Y-%m-%d') as formatted_completion_date,
                DATE_FORMAT(vi.next_inspection_date, '%Y-%m-%d') as formatted_next_inspection_date
            FROM vehicle_inspections vi
            WHERE vi.vehicle_id = ?
            ORDER BY vi.inspection_date DESC
            LIMIT ? OFFSET ?
        `;
        
        const result = await executeQuery(query, [vehicle_id, parseInt(limit), parseInt(offset)]);
        
        if (!result.success) {
            return res.status(500).json({
                success: false,
                message: 'Failed to fetch inspection history',
                error: result.error
            });
        }
        
        // Get total count
        const countQuery = 'SELECT COUNT(*) as total FROM vehicle_inspections WHERE vehicle_id = ?';
        const countResult = await executeQuery(countQuery, [vehicle_id]);
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

// GET /api/vehicle-inspection/statistics - Get inspection statistics
router.get('/statistics', async (req, res) => {
    try {
        const queries = {
            totalInspections: 'SELECT COUNT(*) as count FROM vehicle_inspections',
            scheduledInspections: 'SELECT COUNT(*) as count FROM vehicle_inspections WHERE status = "scheduled"',
            completedInspections: 'SELECT COUNT(*) as count FROM vehicle_inspections WHERE status = "completed"',
            passedInspections: 'SELECT COUNT(*) as count FROM vehicle_inspections WHERE overall_result = "passed"',
            failedInspections: 'SELECT COUNT(*) as count FROM vehicle_inspections WHERE overall_result = "failed"',
            overdueInspections: `
                SELECT COUNT(*) as count 
                FROM vehicles v
                LEFT JOIN vehicle_inspections vi ON v.id = vi.vehicle_id 
                    AND vi.id = (SELECT MAX(id) FROM vehicle_inspections WHERE vehicle_id = v.id)
                WHERE vi.next_inspection_date < CURDATE() OR vi.next_inspection_date IS NULL
            `
        };
        
        const results = {};
        
        for (const [key, query] of Object.entries(queries)) {
            const result = await executeQuery(query);
            results[key] = result.success ? (result.data[0].count || 0) : 0;
        }
        
        // Calculate pass rate
        const totalCompleted = results.passedInspections + results.failedInspections;
        results.passRate = totalCompleted > 0 ? 
            Math.round((results.passedInspections / totalCompleted) * 100) : 0;
        
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