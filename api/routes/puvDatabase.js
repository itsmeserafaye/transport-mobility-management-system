const express = require('express');
const { body, query, param, validationResult } = require('express-validator');
const PuvDatabase = require('../models/PuvDatabase');

const router = express.Router();

// =============================================
// VALIDATION MIDDLEWARE
// =============================================

const handleValidationErrors = (req, res, next) => {
  const errors = validationResult(req);
  if (!errors.isEmpty()) {
    return res.status(400).json({
      error: 'Validation failed',
      details: errors.array(),
      timestamp: req.timestamp
    });
  }
  next();
};

// =============================================
// OPERATORS ENDPOINTS
// =============================================

// GET /api/v1/puv/operators - Get all operators
router.get('/operators', [
  query('status').optional().isIn(['active', 'inactive', 'suspended']),
  query('license_type').optional().isString(),
  query('search').optional().isString(),
  query('limit').optional().isInt({ min: 1, max: 100 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await PuvDatabase.getAllOperators(req.query);
    
    if (result.success) {
      res.json({
        success: true,
        data: result.data,
        count: result.data.length,
        timestamp: req.timestamp
      });
    } else {
      res.status(500).json({
        success: false,
        error: result.error,
        timestamp: req.timestamp
      });
    }
  } catch (error) {
    res.status(500).json({
      success: false,
      error: 'Failed to fetch operators',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/puv/operators/:id - Get operator by ID
router.get('/operators/:id', [
  param('id').notEmpty().withMessage('Operator ID is required'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await PuvDatabase.getOperatorById(req.params.id);
    
    if (result.success) {
      if (result.data.length === 0) {
        return res.status(404).json({
          success: false,
          error: 'Operator not found',
          timestamp: req.timestamp
        });
      }
      
      res.json({
        success: true,
        data: result.data[0],
        timestamp: req.timestamp
      });
    } else {
      res.status(500).json({
        success: false,
        error: result.error,
        timestamp: req.timestamp
      });
    }
  } catch (error) {
    res.status(500).json({
      success: false,
      error: 'Failed to fetch operator',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// POST /api/v1/puv/operators - Create new operator
router.post('/operators', [
  body('first_name').notEmpty().withMessage('First name is required'),
  body('last_name').notEmpty().withMessage('Last name is required'),
  body('license_number').notEmpty().withMessage('License number is required'),
  body('license_type').notEmpty().withMessage('License type is required'),
  body('contact_number').notEmpty().withMessage('Contact number is required'),
  body('email').optional().isEmail().withMessage('Invalid email format'),
  body('address').notEmpty().withMessage('Address is required'),
  body('date_of_birth').optional().isISO8601().withMessage('Invalid date format'),
  body('license_expiry').optional().isISO8601().withMessage('Invalid date format'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await PuvDatabase.createOperator(req.body);
    
    if (result.success) {
      res.status(201).json({
        success: true,
        message: 'Operator created successfully',
        data: { operator_id: result.operator_id },
        timestamp: req.timestamp
      });
    } else {
      res.status(500).json({
        success: false,
        error: result.error,
        timestamp: req.timestamp
      });
    }
  } catch (error) {
    res.status(500).json({
      success: false,
      error: 'Failed to create operator',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// PUT /api/v1/puv/operators/:id - Update operator
router.put('/operators/:id', [
  param('id').notEmpty().withMessage('Operator ID is required'),
  body('first_name').optional().notEmpty(),
  body('last_name').optional().notEmpty(),
  body('email').optional().isEmail(),
  body('date_of_birth').optional().isISO8601(),
  body('license_expiry').optional().isISO8601(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await PuvDatabase.updateOperator(req.params.id, req.body);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Operator updated successfully',
        timestamp: req.timestamp
      });
    } else {
      res.status(500).json({
        success: false,
        error: result.error,
        timestamp: req.timestamp
      });
    }
  } catch (error) {
    res.status(500).json({
      success: false,
      error: 'Failed to update operator',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// DELETE /api/v1/puv/operators/:id - Delete operator
router.delete('/operators/:id', [
  param('id').notEmpty().withMessage('Operator ID is required'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await PuvDatabase.deleteOperator(req.params.id);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Operator deleted successfully',
        timestamp: req.timestamp
      });
    } else {
      res.status(500).json({
        success: false,
        error: result.error,
        timestamp: req.timestamp
      });
    }
  } catch (error) {
    res.status(500).json({
      success: false,
      error: 'Failed to delete operator',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// =============================================
// VEHICLES ENDPOINTS
// =============================================

// GET /api/v1/puv/vehicles - Get all vehicles
router.get('/vehicles', [
  query('operator_id').optional().isString(),
  query('vehicle_type').optional().isString(),
  query('status').optional().isIn(['active', 'inactive', 'maintenance']),
  query('search').optional().isString(),
  query('limit').optional().isInt({ min: 1, max: 100 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await PuvDatabase.getAllVehicles(req.query);
    
    if (result.success) {
      res.json({
        success: true,
        data: result.data,
        count: result.data.length,
        timestamp: req.timestamp
      });
    } else {
      res.status(500).json({
        success: false,
        error: result.error,
        timestamp: req.timestamp
      });
    }
  } catch (error) {
    res.status(500).json({
      success: false,
      error: 'Failed to fetch vehicles',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/puv/vehicles/:id - Get vehicle by ID
router.get('/vehicles/:id', [
  param('id').notEmpty().withMessage('Vehicle ID is required'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await PuvDatabase.getVehicleById(req.params.id);
    
    if (result.success) {
      if (result.data.length === 0) {
        return res.status(404).json({
          success: false,
          error: 'Vehicle not found',
          timestamp: req.timestamp
        });
      }
      
      res.json({
        success: true,
        data: result.data[0],
        timestamp: req.timestamp
      });
    } else {
      res.status(500).json({
        success: false,
        error: result.error,
        timestamp: req.timestamp
      });
    }
  } catch (error) {
    res.status(500).json({
      success: false,
      error: 'Failed to fetch vehicle',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// POST /api/v1/puv/vehicles - Create new vehicle
router.post('/vehicles', [
  body('operator_id').notEmpty().withMessage('Operator ID is required'),
  body('plate_number').notEmpty().withMessage('Plate number is required'),
  body('vehicle_type').notEmpty().withMessage('Vehicle type is required'),
  body('make').notEmpty().withMessage('Make is required'),
  body('model').notEmpty().withMessage('Model is required'),
  body('year').isInt({ min: 1900, max: new Date().getFullYear() + 1 }).withMessage('Invalid year'),
  body('capacity').isInt({ min: 1 }).withMessage('Capacity must be a positive integer'),
  body('engine_number').optional().isString(),
  body('chassis_number').optional().isString(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await PuvDatabase.createVehicle(req.body);
    
    if (result.success) {
      res.status(201).json({
        success: true,
        message: 'Vehicle created successfully',
        data: { vehicle_id: result.vehicle_id },
        timestamp: req.timestamp
      });
    } else {
      res.status(500).json({
        success: false,
        error: result.error,
        timestamp: req.timestamp
      });
    }
  } catch (error) {
    res.status(500).json({
      success: false,
      error: 'Failed to create vehicle',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// PUT /api/v1/puv/vehicles/:id - Update vehicle
router.put('/vehicles/:id', [
  param('id').notEmpty().withMessage('Vehicle ID is required'),
  body('year').optional().isInt({ min: 1900, max: new Date().getFullYear() + 1 }),
  body('capacity').optional().isInt({ min: 1 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await PuvDatabase.updateVehicle(req.params.id, req.body);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Vehicle updated successfully',
        timestamp: req.timestamp
      });
    } else {
      res.status(500).json({
        success: false,
        error: result.error,
        timestamp: req.timestamp
      });
    }
  } catch (error) {
    res.status(500).json({
      success: false,
      error: 'Failed to update vehicle',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// DELETE /api/v1/puv/vehicles/:id - Delete vehicle
router.delete('/vehicles/:id', [
  param('id').notEmpty().withMessage('Vehicle ID is required'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await PuvDatabase.deleteVehicle(req.params.id);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Vehicle deleted successfully',
        timestamp: req.timestamp
      });
    } else {
      res.status(500).json({
        success: false,
        error: result.error,
        timestamp: req.timestamp
      });
    }
  } catch (error) {
    res.status(500).json({
      success: false,
      error: 'Failed to delete vehicle',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// =============================================
// COMPLIANCE STATUS ENDPOINTS
// =============================================

// GET /api/v1/puv/compliance - Get all compliance records
router.get('/compliance', [
  query('operator_id').optional().isString(),
  query('vehicle_id').optional().isString(),
  query('compliance_type').optional().isString(),
  query('status').optional().isIn(['compliant', 'non_compliant', 'pending']),
  query('limit').optional().isInt({ min: 1, max: 100 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await PuvDatabase.getAllComplianceStatus(req.query);
    
    if (result.success) {
      res.json({
        success: true,
        data: result.data,
        count: result.data.length,
        timestamp: req.timestamp
      });
    } else {
      res.status(500).json({
        success: false,
        error: result.error,
        timestamp: req.timestamp
      });
    }
  } catch (error) {
    res.status(500).json({
      success: false,
      error: 'Failed to fetch compliance records',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// POST /api/v1/puv/compliance - Create compliance record
router.post('/compliance', [
  body('operator_id').optional().isString(),
  body('vehicle_id').optional().isString(),
  body('compliance_type').notEmpty().withMessage('Compliance type is required'),
  body('status').isIn(['compliant', 'non_compliant', 'pending']).withMessage('Invalid status'),
  body('check_date').optional().isISO8601(),
  body('expiry_date').optional().isISO8601(),
  body('checked_by').notEmpty().withMessage('Checked by is required'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await PuvDatabase.createComplianceStatus(req.body);
    
    if (result.success) {
      res.status(201).json({
        success: true,
        message: 'Compliance record created successfully',
        data: { compliance_id: result.compliance_id },
        timestamp: req.timestamp
      });
    } else {
      res.status(500).json({
        success: false,
        error: result.error,
        timestamp: req.timestamp
      });
    }
  } catch (error) {
    res.status(500).json({
      success: false,
      error: 'Failed to create compliance record',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// =============================================
// VIOLATION HISTORY ENDPOINTS
// =============================================

// GET /api/v1/puv/violations - Get violation history
router.get('/violations', [
  query('operator_id').optional().isString(),
  query('vehicle_id').optional().isString(),
  query('violation_type').optional().isString(),
  query('status').optional().isString(),
  query('date_from').optional().isISO8601(),
  query('date_to').optional().isISO8601(),
  query('limit').optional().isInt({ min: 1, max: 100 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await PuvDatabase.getAllViolationHistory(req.query);
    
    if (result.success) {
      res.json({
        success: true,
        data: result.data,
        count: result.data.length,
        timestamp: req.timestamp
      });
    } else {
      res.status(500).json({
        success: false,
        error: result.error,
        timestamp: req.timestamp
      });
    }
  } catch (error) {
    res.status(500).json({
      success: false,
      error: 'Failed to fetch violation history',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// POST /api/v1/puv/violations - Create violation record
router.post('/violations', [
  body('operator_id').optional().isString(),
  body('vehicle_id').optional().isString(),
  body('violation_type').notEmpty().withMessage('Violation type is required'),
  body('violation_date').optional().isISO8601(),
  body('location').notEmpty().withMessage('Location is required'),
  body('fine_amount').isFloat({ min: 0 }).withMessage('Fine amount must be a positive number'),
  body('status').optional().isIn(['pending', 'paid', 'contested']),
  body('officer_name').notEmpty().withMessage('Officer name is required'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await PuvDatabase.createViolationHistory(req.body);
    
    if (result.success) {
      res.status(201).json({
        success: true,
        message: 'Violation record created successfully',
        data: { violation_id: result.violation_id },
        timestamp: req.timestamp
      });
    } else {
      res.status(500).json({
        success: false,
        error: result.error,
        timestamp: req.timestamp
      });
    }
  } catch (error) {
    res.status(500).json({
      success: false,
      error: 'Failed to create violation record',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// =============================================
// ANALYTICS AND REPORTS ENDPOINTS
// =============================================

// GET /api/v1/puv/reports/compliance - Get compliance report
router.get('/reports/compliance', [
  query('date_from').optional().isISO8601(),
  query('date_to').optional().isISO8601(),
  query('compliance_type').optional().isString(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await PuvDatabase.getComplianceReport(req.query);
    
    if (result.success) {
      res.json({
        success: true,
        data: result.data,
        timestamp: req.timestamp
      });
    } else {
      res.status(500).json({
        success: false,
        error: result.error,
        timestamp: req.timestamp
      });
    }
  } catch (error) {
    res.status(500).json({
      success: false,
      error: 'Failed to generate compliance report',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/puv/reports/violations - Get violation summary
router.get('/reports/violations', [
  query('date_from').optional().isISO8601(),
  query('date_to').optional().isISO8601(),
  query('violation_type').optional().isString(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await PuvDatabase.getViolationSummary(req.query);
    
    if (result.success) {
      res.json({
        success: true,
        data: result.data,
        timestamp: req.timestamp
      });
    } else {
      res.status(500).json({
        success: false,
        error: result.error,
        timestamp: req.timestamp
      });
    }
  } catch (error) {
    res.status(500).json({
      success: false,
      error: 'Failed to generate violation summary',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

module.exports = router;