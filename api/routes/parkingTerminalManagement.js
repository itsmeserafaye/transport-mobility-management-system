const express = require('express');
const { body, query, param, validationResult } = require('express-validator');
const ParkingTerminalManagement = require('../models/ParkingTerminalManagement');

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
// TERMINALS ENDPOINTS
// =============================================

// GET /api/v1/parking/terminals - Get all terminals
router.get('/terminals', [
  query('status').optional().isIn(['active', 'inactive', 'maintenance', 'closed']),
  query('terminal_type').optional().isString(),
  query('location').optional().isString(),
  query('capacity_min').optional().isInt({ min: 0 }),
  query('capacity_max').optional().isInt({ min: 0 }),
  query('search').optional().isString(),
  query('limit').optional().isInt({ min: 1, max: 100 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await ParkingTerminalManagement.getAllTerminals(req.query);
    
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
      error: 'Failed to fetch terminals',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/parking/terminals/:id - Get terminal by ID
router.get('/terminals/:id', [
  param('id').notEmpty().withMessage('Terminal ID is required'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await ParkingTerminalManagement.getTerminalById(req.params.id);
    
    if (result.success) {
      if (result.data.length === 0) {
        return res.status(404).json({
          success: false,
          error: 'Terminal not found',
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
      error: 'Failed to fetch terminal',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// POST /api/v1/parking/terminals - Create new terminal
router.post('/terminals', [
  body('terminal_name').notEmpty().withMessage('Terminal name is required'),
  body('terminal_code').notEmpty().withMessage('Terminal code is required'),
  body('location').notEmpty().withMessage('Location is required'),
  body('address').notEmpty().withMessage('Address is required'),
  body('capacity').isInt({ min: 1 }).withMessage('Capacity must be a positive integer'),
  body('operating_hours').notEmpty().withMessage('Operating hours are required'),
  body('contact_person').notEmpty().withMessage('Contact person is required'),
  body('contact_number').notEmpty().withMessage('Contact number is required'),
  body('latitude').optional().isFloat(),
  body('longitude').optional().isFloat(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await ParkingTerminalManagement.createTerminal(req.body);
    
    if (result.success) {
      res.status(201).json({
        success: true,
        message: 'Terminal created successfully',
        data: { terminal_id: result.terminal_id },
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
      error: 'Failed to create terminal',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// PUT /api/v1/parking/terminals/:id - Update terminal
router.put('/terminals/:id', [
  param('id').notEmpty().withMessage('Terminal ID is required'),
  body('status').optional().isIn(['active', 'inactive', 'maintenance', 'closed']),
  body('capacity').optional().isInt({ min: 1 }),
  body('operating_hours').optional().isString(),
  body('contact_person').optional().isString(),
  body('contact_number').optional().isString(),
  body('latitude').optional().isFloat(),
  body('longitude').optional().isFloat(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await ParkingTerminalManagement.updateTerminal(req.params.id, req.body);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Terminal updated successfully',
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
      error: 'Failed to update terminal',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// DELETE /api/v1/parking/terminals/:id - Delete terminal
router.delete('/terminals/:id', [
  param('id').notEmpty().withMessage('Terminal ID is required'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await ParkingTerminalManagement.deleteTerminal(req.params.id);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Terminal deleted successfully',
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
      error: 'Failed to delete terminal',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// =============================================
// TERMINAL ASSIGNMENTS ENDPOINTS
// =============================================

// GET /api/v1/parking/assignments - Get all terminal assignments
router.get('/assignments', [
  query('terminal_id').optional().isString(),
  query('operator_id').optional().isString(),
  query('status').optional().isIn(['active', 'inactive', 'suspended']),
  query('assignment_type').optional().isString(),
  query('limit').optional().isInt({ min: 1, max: 100 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await ParkingTerminalManagement.getAllTerminalAssignments(req.query);
    
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
      error: 'Failed to fetch terminal assignments',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/parking/assignments/:id - Get terminal assignment by ID
router.get('/assignments/:id', [
  param('id').notEmpty().withMessage('Assignment ID is required'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await ParkingTerminalManagement.getTerminalAssignmentById(req.params.id);
    
    if (result.success) {
      if (result.data.length === 0) {
        return res.status(404).json({
          success: false,
          error: 'Terminal assignment not found',
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
      error: 'Failed to fetch terminal assignment',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// POST /api/v1/parking/assignments - Create new terminal assignment
router.post('/assignments', [
  body('terminal_id').notEmpty().withMessage('Terminal ID is required'),
  body('operator_id').notEmpty().withMessage('Operator ID is required'),
  body('assignment_type').notEmpty().withMessage('Assignment type is required'),
  body('start_date').isISO8601().withMessage('Valid start date is required'),
  body('end_date').optional().isISO8601(),
  body('shift_schedule').optional().isString(),
  body('responsibilities').optional().isString(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await ParkingTerminalManagement.createTerminalAssignment(req.body);
    
    if (result.success) {
      res.status(201).json({
        success: true,
        message: 'Terminal assignment created successfully',
        data: { assignment_id: result.assignment_id },
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
      error: 'Failed to create terminal assignment',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// PUT /api/v1/parking/assignments/:id - Update terminal assignment
router.put('/assignments/:id', [
  param('id').notEmpty().withMessage('Assignment ID is required'),
  body('status').optional().isIn(['active', 'inactive', 'suspended']),
  body('end_date').optional().isISO8601(),
  body('shift_schedule').optional().isString(),
  body('responsibilities').optional().isString(),
  body('performance_notes').optional().isString(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await ParkingTerminalManagement.updateTerminalAssignment(req.params.id, req.body);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Terminal assignment updated successfully',
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
      error: 'Failed to update terminal assignment',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// DELETE /api/v1/parking/assignments/:id - Delete terminal assignment
router.delete('/assignments/:id', [
  param('id').notEmpty().withMessage('Assignment ID is required'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await ParkingTerminalManagement.deleteTerminalAssignment(req.params.id);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Terminal assignment deleted successfully',
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
      error: 'Failed to delete terminal assignment',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// =============================================
// TERMINAL ASSIGNMENT OPERATIONS
// =============================================

// POST /api/v1/parking/assignments/assign - Assign operator to terminal
router.post('/assignments/assign', [
  body('terminal_id').notEmpty().withMessage('Terminal ID is required'),
  body('operator_id').notEmpty().withMessage('Operator ID is required'),
  body('assignment_type').notEmpty().withMessage('Assignment type is required'),
  body('start_date').isISO8601().withMessage('Valid start date is required'),
  body('shift_schedule').optional().isString(),
  body('responsibilities').optional().isString(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await ParkingTerminalManagement.assignOperatorToTerminal(req.body);
    
    if (result.success) {
      res.status(201).json({
        success: true,
        message: 'Operator assigned to terminal successfully',
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
      error: 'Failed to assign operator to terminal',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// POST /api/v1/parking/assignments/reassign - Reassign operator
router.post('/assignments/reassign', [
  body('assignment_id').notEmpty().withMessage('Assignment ID is required'),
  body('new_terminal_id').notEmpty().withMessage('New terminal ID is required'),
  body('reason').optional().isString(),
  body('effective_date').isISO8601().withMessage('Valid effective date is required'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await ParkingTerminalManagement.reassignOperator(req.body);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Operator reassigned successfully',
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
      error: 'Failed to reassign operator',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// =============================================
// TERMINAL CAPACITY TRACKING ENDPOINTS
// =============================================

// POST /api/v1/parking/capacity/track - Create capacity tracking record
router.post('/capacity/track', [
  body('terminal_id').notEmpty().withMessage('Terminal ID is required'),
  body('recorded_occupancy').isInt({ min: 0 }).withMessage('Recorded occupancy must be a non-negative integer'),
  body('capacity_utilization').isFloat({ min: 0, max: 100 }).withMessage('Capacity utilization must be between 0 and 100'),
  body('recorded_by').notEmpty().withMessage('Recorded by is required'),
  body('notes').optional().isString(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await ParkingTerminalManagement.createCapacityTracking(req.body);
    
    if (result.success) {
      res.status(201).json({
        success: true,
        message: 'Capacity tracking record created successfully',
        data: { tracking_id: result.tracking_id },
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
      error: 'Failed to create capacity tracking record',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/parking/capacity/history/:terminal_id - Get capacity tracking history
router.get('/capacity/history/:terminal_id', [
  param('terminal_id').notEmpty().withMessage('Terminal ID is required'),
  query('date_from').optional().isISO8601(),
  query('date_to').optional().isISO8601(),
  query('limit').optional().isInt({ min: 1, max: 100 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await ParkingTerminalManagement.getCapacityTrackingHistory(req.params.terminal_id, req.query);
    
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
      error: 'Failed to fetch capacity tracking history',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// PUT /api/v1/parking/terminals/:id/occupancy - Update terminal occupancy
router.put('/terminals/:id/occupancy', [
  param('id').notEmpty().withMessage('Terminal ID is required'),
  body('current_occupancy').isInt({ min: 0 }).withMessage('Current occupancy must be a non-negative integer'),
  body('updated_by').notEmpty().withMessage('Updated by is required'),
  body('track_change').optional().isBoolean(),
  body('notes').optional().isString(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await ParkingTerminalManagement.updateTerminalOccupancy(req.params.id, req.body);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Terminal occupancy updated successfully',
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
      error: 'Failed to update terminal occupancy',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// =============================================
// TERMINAL ASSIGNMENT OPERATIONS
// =============================================

// POST /api/v1/parking/assignments/end - End assignment
router.post('/assignments/end', [
  body('assignment_id').notEmpty().withMessage('Assignment ID is required'),
  body('end_reason').notEmpty().withMessage('End reason is required'),
  body('performance_notes').optional().isString(),
  body('ended_by').notEmpty().withMessage('Ended by is required'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await ParkingTerminalManagement.endAssignment(req.body);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Assignment ended successfully',
        data: result.data,
        timestamp: req.timestamp
      });
    } else {
      res.status(400).json({
        success: false,
        error: result.error,
        timestamp: req.timestamp
      });
    }
  } catch (error) {
    res.status(500).json({
      success: false,
      error: 'Failed to end assignment',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// POST /api/v1/parking/assignments/transfer - Transfer assignment
router.post('/assignments/transfer', [
  body('assignment_id').notEmpty().withMessage('Assignment ID is required'),
  body('new_operator_id').notEmpty().withMessage('New operator ID is required'),
  body('transfer_reason').notEmpty().withMessage('Transfer reason is required'),
  body('effective_date').isISO8601().withMessage('Valid effective date is required'),
  body('transferred_by').notEmpty().withMessage('Transferred by is required'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await ParkingTerminalManagement.transferAssignment(req.body);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Assignment transferred successfully',
        data: result.data,
        timestamp: req.timestamp
      });
    } else {
      res.status(400).json({
        success: false,
        error: result.error,
        timestamp: req.timestamp
      });
    }
  } catch (error) {
    res.status(500).json({
      success: false,
      error: 'Failed to transfer assignment',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// POST /api/v1/parking/assignments/suspend - Suspend assignment
router.post('/assignments/suspend', [
  body('assignment_id').notEmpty().withMessage('Assignment ID is required'),
  body('suspension_reason').notEmpty().withMessage('Suspension reason is required'),
  body('suspension_duration').optional().isInt({ min: 1 }),
  body('notes').optional().isString(),
  body('suspended_by').notEmpty().withMessage('Suspended by is required'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await ParkingTerminalManagement.suspendAssignment(req.body);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Assignment suspended successfully',
        data: result.data,
        timestamp: req.timestamp
      });
    } else {
      res.status(400).json({
        success: false,
        error: result.error,
        timestamp: req.timestamp
      });
    }
  } catch (error) {
    res.status(500).json({
      success: false,
      error: 'Failed to suspend assignment',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// POST /api/v1/parking/assignments/reactivate - Reactivate assignment
router.post('/assignments/reactivate', [
  body('assignment_id').notEmpty().withMessage('Assignment ID is required'),
  body('reactivation_reason').notEmpty().withMessage('Reactivation reason is required'),
  body('notes').optional().isString(),
  body('reactivated_by').notEmpty().withMessage('Reactivated by is required'),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await ParkingTerminalManagement.reactivateAssignment(req.body);
    
    if (result.success) {
      res.json({
        success: true,
        message: 'Assignment reactivated successfully',
        data: result.data,
        timestamp: req.timestamp
      });
    } else {
      res.status(400).json({
        success: false,
        error: result.error,
        timestamp: req.timestamp
      });
    }
  } catch (error) {
    res.status(500).json({
      success: false,
      error: 'Failed to reactivate assignment',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// =============================================
// ANALYTICS AND REPORTS ENDPOINTS
// =============================================

// GET /api/v1/parking/analytics/assignments/stats - Get assignment statistics
router.get('/analytics/assignments/stats', [
  query('terminal_id').optional().isString(),
  query('operator_id').optional().isString(),
  query('date_from').optional().isISO8601(),
  query('date_to').optional().isISO8601(),
  query('assignment_type').optional().isIn(['permanent', 'temporary', 'shift_based', 'on_call']),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await ParkingTerminalManagement.getTerminalAssignmentStats(req.query);
    
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
      error: 'Failed to fetch assignment statistics',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/parking/reports/terminal-utilization - Get terminal utilization
router.get('/reports/terminal-utilization', [
  query('terminal_id').optional().isString(),
  query('date_from').optional().isISO8601(),
  query('date_to').optional().isISO8601(),
  query('group_by').optional().isIn(['hour', 'day', 'week', 'month']),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await ParkingTerminalManagement.getTerminalUtilization(req.query);
    
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
      error: 'Failed to fetch terminal utilization',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/parking/analytics/assignments/by-terminal - Get assignments by terminal
router.get('/analytics/assignments/by-terminal', [
  query('terminal_id').optional().isString(),
  query('date_from').optional().isISO8601(),
  query('date_to').optional().isISO8601(),
  query('status').optional().isIn(['active', 'inactive', 'suspended', 'completed']),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await ParkingTerminalManagement.getAssignmentsByTerminal(req.query);
    
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
      error: 'Failed to fetch assignments by terminal',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/parking/analytics/assignments/trends - Get assignment trends
router.get('/analytics/assignments/trends', [
  query('date_from').optional().isISO8601(),
  query('date_to').optional().isISO8601(),
  query('group_by').optional().isIn(['day', 'week', 'month']),
  query('assignment_type').optional().isIn(['permanent', 'temporary', 'shift_based', 'on_call']),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await ParkingTerminalManagement.getAssignmentTrends(req.query);
    
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
      error: 'Failed to fetch assignment trends',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/parking/analytics/occupancy/current - Get current terminal occupancy
router.get('/analytics/occupancy/current', [
  query('terminal_id').optional().isString(),
  query('include_capacity').optional().isBoolean(),
  query('include_utilization').optional().isBoolean(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await ParkingTerminalManagement.getCurrentTerminalOccupancy(req.query);
    
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
      error: 'Failed to fetch current terminal occupancy',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/parking/analytics/operators/frequent - Get frequent operators
router.get('/analytics/operators/frequent', [
  query('terminal_id').optional().isString(),
  query('date_from').optional().isISO8601(),
  query('date_to').optional().isISO8601(),
  query('limit').optional().isInt({ min: 1, max: 50 }),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await ParkingTerminalManagement.getFrequentOperators(req.query);
    
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
      error: 'Failed to fetch frequent operators',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});

// GET /api/v1/parking/analytics/operators/current - Get operator current assignment
router.get('/analytics/operators/current/:operator_id', [
  param('operator_id').notEmpty().withMessage('Operator ID is required'),
  query('include_terminal_details').optional().isBoolean(),
  handleValidationErrors
], async (req, res) => {
  try {
    const result = await ParkingTerminalManagement.getOperatorCurrentAssignment(req.params.operator_id, req.query);
    
    if (result.success) {
      res.json({
        success: true,
        data: result.data,
        timestamp: req.timestamp
      });
    } else {
      res.status(404).json({
        success: false,
        error: result.error,
        timestamp: req.timestamp
      });
    }
  } catch (error) {
    res.status(500).json({
      success: false,
      error: 'Failed to fetch operator current assignment',
      details: error.message,
      timestamp: req.timestamp
    });
  }
});



module.exports = router;