const express = require('express');
const { body, query, param, validationResult } = require('express-validator');
const VehicleInspectionRegistration = require('../models/VehicleInspectionRegistration');

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
// INSPECTIONS ENDPOINTS
// =============================================

// GET /api/v1/vehicle/inspections - Get all inspections
router.get('/inspections', [
  query('vehicle_id').optional().isString(),
  query('inspection_type').optional().isString(),
  query('status').optional().isIn(['scheduled', 'in_progress', 'completed', 'failed', 'cancelled']),
  query('inspector_id').optional().isString(),
  query('date_from').optional().isISO8601(),
  query('date_to').optional().isISO8601(),
  query('result').optional().isIn(['pass', 'fail', 'conditional_pass']),
  query('limit').optional().isInt({ min: 1, max: 100 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await VehicleInspectionRegistration.getAllInspectionRecords(req.query);
    
    if (result.success) {
      res.json({
        success: true,
        data: result.data,
        count: result.data.length,
        timestamp: req.timestamp
      });
    } else {
      console.error('Inspections result error:', result.error);
      res.status(500).json({
        success: false,
        error: result.error,
        timestamp: req.timestamp
      });
    }
  } catch (error) {
    console.error('Inspections catch error:', error);
    res.status(500).json({
      success: false,
      error: 'Failed to fetch inspections',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/vehicle/inspections/:id - Get inspection by ID
router.get('/inspections/:id', [
  param('id').notEmpty().withMessage('Inspection ID is required'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await VehicleInspectionRegistration.getInspectionById(req.params.id);
    
    if (result.success) {
      if (result.data.length === 0) {
        return res.status(404).json({
          success: false,
          error: 'Inspection not found',
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
      error: 'Failed to fetch inspection',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// POST /api/v1/vehicle/inspections - Create new inspection
router.post('/inspections', [
  body('vehicle_id').notEmpty().withMessage('Vehicle ID is required'),
  body('inspection_type').notEmpty().withMessage('Inspection type is required'),
  body('scheduled_date').isISO8601().withMessage('Valid scheduled date is required'),
  body('inspector_id').notEmpty().withMessage('Inspector ID is required'),
  body('inspection_location').notEmpty().withMessage('Inspection location is required'),
  body('inspection_fee').isFloat({ min: 0 }).withMessage('Inspection fee must be a positive number'),
  body('requirements').optional().isArray(),
  body('notes').optional().isString(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await VehicleInspectionRegistration.createInspection(req.body);
    
    if (result.success) {
      res.status(201).json({
        success: true,
        message: 'Inspection created successfully',
        data: { inspection_id: result.inspection_id },
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
      error: 'Failed to create inspection',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// PUT /api/v1/vehicle/inspections/:id - Update inspection
router.put('/inspections/:id', [
  param('id').notEmpty().withMessage('Inspection ID is required'),
  body('status').optional().isIn(['scheduled', 'in_progress', 'completed', 'failed', 'cancelled']),
  body('actual_date').optional().isISO8601(),
  body('result').optional().isIn(['pass', 'fail', 'conditional_pass']),
  body('score').optional().isInt({ min: 0, max: 100 }),
  body('defects_found').optional().isArray(),
  body('recommendations').optional().isArray(),
  body('next_inspection_due').optional().isISO8601(),
  body('certificate_issued').optional().isBoolean(),
  body('certificate_number').optional().isString(),
  body('inspector_notes').optional().isString(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await VehicleInspectionRegistration.updateInspection(req.params.id, req.body);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Inspection updated successfully',
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
      error: 'Failed to update inspection',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// DELETE /api/v1/vehicle/inspections/:id - Delete inspection
router.delete('/inspections/:id', [
  param('id').notEmpty().withMessage('Inspection ID is required'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await VehicleInspectionRegistration.deleteInspection(req.params.id);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Inspection deleted successfully',
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
      error: 'Failed to delete inspection',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// =============================================
// REGISTRATIONS ENDPOINTS
// =============================================

// GET /api/v1/vehicle/registrations - Get all registrations
router.get('/registrations', [
  query('vehicle_id').optional().isString(),
  query('owner_id').optional().isString(),
  query('status').optional().isIn(['active', 'expired', 'suspended', 'cancelled']),
  query('registration_type').optional().isString(),
  query('expiring_soon').optional().isBoolean(),
  query('date_from').optional().isISO8601(),
  query('date_to').optional().isISO8601(),
  query('limit').optional().isInt({ min: 1, max: 100 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await VehicleInspectionRegistration.getAllRegistrations(req.query);
    
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
      error: 'Failed to fetch registrations',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/vehicle/registrations/:id - Get registration by ID
router.get('/registrations/:id', [
  param('id').notEmpty().withMessage('Registration ID is required'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await VehicleInspectionRegistration.getRegistrationById(req.params.id);
    
    if (result.success) {
      if (result.data.length === 0) {
        return res.status(404).json({
          success: false,
          error: 'Registration not found',
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
      error: 'Failed to fetch registration',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// POST /api/v1/vehicle/registrations - Create new registration
router.post('/registrations', [
  body('vehicle_id').notEmpty().withMessage('Vehicle ID is required'),
  body('owner_id').notEmpty().withMessage('Owner ID is required'),
  body('registration_number').notEmpty().withMessage('Registration number is required'),
  body('registration_type').notEmpty().withMessage('Registration type is required'),
  body('issue_date').optional().isISO8601(),
  body('expiry_date').isISO8601().withMessage('Valid expiry date is required'),
  body('registration_fee').isFloat({ min: 0 }).withMessage('Registration fee must be a positive number'),
  body('issued_by').notEmpty().withMessage('Issued by is required'),
  body('vehicle_use').notEmpty().withMessage('Vehicle use is required'),
  body('insurance_details').optional().isObject(),
  body('supporting_documents').optional().isArray(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await VehicleInspectionRegistration.createRegistration(req.body);
    
    if (result.success) {
      res.status(201).json({
        success: true,
        message: 'Registration created successfully',
        data: { registration_id: result.registration_id },
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
      error: 'Failed to create registration',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// PUT /api/v1/vehicle/registrations/:id - Update registration
router.put('/registrations/:id', [
  param('id').notEmpty().withMessage('Registration ID is required'),
  body('status').optional().isIn(['active', 'expired', 'suspended', 'cancelled']),
  body('expiry_date').optional().isISO8601(),
  body('registration_fee').optional().isFloat({ min: 0 }),
  body('vehicle_use').optional().isString(),
  body('insurance_details').optional().isObject(),
  body('renewal_date').optional().isISO8601(),
  body('renewal_fee').optional().isFloat({ min: 0 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await VehicleInspectionRegistration.updateRegistration(req.params.id, req.body);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Registration updated successfully',
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
      error: 'Failed to update registration',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// DELETE /api/v1/vehicle/registrations/:id - Delete registration
router.delete('/registrations/:id', [
  param('id').notEmpty().withMessage('Registration ID is required'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await VehicleInspectionRegistration.deleteRegistration(req.params.id);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Registration deleted successfully',
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
      error: 'Failed to delete registration',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// =============================================
// COMPLIANCE TRACKING ENDPOINTS
// =============================================

// GET /api/v1/vehicle/compliance - Get all compliance records
router.get('/compliance', [
  query('vehicle_id').optional().isString(),
  query('compliance_type').optional().isString(),
  query('status').optional().isIn(['compliant', 'non_compliant', 'pending', 'expired']),
  query('due_soon').optional().isBoolean(),
  query('overdue').optional().isBoolean(),
  query('limit').optional().isInt({ min: 1, max: 100 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await VehicleInspectionRegistration.getAllCompliance(req.query);
    
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

// POST /api/v1/vehicle/compliance - Create new compliance record
router.post('/compliance', [
  body('vehicle_id').notEmpty().withMessage('Vehicle ID is required'),
  body('compliance_type').notEmpty().withMessage('Compliance type is required'),
  body('requirement_description').notEmpty().withMessage('Requirement description is required'),
  body('due_date').isISO8601().withMessage('Valid due date is required'),
  body('responsible_party').notEmpty().withMessage('Responsible party is required'),
  body('priority').optional().isIn(['low', 'medium', 'high', 'critical']),
  body('compliance_details').optional().isObject(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await VehicleInspectionRegistration.createCompliance(req.body);
    
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

// PUT /api/v1/vehicle/compliance/:id - Update compliance record
router.put('/compliance/:id', [
  param('id').notEmpty().withMessage('Compliance ID is required'),
  body('status').optional().isIn(['compliant', 'non_compliant', 'pending', 'expired']),
  body('completion_date').optional().isISO8601(),
  body('compliance_officer').optional().isString(),
  body('verification_documents').optional().isArray(),
  body('notes').optional().isString(),
  body('next_review_date').optional().isISO8601(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await VehicleInspectionRegistration.updateCompliance(req.params.id, req.body);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Compliance record updated successfully',
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
      error: 'Failed to update compliance record',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// =============================================
// INSPECTION SCHEDULING ENDPOINTS
// =============================================

// POST /api/v1/vehicle/inspections/schedule - Schedule inspection
router.post('/inspections/schedule', [
  body('vehicle_id').notEmpty().withMessage('Vehicle ID is required'),
  body('inspection_type').notEmpty().withMessage('Inspection type is required'),
  body('preferred_date').isISO8601().withMessage('Valid preferred date is required'),
  body('preferred_time').optional().matches(/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/),
  body('location_preference').optional().isString(),
  body('contact_person').notEmpty().withMessage('Contact person is required'),
  body('contact_phone').notEmpty().withMessage('Contact phone is required'),
  body('special_requirements').optional().isString(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await VehicleInspectionRegistration.scheduleInspection(req.body);
    
    if (result.success) {
      res.status(201).json({
        success: true,
        message: 'Inspection scheduled successfully',
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
      error: 'Failed to schedule inspection',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// PUT /api/v1/vehicle/inspections/:id/reschedule - Reschedule inspection
router.put('/inspections/:id/reschedule', [
  param('id').notEmpty().withMessage('Inspection ID is required'),
  body('new_date').isISO8601().withMessage('Valid new date is required'),
  body('new_time').optional().matches(/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/),
  body('reason').notEmpty().withMessage('Reason for rescheduling is required'),
  body('rescheduled_by').notEmpty().withMessage('Rescheduled by is required'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await VehicleInspectionRegistration.rescheduleInspection(req.params.id, req.body);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Inspection rescheduled successfully',
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
      error: 'Failed to reschedule inspection',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// =============================================
// ANALYTICS AND REPORTS ENDPOINTS
// =============================================

// GET /api/v1/vehicle/reports/inspection-summary - Get inspection summary
router.get('/reports/inspection-summary', [
  query('date_from').optional().isISO8601(),
  query('date_to').optional().isISO8601(),
  query('inspection_type').optional().isString(),
  query('inspector_id').optional().isString(),
  query('group_by').optional().isIn(['day', 'week', 'month', 'year']),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await VehicleInspectionRegistration.getInspectionSummary(req.query);
    
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
      error: 'Failed to fetch inspection summary',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/vehicle/reports/registration-summary - Get registration summary
router.get('/reports/registration-summary', [
  query('date_from').optional().isISO8601(),
  query('date_to').optional().isISO8601(),
  query('registration_type').optional().isString(),
  query('status').optional().isIn(['active', 'expired', 'suspended', 'cancelled']),
  query('group_by').optional().isIn(['day', 'week', 'month', 'year']),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await VehicleInspectionRegistration.getRegistrationSummary(req.query);
    
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
      error: 'Failed to fetch registration summary',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/vehicle/reports/compliance-summary - Get compliance summary
router.get('/reports/compliance-summary', [
  query('compliance_type').optional().isString(),
  query('status').optional().isIn(['compliant', 'non_compliant', 'pending', 'expired']),
  query('due_soon_days').optional().isInt({ min: 1, max: 365 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await VehicleInspectionRegistration.getComplianceSummary(req.query);
    
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
      error: 'Failed to fetch compliance summary',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/vehicle/reports/expiring-documents - Get expiring documents
router.get('/reports/expiring-documents', [
  query('days').optional().isInt({ min: 1, max: 365 }),
  query('document_type').optional().isIn(['registration', 'inspection', 'insurance', 'license']),
  query('limit').optional().isInt({ min: 1, max: 100 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const days = req.query.days || 30;
    const result = await VehicleInspectionRegistration.getExpiringDocuments(days, req.query);
    
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
      error: 'Failed to fetch expiring documents',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/vehicle/reports/inspections-by-type - Get inspections by type
router.get('/reports/inspections-by-type', [
  query('date_from').optional().isISO8601(),
  query('date_to').optional().isISO8601(),
  query('limit').optional().isInt({ min: 1, max: 50 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await VehicleInspectionRegistration.getInspectionsByType(req.query);
    
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
      error: 'Failed to fetch inspections by type',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/vehicle/reports/inspector-performance - Get inspector performance
router.get('/reports/inspector-performance', [
  query('inspector_id').optional().isString(),
  query('date_from').optional().isISO8601(),
  query('date_to').optional().isISO8601(),
  query('limit').optional().isInt({ min: 1, max: 100 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await VehicleInspectionRegistration.getInspectorPerformance(req.query);
    
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
      error: 'Failed to fetch inspector performance',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/vehicle/inspections/upcoming - Get upcoming inspections
router.get('/inspections/upcoming', [
  query('days').optional().isInt({ min: 1, max: 365 }),
  query('inspector_id').optional().isString(),
  query('inspection_type').optional().isString(),
  query('limit').optional().isInt({ min: 1, max: 100 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const days = req.query.days || 7;
    const result = await VehicleInspectionRegistration.getUpcomingInspections(days, req.query);
    
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
      error: 'Failed to fetch upcoming inspections',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/vehicle/registrations/vehicle/:vehicleId - Get registration by vehicle ID
router.get('/registrations/vehicle/:vehicleId', [
  param('vehicleId').notEmpty().withMessage('Vehicle ID is required'),
  query('status').optional().isIn(['active', 'expired', 'suspended', 'cancelled']),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await VehicleInspectionRegistration.getRegistrationByVehicle(req.params.vehicleId, req.query);
    
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
      error: 'Failed to fetch registration by vehicle',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

module.exports = router;