const express = require('express');
const { body, query, param, validationResult } = require('express-validator');
const TrafficViolationTicketing = require('../models/TrafficViolationTicketing');

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
// VIOLATIONS ENDPOINTS
// =============================================

// GET /api/v1/traffic/violations - Get all violations
router.get('/violations', [
  query('status').optional().isIn(['pending', 'paid', 'contested', 'dismissed', 'overdue']),
  query('violation_type').optional().isString(),
  query('vehicle_plate').optional().isString(),
  query('officer_id').optional().isString(),
  query('location').optional().isString(),
  query('date_from').optional().isISO8601(),
  query('date_to').optional().isISO8601(),
  query('amount_min').optional().isFloat({ min: 0 }),
  query('amount_max').optional().isFloat({ min: 0 }),
  query('limit').optional().isInt({ min: 1, max: 100 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await TrafficViolationTicketing.getAllViolations(req.query);
    
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
      error: 'Failed to fetch violations',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/traffic/violations/:id - Get violation by ID
router.get('/violations/:id', [
  param('id').notEmpty().withMessage('Violation ID is required'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await TrafficViolationTicketing.getViolationById(req.params.id);
    
    if (result.success) {
      if (result.data.length === 0) {
        return res.status(404).json({
          success: false,
          error: 'Violation not found',
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
      error: 'Failed to fetch violation',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// POST /api/v1/traffic/violations - Create new violation
router.post('/violations', [
  body('ticket_number').notEmpty().withMessage('Ticket number is required'),
  body('vehicle_plate').notEmpty().withMessage('Vehicle plate is required'),
  body('violation_type').notEmpty().withMessage('Violation type is required'),
  body('violation_date').isISO8601().withMessage('Valid violation date is required'),
  body('location').notEmpty().withMessage('Location is required'),
  body('fine_amount').isFloat({ min: 0 }).withMessage('Fine amount must be a positive number'),
  body('officer_id').notEmpty().withMessage('Officer ID is required'),
  body('officer_name').notEmpty().withMessage('Officer name is required'),
  body('driver_name').optional().isString(),
  body('driver_license').optional().isString(),
  body('vehicle_owner').optional().isString(),
  body('description').optional().isString(),
  body('evidence_photos').optional().isArray(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await TrafficViolationTicketing.createViolation(req.body);
    
    if (result.success) {
      res.status(201).json({
        success: true,
        message: 'Violation created successfully',
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
      error: 'Failed to create violation',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// PUT /api/v1/traffic/violations/:id - Update violation
router.put('/violations/:id', [
  param('id').notEmpty().withMessage('Violation ID is required'),
  body('status').optional().isIn(['pending', 'paid', 'contested', 'dismissed', 'overdue']),
  body('fine_amount').optional().isFloat({ min: 0 }),
  body('payment_date').optional().isISO8601(),
  body('payment_method').optional().isString(),
  body('payment_reference').optional().isString(),
  body('contest_reason').optional().isString(),
  body('contest_date').optional().isISO8601(),
  body('resolution_notes').optional().isString(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await TrafficViolationTicketing.updateViolation(req.params.id, req.body);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Violation updated successfully',
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
      error: 'Failed to update violation',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// DELETE /api/v1/traffic/violations/:id - Delete violation
router.delete('/violations/:id', [
  param('id').notEmpty().withMessage('Violation ID is required'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await TrafficViolationTicketing.deleteViolation(req.params.id);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Violation deleted successfully',
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
      error: 'Failed to delete violation',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// =============================================
// VIOLATION SETTLEMENT ENDPOINTS
// =============================================

// POST /api/v1/traffic/violations/:id/pay - Process payment for violation
router.post('/violations/:id/pay', [
  param('id').notEmpty().withMessage('Violation ID is required'),
  body('payment_amount').isFloat({ min: 0 }).withMessage('Payment amount must be a positive number'),
  body('payment_method').notEmpty().withMessage('Payment method is required'),
  body('payment_reference').optional().isString(),
  body('paid_by').notEmpty().withMessage('Paid by is required'),
  body('payment_notes').optional().isString(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await TrafficViolationTicketing.processPayment(req.params.id, req.body);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Payment processed successfully',
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
      error: 'Failed to process payment',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// POST /api/v1/traffic/violations/:id/contest - Contest a violation
router.post('/violations/:id/contest', [
  param('id').notEmpty().withMessage('Violation ID is required'),
  body('contest_reason').notEmpty().withMessage('Contest reason is required'),
  body('contested_by').notEmpty().withMessage('Contested by is required'),
  body('supporting_evidence').optional().isArray(),
  body('contact_information').optional().isString(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await TrafficViolationTicketing.contestViolation(req.params.id, req.body);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Violation contested successfully',
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
      error: 'Failed to contest violation',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// POST /api/v1/traffic/violations/:id/dismiss - Dismiss a violation
router.post('/violations/:id/dismiss', [
  param('id').notEmpty().withMessage('Violation ID is required'),
  body('dismissed_by').notEmpty().withMessage('Dismissed by is required'),
  body('dismissal_reason').notEmpty().withMessage('Dismissal reason is required'),
  body('dismissal_notes').optional().isString(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await TrafficViolationTicketing.dismissViolation(req.params.id, req.body);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Violation dismissed successfully',
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
      error: 'Failed to dismiss violation',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// =============================================
// ANALYTICS ENDPOINTS
// =============================================

// GET /api/v1/traffic/analytics/summary - Get violation summary
router.get('/analytics/summary', [
  query('date_from').optional().isISO8601(),
  query('date_to').optional().isISO8601(),
  query('group_by').optional().isIn(['day', 'week', 'month', 'year']),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await TrafficViolationTicketing.getViolationSummary(req.query);
    
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
      error: 'Failed to fetch violation summary',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/traffic/analytics/by-type - Get violations by type
router.get('/analytics/by-type', [
  query('date_from').optional().isISO8601(),
  query('date_to').optional().isISO8601(),
  query('limit').optional().isInt({ min: 1, max: 50 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await TrafficViolationTicketing.getViolationsByType(req.query);
    
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
      error: 'Failed to fetch violations by type',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/traffic/analytics/by-location - Get violations by location
router.get('/analytics/by-location', [
  query('date_from').optional().isISO8601(),
  query('date_to').optional().isISO8601(),
  query('limit').optional().isInt({ min: 1, max: 50 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await TrafficViolationTicketing.getViolationsByLocation(req.query);
    
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
      error: 'Failed to fetch violations by location',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/traffic/analytics/repeat-offenders - Get repeat offenders
router.get('/analytics/repeat-offenders', [
  query('min_violations').optional().isInt({ min: 2 }),
  query('date_from').optional().isISO8601(),
  query('date_to').optional().isISO8601(),
  query('limit').optional().isInt({ min: 1, max: 100 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await TrafficViolationTicketing.getRepeatOffenders(req.query);
    
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
      error: 'Failed to fetch repeat offenders',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/traffic/analytics/trends - Get violation trends
router.get('/analytics/trends', [
  query('period').optional().isIn(['daily', 'weekly', 'monthly', 'yearly']),
  query('date_from').optional().isISO8601(),
  query('date_to').optional().isISO8601(),
  query('violation_type').optional().isString(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await TrafficViolationTicketing.getViolationTrends(req.query);
    
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
      error: 'Failed to fetch violation trends',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/traffic/analytics/officer-performance - Get officer performance
router.get('/analytics/officer-performance', [
  query('officer_id').optional().isString(),
  query('date_from').optional().isISO8601(),
  query('date_to').optional().isISO8601(),
  query('limit').optional().isInt({ min: 1, max: 100 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await TrafficViolationTicketing.getOfficerPerformance(req.query);
    
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
      error: 'Failed to fetch officer performance',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// =============================================
// REVENUE REPORTS ENDPOINTS
// =============================================

// GET /api/v1/traffic/revenue/reports - Get revenue reports
router.get('/revenue/reports', [
  query('report_type').optional().isIn(['daily', 'weekly', 'monthly', 'yearly', 'custom']),
  query('date_from').optional().isISO8601(),
  query('date_to').optional().isISO8601(),
  query('violation_type').optional().isString(),
  query('location').optional().isString(),
  query('officer_id').optional().isString(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await TrafficViolationTicketing.getRevenueReports(req.query);
    
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
      error: 'Failed to fetch revenue reports',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// POST /api/v1/traffic/revenue/generate - Generate revenue report
router.post('/revenue/generate', [
  body('report_type').isIn(['daily', 'weekly', 'monthly', 'yearly', 'custom']).withMessage('Valid report type is required'),
  body('date_from').isISO8601().withMessage('Valid start date is required'),
  body('date_to').isISO8601().withMessage('Valid end date is required'),
  body('generated_by').notEmpty().withMessage('Generated by is required'),
  body('filters').optional().isObject(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await TrafficViolationTicketing.generateRevenueReport(req.body);
    
    if (result.success) {
      res.status(201).json({
        success: true,
        message: 'Revenue report generated successfully',
        data: { report_id: result.report_id },
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
      error: 'Failed to generate revenue report',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/traffic/revenue/summary - Get revenue summary
router.get('/revenue/summary', [
  query('date_from').optional().isISO8601(),
  query('date_to').optional().isISO8601(),
  query('group_by').optional().isIn(['day', 'week', 'month', 'year']),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await TrafficViolationTicketing.getRevenueSummary(req.query);
    
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
      error: 'Failed to fetch revenue summary',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/traffic/violations/overdue - Get overdue violations
router.get('/violations/overdue', [
  query('days_overdue').optional().isInt({ min: 1 }),
  query('limit').optional().isInt({ min: 1, max: 100 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await TrafficViolationTicketing.getOverdueViolations(req.query);
    
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
      error: 'Failed to fetch overdue violations',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/traffic/violations/vehicle/:plate - Get violations by vehicle plate
router.get('/violations/vehicle/:plate', [
  param('plate').notEmpty().withMessage('Vehicle plate is required'),
  query('status').optional().isIn(['pending', 'paid', 'contested', 'dismissed', 'overdue']),
  query('date_from').optional().isISO8601(),
  query('date_to').optional().isISO8601(),
  query('limit').optional().isInt({ min: 1, max: 100 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await TrafficViolationTicketing.getViolationsByVehicle(req.params.plate, req.query);
    
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
      error: 'Failed to fetch violations by vehicle',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

module.exports = router;