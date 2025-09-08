const express = require('express');
const router = express.Router();
const db = require('../config/database');
const { authenticateToken, optionalAuth, authorize } = require('../middleware/auth');
const { validatePagination, validateId, validateOperator, validateVehicle, validateDateRange } = require('../middleware/validation');

// Base route for PUV Database module
router.get('/', (req, res) => {
    res.json({
        module: 'PUV Database',
        description: 'Public Utility Vehicle Database Management',
        endpoints: {
            operators: {
                'GET /operators': 'Get all operators with pagination and search',
                'POST /operators': 'Create new operator',
                'GET /operators/:id': 'Get operator by ID',
                'PUT /operators/:id': 'Update operator',
                'DELETE /operators/:id': 'Delete operator'
            },
            vehicles: {
                'GET /vehicles': 'Get all vehicles with pagination and search',
                'POST /vehicles': 'Register new vehicle',
                'GET /vehicles/:id': 'Get vehicle by ID',
                'PUT /vehicles/:id': 'Update vehicle',
                'DELETE /vehicles/:id': 'Delete vehicle'
            },
            compliance: {
                'GET /compliance/summary': 'Get compliance summary',
                'GET /compliance/expired-licenses': 'Get operators with expired licenses'
            }
        }
    });
});

// Operators endpoints

// GET /api/v1/puv-database/operators - Get all operators with pagination
router.get('/operators', validatePagination, async (req, res) => {
    try {
        const { page = 1, limit = 10, sort = 'created_at', order = 'desc', search } = req.query;
        const offset = (page - 1) * limit;
        
        let whereClause = '';
        let queryParams = [];
        
        if (search) {
            whereClause = 'WHERE first_name LIKE ? OR last_name LIKE ? OR license_number LIKE ?';
            const searchTerm = `%${search}%`;
            queryParams = [searchTerm, searchTerm, searchTerm];
        }
        
        const countQuery = `SELECT COUNT(*) as total FROM operators ${whereClause}`;
        const dataQuery = `
            SELECT id, first_name, last_name, middle_name, date_of_birth, 
                   contact_number, email, address, license_number, license_expiry, 
                   status, created_at, updated_at
            FROM operators 
            ${whereClause}
            ORDER BY ${sort} ${order}
            LIMIT ? OFFSET ?
        `;
        
        const [countResult] = await db.query(countQuery, queryParams);
        const total = countResult[0].total;
        
        const operators = await db.query(dataQuery, [...queryParams, parseInt(limit), parseInt(offset)]);
        
        res.json({
            success: true,
            data: operators,
            pagination: {
                page: parseInt(page),
                limit: parseInt(limit),
                total,
                pages: Math.ceil(total / limit)
            }
        });
    } catch (error) {
        console.error('Error fetching operators:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch operators',
            code: 'FETCH_ERROR'
        });
    }
});

// GET /api/v1/puv-database/operators/:id - Get operator by ID
router.get('/operators/:id', validateId, async (req, res) => {
    try {
        const { id } = req.params;
        
        const query = `
            SELECT id, first_name, last_name, middle_name, date_of_birth, 
                   contact_number, email, address, license_number, license_expiry, 
                   status, created_at, updated_at
            FROM operators 
            WHERE id = ?
        `;
        
        const operators = await db.query(query, [id]);
        
        if (operators.length === 0) {
            return res.status(404).json({
                success: false,
                error: 'Operator not found',
                code: 'NOT_FOUND'
            });
        }
        
        res.json({
            success: true,
            data: operators[0]
        });
    } catch (error) {
        console.error('Error fetching operator:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch operator',
            code: 'FETCH_ERROR'
        });
    }
});

// POST /api/v1/puv-database/operators - Create new operator
router.post('/operators', authenticateToken, authorize(['admin', 'staff']), validateOperator, async (req, res) => {
    try {
        const {
            first_name, last_name, middle_name, date_of_birth,
            contact_number, email, address, license_number, license_expiry, status
        } = req.body;
        
        const query = `
            INSERT INTO operators (
                first_name, last_name, middle_name, date_of_birth,
                contact_number, email, address, license_number, license_expiry, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        `;
        
        const result = await db.query(query, [
            first_name, last_name, middle_name || null, date_of_birth,
            contact_number, email || null, address, license_number, license_expiry, status || 'active'
        ]);
        
        res.status(201).json({
            success: true,
            message: 'Operator created successfully',
            data: {
                id: result.insertId,
                first_name, last_name, middle_name, date_of_birth,
                contact_number, email, address, license_number, license_expiry, status: status || 'active'
            }
        });
    } catch (error) {
        console.error('Error creating operator:', error);
        if (error.code === 'ER_DUP_ENTRY') {
            return res.status(409).json({
                success: false,
                error: 'License number already exists',
                code: 'DUPLICATE_LICENSE'
            });
        }
        res.status(500).json({
            success: false,
            error: 'Failed to create operator',
            code: 'CREATE_ERROR'
        });
    }
});

// PUT /api/v1/puv-database/operators/:id - Update operator
router.put('/operators/:id', authenticateToken, authorize(['admin', 'staff']), validateId, validateOperator, async (req, res) => {
    try {
        const { id } = req.params;
        const {
            first_name, last_name, middle_name, date_of_birth,
            contact_number, email, address, license_number, license_expiry, status
        } = req.body;
        
        const query = `
            UPDATE operators SET
                first_name = ?, last_name = ?, middle_name = ?, date_of_birth = ?,
                contact_number = ?, email = ?, address = ?, license_number = ?, 
                license_expiry = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        `;
        
        const result = await db.query(query, [
            first_name, last_name, middle_name || null, date_of_birth,
            contact_number, email || null, address, license_number, license_expiry, status,
            id
        ]);
        
        if (result.affectedRows === 0) {
            return res.status(404).json({
                success: false,
                error: 'Operator not found',
                code: 'NOT_FOUND'
            });
        }
        
        res.json({
            success: true,
            message: 'Operator updated successfully'
        });
    } catch (error) {
        console.error('Error updating operator:', error);
        if (error.code === 'ER_DUP_ENTRY') {
            return res.status(409).json({
                success: false,
                error: 'License number already exists',
                code: 'DUPLICATE_LICENSE'
            });
        }
        res.status(500).json({
            success: false,
            error: 'Failed to update operator',
            code: 'UPDATE_ERROR'
        });
    }
});

// DELETE /api/v1/puv-database/operators/:id - Delete operator
router.delete('/operators/:id', authenticateToken, authorize(['admin']), validateId, async (req, res) => {
    try {
        const { id } = req.params;
        
        const query = 'DELETE FROM operators WHERE id = ?';
        const result = await db.query(query, [id]);
        
        if (result.affectedRows === 0) {
            return res.status(404).json({
                success: false,
                error: 'Operator not found',
                code: 'NOT_FOUND'
            });
        }
        
        res.json({
            success: true,
            message: 'Operator deleted successfully'
        });
    } catch (error) {
        console.error('Error deleting operator:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to delete operator',
            code: 'DELETE_ERROR'
        });
    }
});

// Vehicles endpoints

// GET /api/v1/puv-database/vehicles - Get all vehicles with pagination
router.get('/vehicles', validatePagination, async (req, res) => {
    try {
        const { page = 1, limit = 10, sort = 'created_at', order = 'desc', search, operator_id } = req.query;
        const offset = (page - 1) * limit;
        
        let whereClause = '';
        let queryParams = [];
        
        const conditions = [];
        
        if (search) {
            conditions.push('(v.plate_number LIKE ? OR v.make LIKE ? OR v.model LIKE ?)');
            const searchTerm = `%${search}%`;
            queryParams.push(searchTerm, searchTerm, searchTerm);
        }
        
        if (operator_id) {
            conditions.push('v.operator_id = ?');
            queryParams.push(operator_id);
        }
        
        if (conditions.length > 0) {
            whereClause = 'WHERE ' + conditions.join(' AND ');
        }
        
        const countQuery = `
            SELECT COUNT(*) as total 
            FROM vehicles v 
            LEFT JOIN operators o ON v.operator_id = o.id 
            ${whereClause}
        `;
        
        const dataQuery = `
            SELECT v.id, v.plate_number, v.vehicle_type, v.make, v.model, v.year, 
                   v.color, v.engine_number, v.chassis_number, v.seating_capacity, 
                   v.operator_id, v.status, v.created_at, v.updated_at,
                   CONCAT(o.first_name, ' ', o.last_name) as operator_name
            FROM vehicles v 
            LEFT JOIN operators o ON v.operator_id = o.id 
            ${whereClause}
            ORDER BY v.${sort} ${order}
            LIMIT ? OFFSET ?
        `;
        
        const [countResult] = await db.query(countQuery, queryParams);
        const total = countResult[0].total;
        
        const vehicles = await db.query(dataQuery, [...queryParams, parseInt(limit), parseInt(offset)]);
        
        res.json({
            success: true,
            data: vehicles,
            pagination: {
                page: parseInt(page),
                limit: parseInt(limit),
                total,
                pages: Math.ceil(total / limit)
            }
        });
    } catch (error) {
        console.error('Error fetching vehicles:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch vehicles',
            code: 'FETCH_ERROR'
        });
    }
});

// GET /api/v1/puv-database/vehicles/:id - Get vehicle by ID
router.get('/vehicles/:id', validateId, async (req, res) => {
    try {
        const { id } = req.params;
        
        const query = `
            SELECT v.id, v.plate_number, v.vehicle_type, v.make, v.model, v.year, 
                   v.color, v.engine_number, v.chassis_number, v.seating_capacity, 
                   v.operator_id, v.status, v.created_at, v.updated_at,
                   CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                   o.contact_number as operator_contact
            FROM vehicles v 
            LEFT JOIN operators o ON v.operator_id = o.id 
            WHERE v.id = ?
        `;
        
        const vehicles = await db.query(query, [id]);
        
        if (vehicles.length === 0) {
            return res.status(404).json({
                success: false,
                error: 'Vehicle not found',
                code: 'NOT_FOUND'
            });
        }
        
        res.json({
            success: true,
            data: vehicles[0]
        });
    } catch (error) {
        console.error('Error fetching vehicle:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch vehicle',
            code: 'FETCH_ERROR'
        });
    }
});

// POST /api/v1/puv-database/vehicles - Create new vehicle
router.post('/vehicles', authenticateToken, authorize(['admin', 'staff']), validateVehicle, async (req, res) => {
    try {
        const {
            plate_number, vehicle_type, make, model, year, color,
            engine_number, chassis_number, seating_capacity, operator_id, status
        } = req.body;
        
        const query = `
            INSERT INTO vehicles (
                plate_number, vehicle_type, make, model, year, color,
                engine_number, chassis_number, seating_capacity, operator_id, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        `;
        
        const result = await db.query(query, [
            plate_number, vehicle_type, make, model, year, color,
            engine_number, chassis_number, seating_capacity, operator_id, status || 'active'
        ]);
        
        res.status(201).json({
            success: true,
            message: 'Vehicle created successfully',
            data: {
                id: result.insertId,
                plate_number, vehicle_type, make, model, year, color,
                engine_number, chassis_number, seating_capacity, operator_id, status: status || 'active'
            }
        });
    } catch (error) {
        console.error('Error creating vehicle:', error);
        if (error.code === 'ER_DUP_ENTRY') {
            return res.status(409).json({
                success: false,
                error: 'Plate number already exists',
                code: 'DUPLICATE_PLATE'
            });
        }
        res.status(500).json({
            success: false,
            error: 'Failed to create vehicle',
            code: 'CREATE_ERROR'
        });
    }
});

// Compliance endpoints

// GET /api/v1/puv-database/compliance/summary - Get compliance summary
router.get('/compliance/summary', optionalAuth, async (req, res) => {
    try {
        const summaryQuery = `
            SELECT 
                COUNT(CASE WHEN o.status = 'active' THEN 1 END) as active_operators,
                COUNT(CASE WHEN o.status = 'inactive' THEN 1 END) as inactive_operators,
                COUNT(CASE WHEN o.status = 'suspended' THEN 1 END) as suspended_operators,
                COUNT(CASE WHEN o.license_expiry < CURDATE() THEN 1 END) as expired_licenses,
                COUNT(CASE WHEN o.license_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as expiring_soon
            FROM operators o
        `;
        
        const vehicleSummaryQuery = `
            SELECT 
                COUNT(CASE WHEN v.status = 'active' THEN 1 END) as active_vehicles,
                COUNT(CASE WHEN v.status = 'inactive' THEN 1 END) as inactive_vehicles,
                COUNT(CASE WHEN v.status = 'maintenance' THEN 1 END) as maintenance_vehicles,
                COUNT(CASE WHEN v.status = 'retired' THEN 1 END) as retired_vehicles
            FROM vehicles v
        `;
        
        const [operatorSummary] = await db.query(summaryQuery);
        const [vehicleSummary] = await db.query(vehicleSummaryQuery);
        
        res.json({
            success: true,
            data: {
                operators: operatorSummary[0],
                vehicles: vehicleSummary[0]
            }
        });
    } catch (error) {
        console.error('Error fetching compliance summary:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch compliance summary',
            code: 'FETCH_ERROR'
        });
    }
});

// GET /api/v1/puv-database/compliance/expired-licenses - Get operators with expired licenses
router.get('/compliance/expired-licenses', validatePagination, async (req, res) => {
    try {
        const { page = 1, limit = 10 } = req.query;
        const offset = (page - 1) * limit;
        
        const countQuery = `
            SELECT COUNT(*) as total 
            FROM operators 
            WHERE license_expiry < CURDATE()
        `;
        
        const dataQuery = `
            SELECT id, first_name, last_name, license_number, license_expiry, 
                   contact_number, status, 
                   DATEDIFF(CURDATE(), license_expiry) as days_expired
            FROM operators 
            WHERE license_expiry < CURDATE()
            ORDER BY license_expiry ASC
            LIMIT ? OFFSET ?
        `;
        
        const [countResult] = await db.query(countQuery);
        const total = countResult[0].total;
        
        const expiredLicenses = await db.query(dataQuery, [parseInt(limit), parseInt(offset)]);
        
        res.json({
            success: true,
            data: expiredLicenses,
            pagination: {
                page: parseInt(page),
                limit: parseInt(limit),
                total,
                pages: Math.ceil(total / limit)
            }
        });
    } catch (error) {
        console.error('Error fetching expired licenses:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch expired licenses',
            code: 'FETCH_ERROR'
        });
    }
});

module.exports = router;