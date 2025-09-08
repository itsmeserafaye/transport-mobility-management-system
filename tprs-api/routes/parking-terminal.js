const express = require('express');
const router = express.Router();
const db = require('../config/database');
const { authenticateToken, optionalAuth, authorize } = require('../middleware/auth');
const { validatePagination, validateId, validateDateRange } = require('../middleware/validation');
const Joi = require('joi');

// Validation schemas
const parkingSpaceSchema = Joi.object({
    space_number: Joi.string().required(),
    location: Joi.string().required(),
    space_type: Joi.string().valid('regular', 'disabled', 'loading', 'reserved').required(),
    hourly_rate: Joi.number().positive().required(),
    daily_rate: Joi.number().positive().required(),
    status: Joi.string().valid('available', 'occupied', 'maintenance', 'reserved').default('available'),
    terminal_id: Joi.number().integer().positive().allow(null)
});

const terminalSchema = Joi.object({
    name: Joi.string().required(),
    location: Joi.string().required(),
    capacity: Joi.number().integer().positive().required(),
    operating_hours: Joi.string().required(),
    contact_number: Joi.string().allow(null, ''),
    manager_name: Joi.string().allow(null, ''),
    status: Joi.string().valid('active', 'inactive', 'maintenance').default('active'),
    facilities: Joi.string().allow(null, '')
});

const parkingSessionSchema = Joi.object({
    space_id: Joi.number().integer().positive().required(),
    vehicle_plate: Joi.string().required(),
    vehicle_type: Joi.string().required(),
    entry_time: Joi.date().iso().required(),
    exit_time: Joi.date().iso().allow(null),
    duration_hours: Joi.number().positive().allow(null),
    amount_due: Joi.number().positive().allow(null),
    amount_paid: Joi.number().positive().allow(null),
    payment_status: Joi.string().valid('pending', 'paid', 'overdue').default('pending'),
    payment_method: Joi.string().valid('cash', 'card', 'mobile', 'online').allow(null)
});

// Validation middleware
const validateParkingSpace = (req, res, next) => {
    const { error } = parkingSpaceSchema.validate(req.body);
    if (error) {
        return res.status(400).json({
            success: false,
            error: error.details[0].message,
            code: 'VALIDATION_ERROR'
        });
    }
    next();
};

const validateTerminal = (req, res, next) => {
    const { error } = terminalSchema.validate(req.body);
    if (error) {
        return res.status(400).json({
            success: false,
            error: error.details[0].message,
            code: 'VALIDATION_ERROR'
        });
    }
    next();
};

const validateParkingSession = (req, res, next) => {
    const { error } = parkingSessionSchema.validate(req.body);
    if (error) {
        return res.status(400).json({
            success: false,
            error: error.details[0].message,
            code: 'VALIDATION_ERROR'
        });
    }
    next();
};

// Parking Space endpoints

// GET /api/v1/parking-terminal/spaces - Get all parking spaces
router.get('/spaces', validatePagination, async (req, res) => {
    try {
        const { page = 1, limit = 10, sort = 'space_number', order = 'asc', search, status, space_type, terminal_id } = req.query;
        const offset = (page - 1) * limit;
        
        let whereClause = '';
        let queryParams = [];
        
        const conditions = [];
        
        if (search) {
            conditions.push('(ps.space_number LIKE ? OR ps.location LIKE ?)');
            const searchTerm = `%${search}%`;
            queryParams.push(searchTerm, searchTerm);
        }
        
        if (status) {
            conditions.push('ps.status = ?');
            queryParams.push(status);
        }
        
        if (space_type) {
            conditions.push('ps.space_type = ?');
            queryParams.push(space_type);
        }
        
        if (terminal_id) {
            conditions.push('ps.terminal_id = ?');
            queryParams.push(terminal_id);
        }
        
        if (conditions.length > 0) {
            whereClause = 'WHERE ' + conditions.join(' AND ');
        }
        
        const countQuery = `
            SELECT COUNT(*) as total 
            FROM parking_spaces ps 
            LEFT JOIN terminals t ON ps.terminal_id = t.id 
            ${whereClause}
        `;
        
        const dataQuery = `
            SELECT ps.id, ps.space_number, ps.location, ps.space_type, 
                   ps.hourly_rate, ps.daily_rate, ps.status, ps.terminal_id,
                   ps.created_at, ps.updated_at,
                   t.name as terminal_name,
                   t.location as terminal_location
            FROM parking_spaces ps 
            LEFT JOIN terminals t ON ps.terminal_id = t.id 
            ${whereClause}
            ORDER BY ps.${sort} ${order}
            LIMIT ? OFFSET ?
        `;
        
        const [countResult] = await db.query(countQuery, queryParams);
        const total = countResult[0].total;
        
        const spaces = await db.query(dataQuery, [...queryParams, parseInt(limit), parseInt(offset)]);
        
        res.json({
            success: true,
            data: spaces,
            pagination: {
                page: parseInt(page),
                limit: parseInt(limit),
                total,
                pages: Math.ceil(total / limit)
            }
        });
    } catch (error) {
        console.error('Error fetching parking spaces:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch parking spaces',
            code: 'FETCH_ERROR'
        });
    }
});

// GET /api/v1/parking-terminal/spaces/:id - Get parking space by ID
router.get('/spaces/:id', validateId, async (req, res) => {
    try {
        const { id } = req.params;
        
        const query = `
            SELECT ps.id, ps.space_number, ps.location, ps.space_type, 
                   ps.hourly_rate, ps.daily_rate, ps.status, ps.terminal_id,
                   ps.created_at, ps.updated_at,
                   t.name as terminal_name,
                   t.location as terminal_location,
                   t.operating_hours as terminal_hours
            FROM parking_spaces ps 
            LEFT JOIN terminals t ON ps.terminal_id = t.id 
            WHERE ps.id = ?
        `;
        
        const spaces = await db.query(query, [id]);
        
        if (spaces.length === 0) {
            return res.status(404).json({
                success: false,
                error: 'Parking space not found',
                code: 'NOT_FOUND'
            });
        }
        
        res.json({
            success: true,
            data: spaces[0]
        });
    } catch (error) {
        console.error('Error fetching parking space:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch parking space',
            code: 'FETCH_ERROR'
        });
    }
});

// POST /api/v1/parking-terminal/spaces - Create new parking space
router.post('/spaces', authenticateToken, authorize(['admin', 'staff']), validateParkingSpace, async (req, res) => {
    try {
        const {
            space_number, location, space_type, hourly_rate, daily_rate, 
            status = 'available', terminal_id
        } = req.body;
        
        // Check if terminal exists (if provided)
        if (terminal_id) {
            const terminalCheck = await db.query('SELECT id FROM terminals WHERE id = ?', [terminal_id]);
            if (terminalCheck.length === 0) {
                return res.status(400).json({
                    success: false,
                    error: 'Terminal not found',
                    code: 'TERMINAL_NOT_FOUND'
                });
            }
        }
        
        const query = `
            INSERT INTO parking_spaces (
                space_number, location, space_type, hourly_rate, daily_rate, 
                status, terminal_id
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        `;
        
        const insertResult = await db.query(query, [
            space_number, location, space_type, hourly_rate, daily_rate, 
            status, terminal_id || null
        ]);
        
        res.status(201).json({
            success: true,
            message: 'Parking space created successfully',
            data: {
                id: insertResult.insertId,
                space_number, location, space_type, hourly_rate, daily_rate, 
                status, terminal_id
            }
        });
    } catch (error) {
        console.error('Error creating parking space:', error);
        if (error.code === 'ER_DUP_ENTRY') {
            return res.status(409).json({
                success: false,
                error: 'Space number already exists',
                code: 'DUPLICATE_SPACE'
            });
        }
        res.status(500).json({
            success: false,
            error: 'Failed to create parking space',
            code: 'CREATE_ERROR'
        });
    }
});

// PUT /api/v1/parking-terminal/spaces/:id - Update parking space
router.put('/spaces/:id', authenticateToken, authorize(['admin', 'staff']), validateId, validateParkingSpace, async (req, res) => {
    try {
        const { id } = req.params;
        const {
            space_number, location, space_type, hourly_rate, daily_rate, 
            status, terminal_id
        } = req.body;
        
        // Check if terminal exists (if provided)
        if (terminal_id) {
            const terminalCheck = await db.query('SELECT id FROM terminals WHERE id = ?', [terminal_id]);
            if (terminalCheck.length === 0) {
                return res.status(400).json({
                    success: false,
                    error: 'Terminal not found',
                    code: 'TERMINAL_NOT_FOUND'
                });
            }
        }
        
        const query = `
            UPDATE parking_spaces SET
                space_number = ?, location = ?, space_type = ?, hourly_rate = ?, 
                daily_rate = ?, status = ?, terminal_id = ?, updated_at = NOW()
            WHERE id = ?
        `;
        
        const updateResult = await db.query(query, [
            space_number, location, space_type, hourly_rate, daily_rate, 
            status, terminal_id || null, id
        ]);
        
        if (updateResult.affectedRows === 0) {
            return res.status(404).json({
                success: false,
                error: 'Parking space not found',
                code: 'NOT_FOUND'
            });
        }
        
        res.json({
            success: true,
            message: 'Parking space updated successfully'
        });
    } catch (error) {
        console.error('Error updating parking space:', error);
        if (error.code === 'ER_DUP_ENTRY') {
            return res.status(409).json({
                success: false,
                error: 'Space number already exists',
                code: 'DUPLICATE_SPACE'
            });
        }
        res.status(500).json({
            success: false,
            error: 'Failed to update parking space',
            code: 'UPDATE_ERROR'
        });
    }
});

// DELETE /api/v1/parking-terminal/spaces/:id - Delete parking space
router.delete('/spaces/:id', authenticateToken, authorize(['admin']), validateId, async (req, res) => {
    try {
        const { id } = req.params;
        
        const query = 'DELETE FROM parking_spaces WHERE id = ?';
        const result = await db.query(query, [id]);
        
        if (result.affectedRows === 0) {
            return res.status(404).json({
                success: false,
                error: 'Parking space not found',
                code: 'NOT_FOUND'
            });
        }
        
        res.json({
            success: true,
            message: 'Parking space deleted successfully'
        });
    } catch (error) {
        console.error('Error deleting parking space:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to delete parking space',
            code: 'DELETE_ERROR'
        });
    }
});

// Terminal endpoints

// GET /api/v1/parking-terminal/terminals - Get all terminals
router.get('/terminals', validatePagination, async (req, res) => {
    try {
        const { page = 1, limit = 10, sort = 'name', order = 'asc', search, status } = req.query;
        const offset = (page - 1) * limit;
        
        let whereClause = '';
        let queryParams = [];
        
        const conditions = [];
        
        if (search) {
            conditions.push('(t.name LIKE ? OR t.location LIKE ? OR t.manager_name LIKE ?)');
            const searchTerm = `%${search}%`;
            queryParams.push(searchTerm, searchTerm, searchTerm);
        }
        
        if (status) {
            conditions.push('t.status = ?');
            queryParams.push(status);
        }
        
        if (conditions.length > 0) {
            whereClause = 'WHERE ' + conditions.join(' AND ');
        }
        
        const countQuery = `
            SELECT COUNT(*) as total 
            FROM terminals t 
            ${whereClause}
        `;
        
        const dataQuery = `
            SELECT t.id, t.name, t.location, t.capacity, t.operating_hours, 
                   t.contact_number, t.manager_name, t.status, t.facilities,
                   t.created_at, t.updated_at,
                   COUNT(ps.id) as total_spaces,
                   COUNT(CASE WHEN ps.status = 'available' THEN 1 END) as available_spaces,
                   COUNT(CASE WHEN ps.status = 'occupied' THEN 1 END) as occupied_spaces
            FROM terminals t 
            LEFT JOIN parking_spaces ps ON t.id = ps.terminal_id
            ${whereClause}
            GROUP BY t.id
            ORDER BY t.${sort} ${order}
            LIMIT ? OFFSET ?
        `;
        
        const [countResult] = await db.query(countQuery, queryParams);
        const total = countResult[0].total;
        
        const terminals = await db.query(dataQuery, [...queryParams, parseInt(limit), parseInt(offset)]);
        
        res.json({
            success: true,
            data: terminals,
            pagination: {
                page: parseInt(page),
                limit: parseInt(limit),
                total,
                pages: Math.ceil(total / limit)
            }
        });
    } catch (error) {
        console.error('Error fetching terminals:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch terminals',
            code: 'FETCH_ERROR'
        });
    }
});

// GET /api/v1/parking-terminal/terminals/:id - Get terminal by ID
router.get('/terminals/:id', validateId, async (req, res) => {
    try {
        const { id } = req.params;
        
        const query = `
            SELECT t.id, t.name, t.location, t.capacity, t.operating_hours, 
                   t.contact_number, t.manager_name, t.status, t.facilities,
                   t.created_at, t.updated_at,
                   COUNT(ps.id) as total_spaces,
                   COUNT(CASE WHEN ps.status = 'available' THEN 1 END) as available_spaces,
                   COUNT(CASE WHEN ps.status = 'occupied' THEN 1 END) as occupied_spaces,
                   COUNT(CASE WHEN ps.status = 'maintenance' THEN 1 END) as maintenance_spaces
            FROM terminals t 
            LEFT JOIN parking_spaces ps ON t.id = ps.terminal_id
            WHERE t.id = ?
            GROUP BY t.id
        `;
        
        const terminals = await db.query(query, [id]);
        
        if (terminals.length === 0) {
            return res.status(404).json({
                success: false,
                error: 'Terminal not found',
                code: 'NOT_FOUND'
            });
        }
        
        res.json({
            success: true,
            data: terminals[0]
        });
    } catch (error) {
        console.error('Error fetching terminal:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch terminal',
            code: 'FETCH_ERROR'
        });
    }
});

// POST /api/v1/parking-terminal/terminals - Create new terminal
router.post('/terminals', authenticateToken, authorize(['admin', 'staff']), validateTerminal, async (req, res) => {
    try {
        const {
            name, location, capacity, operating_hours, contact_number, 
            manager_name, status = 'active', facilities
        } = req.body;
        
        const query = `
            INSERT INTO terminals (
                name, location, capacity, operating_hours, contact_number, 
                manager_name, status, facilities
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        `;
        
        const insertResult = await db.query(query, [
            name, location, capacity, operating_hours, contact_number || null, 
            manager_name || null, status, facilities || null
        ]);
        
        res.status(201).json({
            success: true,
            message: 'Terminal created successfully',
            data: {
                id: insertResult.insertId,
                name, location, capacity, operating_hours, contact_number, 
                manager_name, status, facilities
            }
        });
    } catch (error) {
        console.error('Error creating terminal:', error);
        if (error.code === 'ER_DUP_ENTRY') {
            return res.status(409).json({
                success: false,
                error: 'Terminal name already exists',
                code: 'DUPLICATE_TERMINAL'
            });
        }
        res.status(500).json({
            success: false,
            error: 'Failed to create terminal',
            code: 'CREATE_ERROR'
        });
    }
});

// PUT /api/v1/parking-terminal/terminals/:id - Update terminal
router.put('/terminals/:id', authenticateToken, authorize(['admin', 'staff']), validateId, validateTerminal, async (req, res) => {
    try {
        const { id } = req.params;
        const {
            name, location, capacity, operating_hours, contact_number, 
            manager_name, status, facilities
        } = req.body;
        
        const query = `
            UPDATE terminals SET
                name = ?, location = ?, capacity = ?, operating_hours = ?, 
                contact_number = ?, manager_name = ?, status = ?, facilities = ?, 
                updated_at = NOW()
            WHERE id = ?
        `;
        
        const updateResult = await db.query(query, [
            name, location, capacity, operating_hours, contact_number || null, 
            manager_name || null, status, facilities || null, id
        ]);
        
        if (updateResult.affectedRows === 0) {
            return res.status(404).json({
                success: false,
                error: 'Terminal not found',
                code: 'NOT_FOUND'
            });
        }
        
        res.json({
            success: true,
            message: 'Terminal updated successfully'
        });
    } catch (error) {
        console.error('Error updating terminal:', error);
        if (error.code === 'ER_DUP_ENTRY') {
            return res.status(409).json({
                success: false,
                error: 'Terminal name already exists',
                code: 'DUPLICATE_TERMINAL'
            });
        }
        res.status(500).json({
            success: false,
            error: 'Failed to update terminal',
            code: 'UPDATE_ERROR'
        });
    }
});

// DELETE /api/v1/parking-terminal/terminals/:id - Delete terminal
router.delete('/terminals/:id', authenticateToken, authorize(['admin']), validateId, async (req, res) => {
    try {
        const { id } = req.params;
        
        // Check if terminal has parking spaces
        const spacesCheck = await db.query('SELECT COUNT(*) as count FROM parking_spaces WHERE terminal_id = ?', [id]);
        if (spacesCheck[0].count > 0) {
            return res.status(400).json({
                success: false,
                error: 'Cannot delete terminal with existing parking spaces',
                code: 'HAS_DEPENDENCIES'
            });
        }
        
        const query = 'DELETE FROM terminals WHERE id = ?';
        const result = await db.query(query, [id]);
        
        if (result.affectedRows === 0) {
            return res.status(404).json({
                success: false,
                error: 'Terminal not found',
                code: 'NOT_FOUND'
            });
        }
        
        res.json({
            success: true,
            message: 'Terminal deleted successfully'
        });
    } catch (error) {
        console.error('Error deleting terminal:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to delete terminal',
            code: 'DELETE_ERROR'
        });
    }
});

// Parking Session endpoints

// GET /api/v1/parking-terminal/sessions - Get all parking sessions
router.get('/sessions', validatePagination, async (req, res) => {
    try {
        const { page = 1, limit = 10, sort = 'entry_time', order = 'desc', search, payment_status, space_id, start_date, end_date } = req.query;
        const offset = (page - 1) * limit;
        
        let whereClause = '';
        let queryParams = [];
        
        const conditions = [];
        
        if (search) {
            conditions.push('(pss.vehicle_plate LIKE ? OR ps.space_number LIKE ?)');
            const searchTerm = `%${search}%`;
            queryParams.push(searchTerm, searchTerm);
        }
        
        if (payment_status) {
            conditions.push('pss.payment_status = ?');
            queryParams.push(payment_status);
        }
        
        if (space_id) {
            conditions.push('pss.space_id = ?');
            queryParams.push(space_id);
        }
        
        if (start_date) {
            conditions.push('DATE(pss.entry_time) >= ?');
            queryParams.push(start_date);
        }
        
        if (end_date) {
            conditions.push('DATE(pss.entry_time) <= ?');
            queryParams.push(end_date);
        }
        
        if (conditions.length > 0) {
            whereClause = 'WHERE ' + conditions.join(' AND ');
        }
        
        const countQuery = `
            SELECT COUNT(*) as total 
            FROM parking_sessions pss 
            LEFT JOIN parking_spaces ps ON pss.space_id = ps.id 
            LEFT JOIN terminals t ON ps.terminal_id = t.id 
            ${whereClause}
        `;
        
        const dataQuery = `
            SELECT pss.id, pss.space_id, pss.vehicle_plate, pss.vehicle_type, 
                   pss.entry_time, pss.exit_time, pss.duration_hours, 
                   pss.amount_due, pss.amount_paid, pss.payment_status, 
                   pss.payment_method, pss.created_at, pss.updated_at,
                   ps.space_number, ps.location as space_location,
                   t.name as terminal_name
            FROM parking_sessions pss 
            LEFT JOIN parking_spaces ps ON pss.space_id = ps.id 
            LEFT JOIN terminals t ON ps.terminal_id = t.id 
            ${whereClause}
            ORDER BY pss.${sort} ${order}
            LIMIT ? OFFSET ?
        `;
        
        const [countResult] = await db.query(countQuery, queryParams);
        const total = countResult[0].total;
        
        const sessions = await db.query(dataQuery, [...queryParams, parseInt(limit), parseInt(offset)]);
        
        res.json({
            success: true,
            data: sessions,
            pagination: {
                page: parseInt(page),
                limit: parseInt(limit),
                total,
                pages: Math.ceil(total / limit)
            }
        });
    } catch (error) {
        console.error('Error fetching parking sessions:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch parking sessions',
            code: 'FETCH_ERROR'
        });
    }
});

// POST /api/v1/parking-terminal/sessions - Create new parking session (entry)
router.post('/sessions', authenticateToken, authorize(['admin', 'staff', 'attendant']), validateParkingSession, async (req, res) => {
    try {
        const {
            space_id, vehicle_plate, vehicle_type, entry_time
        } = req.body;
        
        // Check if space exists and is available
        const spaceCheck = await db.query('SELECT id, status FROM parking_spaces WHERE id = ?', [space_id]);
        if (spaceCheck.length === 0) {
            return res.status(400).json({
                success: false,
                error: 'Parking space not found',
                code: 'SPACE_NOT_FOUND'
            });
        }
        
        if (spaceCheck[0].status !== 'available') {
            return res.status(400).json({
                success: false,
                error: 'Parking space is not available',
                code: 'SPACE_NOT_AVAILABLE'
            });
        }
        
        const query = `
            INSERT INTO parking_sessions (
                space_id, vehicle_plate, vehicle_type, entry_time, payment_status
            ) VALUES (?, ?, ?, ?, 'pending')
        `;
        
        const insertResult = await db.query(query, [
            space_id, vehicle_plate, vehicle_type, entry_time
        ]);
        
        // Update space status to occupied
        await db.query('UPDATE parking_spaces SET status = "occupied" WHERE id = ?', [space_id]);
        
        res.status(201).json({
            success: true,
            message: 'Parking session started successfully',
            data: {
                id: insertResult.insertId,
                space_id, vehicle_plate, vehicle_type, entry_time,
                payment_status: 'pending'
            }
        });
    } catch (error) {
        console.error('Error creating parking session:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to create parking session',
            code: 'CREATE_ERROR'
        });
    }
});

// PUT /api/v1/parking-terminal/sessions/:id/exit - End parking session (exit)
router.put('/sessions/:id/exit', authenticateToken, authorize(['admin', 'staff', 'attendant']), validateId, async (req, res) => {
    try {
        const { id } = req.params;
        const { exit_time, payment_method } = req.body;
        
        // Get session details
        const sessionQuery = `
            SELECT pss.*, ps.hourly_rate, ps.daily_rate 
            FROM parking_sessions pss 
            LEFT JOIN parking_spaces ps ON pss.space_id = ps.id 
            WHERE pss.id = ? AND pss.exit_time IS NULL
        `;
        
        const sessions = await db.query(sessionQuery, [id]);
        
        if (sessions.length === 0) {
            return res.status(404).json({
                success: false,
                error: 'Active parking session not found',
                code: 'SESSION_NOT_FOUND'
            });
        }
        
        const session = sessions[0];
        const entryTime = new Date(session.entry_time);
        const exitTimeDate = new Date(exit_time);
        
        // Calculate duration and amount
        const durationMs = exitTimeDate - entryTime;
        const durationHours = Math.ceil(durationMs / (1000 * 60 * 60)); // Round up to next hour
        
        let amountDue;
        if (durationHours <= 24) {
            amountDue = durationHours * session.hourly_rate;
        } else {
            const days = Math.ceil(durationHours / 24);
            amountDue = days * session.daily_rate;
        }
        
        const updateQuery = `
            UPDATE parking_sessions SET
                exit_time = ?, duration_hours = ?, amount_due = ?, 
                amount_paid = ?, payment_status = 'paid', payment_method = ?,
                updated_at = NOW()
            WHERE id = ?
        `;
        
        await db.query(updateQuery, [
            exit_time, durationHours, amountDue, amountDue, payment_method, id
        ]);
        
        // Update space status to available
        await db.query('UPDATE parking_spaces SET status = "available" WHERE id = ?', [session.space_id]);
        
        res.json({
            success: true,
            message: 'Parking session ended successfully',
            data: {
                duration_hours: durationHours,
                amount_due: amountDue,
                payment_status: 'paid'
            }
        });
    } catch (error) {
        console.error('Error ending parking session:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to end parking session',
            code: 'UPDATE_ERROR'
        });
    }
});

// GET /api/v1/parking-terminal/revenue/summary - Get revenue summary
router.get('/revenue/summary', optionalAuth, validateDateRange, async (req, res) => {
    try {
        const { start_date, end_date, terminal_id } = req.query;
        
        let whereClause = 'WHERE pss.payment_status = "paid"';
        let queryParams = [];
        
        if (start_date) {
            whereClause += ' AND DATE(pss.exit_time) >= ?';
            queryParams.push(start_date);
        }
        
        if (end_date) {
            whereClause += ' AND DATE(pss.exit_time) <= ?';
            queryParams.push(end_date);
        }
        
        if (terminal_id) {
            whereClause += ' AND ps.terminal_id = ?';
            queryParams.push(terminal_id);
        }
        
        const summaryQuery = `
            SELECT 
                COUNT(*) as total_sessions,
                SUM(pss.amount_paid) as total_revenue,
                AVG(pss.amount_paid) as average_fee,
                AVG(pss.duration_hours) as average_duration,
                COUNT(CASE WHEN pss.payment_method = 'cash' THEN 1 END) as cash_payments,
                COUNT(CASE WHEN pss.payment_method = 'card' THEN 1 END) as card_payments,
                COUNT(CASE WHEN pss.payment_method = 'mobile' THEN 1 END) as mobile_payments,
                SUM(CASE WHEN pss.payment_method = 'cash' THEN pss.amount_paid ELSE 0 END) as cash_revenue,
                SUM(CASE WHEN pss.payment_method = 'card' THEN pss.amount_paid ELSE 0 END) as card_revenue,
                SUM(CASE WHEN pss.payment_method = 'mobile' THEN pss.amount_paid ELSE 0 END) as mobile_revenue
            FROM parking_sessions pss 
            LEFT JOIN parking_spaces ps ON pss.space_id = ps.id 
            ${whereClause}
        `;
        
        const [summary] = await db.query(summaryQuery, queryParams);
        
        res.json({
            success: true,
            data: summary[0]
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

// GET /api/v1/parking-terminal/occupancy/current - Get current occupancy status
router.get('/occupancy/current', optionalAuth, async (req, res) => {
    try {
        const { terminal_id } = req.query;
        
        let whereClause = '';
        let queryParams = [];
        
        if (terminal_id) {
            whereClause = 'WHERE ps.terminal_id = ?';
            queryParams.push(terminal_id);
        }
        
        const occupancyQuery = `
            SELECT 
                t.id as terminal_id,
                t.name as terminal_name,
                t.capacity,
                COUNT(ps.id) as total_spaces,
                COUNT(CASE WHEN ps.status = 'available' THEN 1 END) as available_spaces,
                COUNT(CASE WHEN ps.status = 'occupied' THEN 1 END) as occupied_spaces,
                COUNT(CASE WHEN ps.status = 'maintenance' THEN 1 END) as maintenance_spaces,
                COUNT(CASE WHEN ps.status = 'reserved' THEN 1 END) as reserved_spaces,
                ROUND((COUNT(CASE WHEN ps.status = 'occupied' THEN 1 END) / COUNT(ps.id)) * 100, 2) as occupancy_rate
            FROM terminals t
            LEFT JOIN parking_spaces ps ON t.id = ps.terminal_id
            ${whereClause}
            GROUP BY t.id, t.name, t.capacity
            ORDER BY t.name
        `;
        
        const occupancy = await db.query(occupancyQuery, queryParams);
        
        res.json({
            success: true,
            data: occupancy
        });
    } catch (error) {
        console.error('Error fetching occupancy status:', error);
        res.status(500).json({
            success: false,
            error: 'Failed to fetch occupancy status',
            code: 'FETCH_ERROR'
        });
    }
});

module.exports = router;