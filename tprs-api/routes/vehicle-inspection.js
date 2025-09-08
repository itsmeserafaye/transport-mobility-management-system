const express = require('express');
const router = express.Router();
const db = require('../config/database');
const { authenticateToken, optionalAuth, authorize } = require('../middleware/auth');
const { validatePagination, validateId, validateInspection, validateDateRange } = require('../middleware/validation');

// Inspection endpoints

// GET /api/v1/vehicle-inspection/inspections - Get all inspections with pagination
router.get('/inspections', validatePagination, async (req, res) => {
    try {
        const { page = 1, limit = 10, sort = 'inspection_date', order = 'desc', search, result, vehicle_id, start_date, end_date } = req.query;
        const offset = (page - 1) * limit;
        
        let whereClause = '';
        let queryParams = [];
        
        const conditions = [];
        
        if (search) {
            conditions.push('(i.certificate_number LIKE ? OR i.inspector_name LIKE ? OR vh.plate_number LIKE ? OR CONCAT(o.first_name, " ", o.last_name) LIKE ?)');
            const searchTerm = `%${search}%`;
            queryParams.push(searchTerm, searchTerm, searchTerm, searchTerm);
        }
        
        if (result) {
            conditions.push('i.result = ?');
            queryParams.push(result);
        }
        
        if (vehicle_id) {
            conditions.push('i.vehicle_id = ?');
            queryParams.push(vehicle_id);
        }
        
        if (start_date) {
            conditions.push('i.inspection_date >= ?');
            queryParams.push(start_date);
        }
        
        if (end_date) {
            conditions.push('i.inspection_date <= ?');
            queryParams.push(end_date);
        }
        
        if (conditions.length > 0) {
            whereClause = 'WHERE ' + conditions.join(' AND ');
        }
        
        const countQuery = `
            SELECT COUNT(*) as total 
            FROM inspections i 
            LEFT JOIN vehicles vh ON i.vehicle_id = vh.id 
            LEFT JOIN operators o ON vh.operator_id = o.id 
            ${whereClause}
        `;
        
        const dataQuery = `
            SELECT i.id, i.vehicle_id, i.inspection_date, i.inspector_name, 
                   i.inspection_type, i.result, i.notes, i.next_inspection_date, 
                   i.certificate_number, i.created_at, i.updated_at,
                   vh.plate_number as vehicle_plate,
                   vh.make as vehicle_make,
                   vh.model as vehicle_model,
                   vh.year as vehicle_year,
                   CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                   o.contact_number as operator_contact
            FROM inspections i 
            LEFT JOIN vehicles vh ON i.vehicle_id = vh.id 
            LEFT JOIN operators o ON vh.operator_id = o.id 
            ${whereClause}
            ORDER BY i.${sort} ${order}
            LIMIT ? OFFSET ?
        `;
        
        const [countResult] = await db.query(countQuery, queryParams);
        const total = countResult[0].total;
        
        const inspections = await db.query(dataQuery, [...queryParams, parseInt(limit), parseInt(offset)]);
        
        res.json({
            success: true,
            data: inspections,
            pagination: {
                page: parseInt(page),
                limit: parseInt(limit),
                total,
                pages: Math.ceil(total / limit)
            }
        });
    } catch (error) {
        console.error('Error fetching inspections:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch inspections',
            code: 'FETCH_ERROR'
        });
    }
});

// GET /api/v1/vehicle-inspection/inspections/:id - Get inspection by ID
router.get('/inspections/:id', validateId, async (req, res) => {
    try {
        const { id } = req.params;
        
        const query = `
            SELECT i.id, i.vehicle_id, i.inspection_date, i.inspector_name, 
                   i.inspection_type, i.result, i.notes, i.next_inspection_date, 
                   i.certificate_number, i.created_at, i.updated_at,
                   vh.plate_number as vehicle_plate,
                   vh.make as vehicle_make,
                   vh.model as vehicle_model,
                   vh.year as vehicle_year,
                   vh.vehicle_type,
                   vh.color as vehicle_color,
                   vh.engine_number,
                   vh.chassis_number,
                   vh.seating_capacity,
                   CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                   o.contact_number as operator_contact,
                   o.email as operator_email,
                   o.address as operator_address
            FROM inspections i 
            LEFT JOIN vehicles vh ON i.vehicle_id = vh.id 
            LEFT JOIN operators o ON vh.operator_id = o.id 
            WHERE i.id = ?
        `;
        
        const inspections = await db.query(query, [id]);
        
        if (inspections.length === 0) {
            return res.status(404).json({
                success: false,
                error: 'Inspection not found',
                code: 'NOT_FOUND'
            });
        }
        
        res.json({
            success: true,
            data: inspections[0]
        });
    } catch (error) {
        console.error('Error fetching inspection:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch inspection',
            code: 'FETCH_ERROR'
        });
    }
});

// POST /api/v1/vehicle-inspection/inspections - Create new inspection
router.post('/inspections', authenticateToken, authorize(['admin', 'staff', 'inspector']), validateInspection, async (req, res) => {
    try {
        const {
            vehicle_id, inspection_date, inspector_name, inspection_type, 
            result, notes, next_inspection_date, certificate_number
        } = req.body;
        
        // Check if vehicle exists
        const vehicleCheck = await db.query('SELECT id FROM vehicles WHERE id = ?', [vehicle_id]);
        if (vehicleCheck.length === 0) {
            return res.status(400).json({
                success: false,
                error: 'Vehicle not found',
                code: 'VEHICLE_NOT_FOUND'
            });
        }
        
        const query = `
            INSERT INTO inspections (
                vehicle_id, inspection_date, inspector_name, inspection_type, 
                result, notes, next_inspection_date, certificate_number
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        `;
        
        const insertResult = await db.query(query, [
            vehicle_id, inspection_date, inspector_name, inspection_type, 
            result, notes || null, next_inspection_date || null, certificate_number || null
        ]);
        
        // Update vehicle status based on inspection result
        let vehicleStatus = 'active';
        if (result === 'failed') {
            vehicleStatus = 'inactive';
        } else if (result === 'conditional') {
            vehicleStatus = 'maintenance';
        }
        
        await db.query('UPDATE vehicles SET status = ? WHERE id = ?', [vehicleStatus, vehicle_id]);
        
        res.status(201).json({
            success: true,
            message: 'Inspection created successfully',
            data: {
                id: insertResult.insertId,
                vehicle_id, inspection_date, inspector_name, inspection_type, 
                result, notes, next_inspection_date, certificate_number
            }
        });
    } catch (error) {
        console.error('Error creating inspection:', error);
        if (error.code === 'ER_DUP_ENTRY') {
            return res.status(409).json({
                success: false,
                error: 'Certificate number already exists',
                code: 'DUPLICATE_CERTIFICATE'
            });
        }
        res.status(500).json({
            success: false,
            error: 'Failed to create inspection',
            code: 'CREATE_ERROR'
        });
    }
});

// PUT /api/v1/vehicle-inspection/inspections/:id - Update inspection
router.put('/inspections/:id', authenticateToken, authorize(['admin', 'staff']), validateId, validateInspection, async (req, res) => {
    try {
        const { id } = req.params;
        const {
            vehicle_id, inspection_date, inspector_name, inspection_type, 
            result, notes, next_inspection_date, certificate_number
        } = req.body;
        
        // Check if vehicle exists
        const vehicleCheck = await db.query('SELECT id FROM vehicles WHERE id = ?', [vehicle_id]);
        if (vehicleCheck.length === 0) {
            return res.status(400).json({
                success: false,
                error: 'Vehicle not found',
                code: 'VEHICLE_NOT_FOUND'
            });
        }
        
        const query = `
            UPDATE inspections SET
                vehicle_id = ?, inspection_date = ?, inspector_name = ?, inspection_type = ?, 
                result = ?, notes = ?, next_inspection_date = ?, certificate_number = ?, 
                updated_at = NOW()
            WHERE id = ?
        `;
        
        const updateResult = await db.query(query, [
            vehicle_id, inspection_date, inspector_name, inspection_type, 
            result, notes || null, next_inspection_date || null, certificate_number || null, id
        ]);
        
        if (updateResult.affectedRows === 0) {
            return res.status(404).json({
                success: false,
                error: 'Inspection not found',
                code: 'NOT_FOUND'
            });
        }
        
        // Update vehicle status based on inspection result
        let vehicleStatus = 'active';
        if (result === 'failed') {
            vehicleStatus = 'inactive';
        } else if (result === 'conditional') {
            vehicleStatus = 'maintenance';
        }
        
        await db.query('UPDATE vehicles SET status = ? WHERE id = ?', [vehicleStatus, vehicle_id]);
        
        res.json({
            success: true,
            message: 'Inspection updated successfully'
        });
    } catch (error) {
        console.error('Error updating inspection:', error);
        if (error.code === 'ER_DUP_ENTRY') {
            return res.status(409).json({
                success: false,
                error: 'Certificate number already exists',
                code: 'DUPLICATE_CERTIFICATE'
            });
        }
        res.status(500).json({
            success: false,
            error: 'Failed to update inspection',
            code: 'UPDATE_ERROR'
        });
    }
});

// DELETE /api/v1/vehicle-inspection/inspections/:id - Delete inspection
router.delete('/inspections/:id', authenticateToken, authorize(['admin']), validateId, async (req, res) => {
    try {
        const { id } = req.params;
        
        const query = 'DELETE FROM inspections WHERE id = ?';
        const result = await db.query(query, [id]);
        
        if (result.affectedRows === 0) {
            return res.status(404).json({
                success: false,
                error: 'Inspection not found',
                code: 'NOT_FOUND'
            });
        }
        
        res.json({
            success: true,
            message: 'Inspection deleted successfully'
        });
    } catch (error) {
        console.error('Error deleting inspection:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to delete inspection',
            code: 'DELETE_ERROR'
        });
    }
});

// Registration endpoints

// GET /api/v1/vehicle-inspection/registrations/due - Get vehicles due for inspection
router.get('/registrations/due', validatePagination, async (req, res) => {
    try {
        const { page = 1, limit = 10, days = 30 } = req.query;
        const offset = (page - 1) * limit;
        
        const countQuery = `
            SELECT COUNT(*) as total 
            FROM vehicles v
            LEFT JOIN operators o ON v.operator_id = o.id
            LEFT JOIN (
                SELECT vehicle_id, MAX(inspection_date) as last_inspection_date,
                       next_inspection_date
                FROM inspections 
                WHERE result = 'passed'
                GROUP BY vehicle_id
            ) li ON v.id = li.vehicle_id
            WHERE (
                li.next_inspection_date IS NULL OR 
                li.next_inspection_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
            ) AND v.status = 'active'
        `;
        
        const dataQuery = `
            SELECT v.id, v.plate_number, v.make, v.model, v.year, v.vehicle_type,
                   CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                   o.contact_number as operator_contact,
                   li.last_inspection_date,
                   li.next_inspection_date,
                   CASE 
                       WHEN li.next_inspection_date IS NULL THEN 'Never inspected'
                       WHEN li.next_inspection_date < CURDATE() THEN 'Overdue'
                       ELSE 'Due soon'
                   END as status,
                   CASE 
                       WHEN li.next_inspection_date IS NULL THEN NULL
                       ELSE DATEDIFF(li.next_inspection_date, CURDATE())
                   END as days_until_due
            FROM vehicles v
            LEFT JOIN operators o ON v.operator_id = o.id
            LEFT JOIN (
                SELECT vehicle_id, MAX(inspection_date) as last_inspection_date,
                       next_inspection_date
                FROM inspections 
                WHERE result = 'passed'
                GROUP BY vehicle_id
            ) li ON v.id = li.vehicle_id
            WHERE (
                li.next_inspection_date IS NULL OR 
                li.next_inspection_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
            ) AND v.status = 'active'
            ORDER BY li.next_inspection_date ASC
            LIMIT ? OFFSET ?
        `;
        
        const [countResult] = await db.query(countQuery, [days]);
        const total = countResult[0].total;
        
        const dueVehicles = await db.query(dataQuery, [days, parseInt(limit), parseInt(offset)]);
        
        res.json({
            success: true,
            data: dueVehicles,
            pagination: {
                page: parseInt(page),
                limit: parseInt(limit),
                total,
                pages: Math.ceil(total / limit)
            }
        });
    } catch (error) {
        console.error('Error fetching vehicles due for inspection:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch vehicles due for inspection',
            code: 'FETCH_ERROR'
        });
    }
});

// GET /api/v1/vehicle-inspection/registrations/summary - Get registration summary
router.get('/registrations/summary', optionalAuth, async (req, res) => {
    try {
        const summaryQuery = `
            SELECT 
                COUNT(DISTINCT v.id) as total_vehicles,
                COUNT(DISTINCT CASE WHEN v.status = 'active' THEN v.id END) as active_vehicles,
                COUNT(DISTINCT CASE WHEN v.status = 'inactive' THEN v.id END) as inactive_vehicles,
                COUNT(DISTINCT CASE WHEN v.status = 'maintenance' THEN v.id END) as maintenance_vehicles,
                COUNT(DISTINCT CASE WHEN i.result = 'passed' THEN v.id END) as passed_inspections,
                COUNT(DISTINCT CASE WHEN i.result = 'failed' THEN v.id END) as failed_inspections,
                COUNT(DISTINCT CASE WHEN i.result = 'conditional' THEN v.id END) as conditional_inspections
            FROM vehicles v
            LEFT JOIN inspections i ON v.id = i.vehicle_id
        `;
        
        const overdueQuery = `
            SELECT COUNT(*) as overdue_inspections
            FROM vehicles v
            LEFT JOIN (
                SELECT vehicle_id, next_inspection_date
                FROM inspections 
                WHERE result = 'passed'
                GROUP BY vehicle_id
                HAVING MAX(inspection_date)
            ) li ON v.id = li.vehicle_id
            WHERE li.next_inspection_date < CURDATE() AND v.status = 'active'
        `;
        
        const dueSoonQuery = `
            SELECT COUNT(*) as due_soon_inspections
            FROM vehicles v
            LEFT JOIN (
                SELECT vehicle_id, next_inspection_date
                FROM inspections 
                WHERE result = 'passed'
                GROUP BY vehicle_id
                HAVING MAX(inspection_date)
            ) li ON v.id = li.vehicle_id
            WHERE li.next_inspection_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            AND v.status = 'active'
        `;
        
        const [summary] = await db.query(summaryQuery);
        const [overdue] = await db.query(overdueQuery);
        const [dueSoon] = await db.query(dueSoonQuery);
        
        res.json({
            success: true,
            data: {
                ...summary[0],
                overdue_inspections: overdue[0].overdue_inspections,
                due_soon_inspections: dueSoon[0].due_soon_inspections
            }
        });
    } catch (error) {
        console.error('Error fetching registration summary:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch registration summary',
            code: 'FETCH_ERROR'
        });
    }
});

// GET /api/v1/vehicle-inspection/registrations/certificates - Get inspection certificates
router.get('/registrations/certificates', validatePagination, async (req, res) => {
    try {
        const { page = 1, limit = 10, search, vehicle_id } = req.query;
        const offset = (page - 1) * limit;
        
        let whereClause = 'WHERE i.certificate_number IS NOT NULL AND i.certificate_number != ""';
        let queryParams = [];
        
        if (search) {
            whereClause += ' AND (i.certificate_number LIKE ? OR vh.plate_number LIKE ? OR CONCAT(o.first_name, " ", o.last_name) LIKE ?)';
            const searchTerm = `%${search}%`;
            queryParams.push(searchTerm, searchTerm, searchTerm);
        }
        
        if (vehicle_id) {
            whereClause += ' AND i.vehicle_id = ?';
            queryParams.push(vehicle_id);
        }
        
        const countQuery = `
            SELECT COUNT(*) as total 
            FROM inspections i 
            LEFT JOIN vehicles vh ON i.vehicle_id = vh.id 
            LEFT JOIN operators o ON vh.operator_id = o.id 
            ${whereClause}
        `;
        
        const dataQuery = `
            SELECT i.id, i.certificate_number, i.inspection_date, i.inspector_name, 
                   i.inspection_type, i.result, i.next_inspection_date,
                   vh.plate_number as vehicle_plate,
                   vh.make as vehicle_make,
                   vh.model as vehicle_model,
                   CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                   o.contact_number as operator_contact,
                   DATEDIFF(i.next_inspection_date, CURDATE()) as days_until_expiry
            FROM inspections i 
            LEFT JOIN vehicles vh ON i.vehicle_id = vh.id 
            LEFT JOIN operators o ON vh.operator_id = o.id 
            ${whereClause}
            ORDER BY i.inspection_date DESC
            LIMIT ? OFFSET ?
        `;
        
        const [countResult] = await db.query(countQuery, queryParams);
        const total = countResult[0].total;
        
        const certificates = await db.query(dataQuery, [...queryParams, parseInt(limit), parseInt(offset)]);
        
        res.json({
            success: true,
            data: certificates,
            pagination: {
                page: parseInt(page),
                limit: parseInt(limit),
                total,
                pages: Math.ceil(total / limit)
            }
        });
    } catch (error) {
        console.error('Error fetching inspection certificates:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch inspection certificates',
            code: 'FETCH_ERROR'
        });
    }
});

// GET /api/v1/vehicle-inspection/vehicles/:id/history - Get inspection history for a vehicle
router.get('/vehicles/:id/history', validateId, validatePagination, async (req, res) => {
    try {
        const { id } = req.params;
        const { page = 1, limit = 10 } = req.query;
        const offset = (page - 1) * limit;
        
        // Check if vehicle exists
        const vehicleCheck = await db.query('SELECT id, plate_number FROM vehicles WHERE id = ?', [id]);
        if (vehicleCheck.length === 0) {
            return res.status(404).json({
                success: false,
                error: 'Vehicle not found',
                code: 'VEHICLE_NOT_FOUND'
            });
        }
        
        const countQuery = `
            SELECT COUNT(*) as total 
            FROM inspections 
            WHERE vehicle_id = ?
        `;
        
        const dataQuery = `
            SELECT id, inspection_date, inspector_name, inspection_type, 
                   result, notes, next_inspection_date, certificate_number,
                   created_at, updated_at
            FROM inspections 
            WHERE vehicle_id = ?
            ORDER BY inspection_date DESC
            LIMIT ? OFFSET ?
        `;
        
        const [countResult] = await db.query(countQuery, [id]);
        const total = countResult[0].total;
        
        const history = await db.query(dataQuery, [id, parseInt(limit), parseInt(offset)]);
        
        res.json({
            success: true,
            data: {
                vehicle: vehicleCheck[0],
                inspections: history
            },
            pagination: {
                page: parseInt(page),
                limit: parseInt(limit),
                total,
                pages: Math.ceil(total / limit)
            }
        });
    } catch (error) {
        console.error('Error fetching vehicle inspection history:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch vehicle inspection history',
            code: 'FETCH_ERROR'
        });
    }
});

module.exports = router;