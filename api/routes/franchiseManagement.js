const express = require('express');
const { body, query, param, validationResult } = require('express-validator');
const FranchiseManagement = require('../models/FranchiseManagement');

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
// FRANCHISE APPLICATIONS ENDPOINTS
// =============================================

// GET /api/v1/franchise/applications - Get all applications
router.get('/applications', [
  query('status').optional().isIn(['pending', 'under_review', 'approved', 'rejected', 'cancelled']),
  query('application_type').optional().isString(),
  query('operator_id').optional().isString(),
  query('date_from').optional().isISO8601(),
  query('date_to').optional().isISO8601(),
  query('limit').optional().isInt({ min: 1, max: 100 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await FranchiseManagement.getAllApplications(req.query);
    
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
    console.error('Franchise applications error:', error);
    res.status(500).json({
      success: false,
      error: 'Failed to fetch applications',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/franchise/applications/:id - Get application by ID
router.get('/applications/:id', [
  param('id').notEmpty().withMessage('Application ID is required'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await FranchiseManagement.getApplicationById(req.params.id);
    
    if (result.success) {
      if (result.data.length === 0) {
        return res.status(404).json({
          success: false,
          error: 'Application not found',
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
      error: 'Failed to fetch application',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// POST /api/v1/franchise/applications - Create new application
router.post('/applications', [
  body('operator_id').notEmpty().withMessage('Operator ID is required'),
  body('application_type').notEmpty().withMessage('Application type is required'),
  body('route_applied').notEmpty().withMessage('Route applied is required'),
  body('vehicle_capacity').isInt({ min: 1 }).withMessage('Vehicle capacity must be a positive integer'),
  body('application_fee').isFloat({ min: 0 }).withMessage('Application fee must be a positive number'),
  body('supporting_documents').optional().isArray(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await FranchiseManagement.createApplication(req.body);
    
    if (result.success) {
      res.status(201).json({
        success: true,
        message: 'Application created successfully',
        data: { application_id: result.application_id },
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
      error: 'Failed to create application',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// PUT /api/v1/franchise/applications/:id - Update application
router.put('/applications/:id', [
  param('id').notEmpty().withMessage('Application ID is required'),
  body('status').optional().isIn(['pending', 'under_review', 'approved', 'rejected', 'cancelled']),
  body('vehicle_capacity').optional().isInt({ min: 1 }),
  body('application_fee').optional().isFloat({ min: 0 }),
  body('supporting_documents').optional().isArray(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await FranchiseManagement.updateApplication(req.params.id, req.body);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Application updated successfully',
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
      error: 'Failed to update application',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// DELETE /api/v1/franchise/applications/:id - Delete application
router.delete('/applications/:id', [
  param('id').notEmpty().withMessage('Application ID is required'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await FranchiseManagement.deleteApplication(req.params.id);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Application deleted successfully',
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
      error: 'Failed to delete application',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// =============================================
// DOCUMENTS ENDPOINTS
// =============================================

// GET /api/v1/franchise/documents - Get all documents
router.get('/documents', [
  query('application_id').optional().isString(),
  query('document_type').optional().isString(),
  query('status').optional().isIn(['pending', 'verified', 'rejected']),
  query('limit').optional().isInt({ min: 1, max: 100 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await FranchiseManagement.getAllDocuments(req.query);
    
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
      error: 'Failed to fetch documents',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// POST /api/v1/franchise/documents - Create new document
router.post('/documents', [
  body('application_id').notEmpty().withMessage('Application ID is required'),
  body('document_type').notEmpty().withMessage('Document type is required'),
  body('document_name').notEmpty().withMessage('Document name is required'),
  body('file_path').notEmpty().withMessage('File path is required'),
  body('file_size').optional().isInt({ min: 0 }),
  body('uploaded_by').notEmpty().withMessage('Uploaded by is required'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await FranchiseManagement.createDocument(req.body);
    
    if (result.success) {
      res.status(201).json({
        success: true,
        message: 'Document created successfully',
        data: { document_id: result.document_id },
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
      error: 'Failed to create document',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// PUT /api/v1/franchise/documents/:id - Update document
router.put('/documents/:id', [
  param('id').notEmpty().withMessage('Document ID is required'),
  body('status').optional().isIn(['pending', 'verified', 'rejected']),
  body('verified_by').optional().isString(),
  body('verification_date').optional().isISO8601(),
  body('remarks').optional().isString(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await FranchiseManagement.updateDocument(req.params.id, req.body);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Document updated successfully',
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
      error: 'Failed to update document',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// =============================================
// FRANCHISES ENDPOINTS
// =============================================

// GET /api/v1/franchise/franchises - Get all franchises
router.get('/franchises', [
  query('operator_id').optional().isString(),
  query('status').optional().isIn(['active', 'expired', 'suspended', 'revoked']),
  query('franchise_type').optional().isString(),
  query('route_id').optional().isString(),
  query('expiring_soon').optional().isBoolean(),
  query('limit').optional().isInt({ min: 1, max: 100 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await FranchiseManagement.getAllFranchises(req.query);
    
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
      error: 'Failed to fetch franchises',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/franchise/franchises/:id - Get franchise by ID
router.get('/franchises/:id', [
  param('id').notEmpty().withMessage('Franchise ID is required'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await FranchiseManagement.getFranchiseById(req.params.id);
    
    if (result.success) {
      if (result.data.length === 0) {
        return res.status(404).json({
          success: false,
          error: 'Franchise not found',
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
      error: 'Failed to fetch franchise',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// POST /api/v1/franchise/franchises - Create new franchise
router.post('/franchises', [
  body('application_id').notEmpty().withMessage('Application ID is required'),
  body('operator_id').notEmpty().withMessage('Operator ID is required'),
  body('franchise_number').notEmpty().withMessage('Franchise number is required'),
  body('franchise_type').notEmpty().withMessage('Franchise type is required'),
  body('route_id').notEmpty().withMessage('Route ID is required'),
  body('issue_date').optional().isISO8601(),
  body('expiry_date').isISO8601().withMessage('Valid expiry date is required'),
  body('franchise_fee').isFloat({ min: 0 }).withMessage('Franchise fee must be a positive number'),
  body('issued_by').notEmpty().withMessage('Issued by is required'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await FranchiseManagement.createFranchise(req.body);
    
    if (result.success) {
      res.status(201).json({
        success: true,
        message: 'Franchise created successfully',
        data: { franchise_id: result.franchise_id },
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
      error: 'Failed to create franchise',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// PUT /api/v1/franchise/franchises/:id - Update franchise
router.put('/franchises/:id', [
  param('id').notEmpty().withMessage('Franchise ID is required'),
  body('status').optional().isIn(['active', 'expired', 'suspended', 'revoked']),
  body('expiry_date').optional().isISO8601(),
  body('franchise_fee').optional().isFloat({ min: 0 }),
  body('conditions').optional().isArray(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await FranchiseManagement.updateFranchise(req.params.id, req.body);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Franchise updated successfully',
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
      error: 'Failed to update franchise',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// DELETE /api/v1/franchise/franchises/:id - Delete franchise
router.delete('/franchises/:id', [
  param('id').notEmpty().withMessage('Franchise ID is required'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await FranchiseManagement.deleteFranchise(req.params.id);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Franchise deleted successfully',
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
      error: 'Failed to delete franchise',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// =============================================
// ROUTES ENDPOINTS
// =============================================

// GET /api/v1/franchise/routes - Get all routes
router.get('/routes', [
  query('status').optional().isIn(['active', 'inactive', 'under_construction']),
  query('route_type').optional().isString(),
  query('search').optional().isString(),
  query('limit').optional().isInt({ min: 1, max: 100 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await FranchiseManagement.getAllRoutes(req.query);
    
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
      error: 'Failed to fetch routes',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/franchise/routes/:id - Get route by ID
router.get('/routes/:id', [
  param('id').notEmpty().withMessage('Route ID is required'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await FranchiseManagement.getRouteById(req.params.id);
    
    if (result.success) {
      if (result.data.length === 0) {
        return res.status(404).json({
          success: false,
          error: 'Route not found',
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
      error: 'Failed to fetch route',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// POST /api/v1/franchise/routes - Create new route
router.post('/routes', [
  body('route_name').notEmpty().withMessage('Route name is required'),
  body('route_code').notEmpty().withMessage('Route code is required'),
  body('origin').notEmpty().withMessage('Origin is required'),
  body('destination').notEmpty().withMessage('Destination is required'),
  body('distance_km').isFloat({ min: 0 }).withMessage('Distance must be a positive number'),
  body('estimated_travel_time').isInt({ min: 1 }).withMessage('Travel time must be a positive integer'),
  body('route_type').notEmpty().withMessage('Route type is required'),
  body('fare_amount').isFloat({ min: 0 }).withMessage('Fare amount must be a positive number'),
  body('waypoints').optional().isArray(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await FranchiseManagement.createRoute(req.body);
    
    if (result.success) {
      res.status(201).json({
        success: true,
        message: 'Route created successfully',
        data: { route_id: result.route_id },
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
      error: 'Failed to create route',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// PUT /api/v1/franchise/routes/:id - Update route
router.put('/routes/:id', [
  param('id').notEmpty().withMessage('Route ID is required'),
  body('distance_km').optional().isFloat({ min: 0 }),
  body('estimated_travel_time').optional().isInt({ min: 1 }),
  body('fare_amount').optional().isFloat({ min: 0 }),
  body('waypoints').optional().isArray(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await FranchiseManagement.updateRoute(req.params.id, req.body);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Route updated successfully',
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
      error: 'Failed to update route',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// =============================================
// SCHEDULES ENDPOINTS
// =============================================

// GET /api/v1/franchise/schedules - Get all schedules
router.get('/schedules', [
  query('route_id').optional().isString(),
  query('operator_id').optional().isString(),
  query('vehicle_id').optional().isString(),
  query('status').optional().isIn(['active', 'inactive', 'suspended']),
  query('day_of_week').optional().isString(),
  query('limit').optional().isInt({ min: 1, max: 100 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await FranchiseManagement.getAllSchedules(req.query);
    
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
      error: 'Failed to fetch schedules',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// POST /api/v1/franchise/schedules - Create new schedule
router.post('/schedules', [
  body('route_id').notEmpty().withMessage('Route ID is required'),
  body('operator_id').notEmpty().withMessage('Operator ID is required'),
  body('vehicle_id').notEmpty().withMessage('Vehicle ID is required'),
  body('departure_time').matches(/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/).withMessage('Invalid departure time format (HH:MM)'),
  body('arrival_time').matches(/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/).withMessage('Invalid arrival time format (HH:MM)'),
  body('frequency_minutes').isInt({ min: 1 }).withMessage('Frequency must be a positive integer'),
  body('days_of_operation').isArray().withMessage('Days of operation must be an array'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await FranchiseManagement.createSchedule(req.body);
    
    if (result.success) {
      res.status(201).json({
        success: true,
        message: 'Schedule created successfully',
        data: { schedule_id: result.schedule_id },
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
      error: 'Failed to create schedule',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// =============================================
// WORKFLOW AND ANALYTICS ENDPOINTS
// =============================================

// POST /api/v1/franchise/applications/:id/approve - Approve application
router.post('/applications/:id/approve', [
  param('id').notEmpty().withMessage('Application ID is required'),
  body('approved_by').notEmpty().withMessage('Approved by is required'),
  body('approval_notes').optional().isString(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await FranchiseManagement.approveApplication(req.params.id, req.body);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Application approved successfully',
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
      error: 'Failed to approve application',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// POST /api/v1/franchise/applications/:id/reject - Reject application
router.post('/applications/:id/reject', [
  param('id').notEmpty().withMessage('Application ID is required'),
  body('rejected_by').notEmpty().withMessage('Rejected by is required'),
  body('rejection_reason').notEmpty().withMessage('Rejection reason is required'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await FranchiseManagement.rejectApplication(req.params.id, req.body);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Application rejected successfully',
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
      error: 'Failed to reject application',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/franchise/reports/expiring - Get expiring franchises
router.get('/reports/expiring', [
  query('days').optional().isInt({ min: 1, max: 365 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const days = req.query.days || 30;
    const result = await FranchiseManagement.getExpiringFranchises(days);
    
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
      error: 'Failed to fetch expiring franchises',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/franchise/reports/utilization - Get route utilization
router.get('/reports/utilization', [
  query('route_id').optional().isString(),
  query('date_from').optional().isISO8601(),
  query('date_to').optional().isISO8601(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await FranchiseManagement.getRouteUtilization(req.query);
    
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
      error: 'Failed to fetch route utilization',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

module.exports = router;