const Joi = require('joi');

// Generic validation middleware
const validate = (schema, property = 'body') => {
    return (req, res, next) => {
        const { error } = schema.validate(req[property]);
        if (error) {
            return res.status(400).json({
                error: 'Validation failed',
                details: error.details.map(detail => ({
                    field: detail.path.join('.'),
                    message: detail.message
                })),
                code: 'VALIDATION_ERROR'
            });
        }
        next();
    };
};

// Common validation schemas
const schemas = {
    // Pagination
    pagination: Joi.object({
        page: Joi.number().integer().min(1).default(1),
        limit: Joi.number().integer().min(1).max(100).default(10),
        sort: Joi.string().optional(),
        order: Joi.string().valid('asc', 'desc').default('desc')
    }),

    // ID parameter
    id: Joi.object({
        id: Joi.number().integer().positive().required()
    }),

    // Date range
    dateRange: Joi.object({
        start_date: Joi.date().optional(),
        end_date: Joi.date().min(Joi.ref('start_date')).optional()
    }),

    // Operator validation
    operator: Joi.object({
        first_name: Joi.string().min(2).max(50).required(),
        last_name: Joi.string().min(2).max(50).required(),
        middle_name: Joi.string().max(50).optional().allow(''),
        date_of_birth: Joi.date().max('now').required(),
        contact_number: Joi.string().pattern(/^[0-9+\-\s()]+$/).min(10).max(20).required(),
        email: Joi.string().email().optional().allow(''),
        address: Joi.string().min(10).max(200).required(),
        license_number: Joi.string().min(5).max(20).required(),
        license_expiry: Joi.date().min('now').required(),
        status: Joi.string().valid('active', 'inactive', 'suspended').default('active')
    }),

    // Vehicle validation
    vehicle: Joi.object({
        plate_number: Joi.string().min(6).max(10).required(),
        vehicle_type: Joi.string().valid('jeepney', 'bus', 'taxi', 'tricycle', 'uv_express').required(),
        make: Joi.string().min(2).max(50).required(),
        model: Joi.string().min(2).max(50).required(),
        year: Joi.number().integer().min(1990).max(new Date().getFullYear() + 1).required(),
        color: Joi.string().min(3).max(30).required(),
        engine_number: Joi.string().min(5).max(30).required(),
        chassis_number: Joi.string().min(5).max(30).required(),
        seating_capacity: Joi.number().integer().min(1).max(100).required(),
        operator_id: Joi.number().integer().positive().required(),
        status: Joi.string().valid('active', 'inactive', 'maintenance', 'retired').default('active')
    }),

    // Franchise validation
    franchise: Joi.object({
        franchise_number: Joi.string().min(5).max(20).required(),
        operator_id: Joi.number().integer().positive().required(),
        route_assigned: Joi.string().min(5).max(100).required(),
        issue_date: Joi.date().max('now').required(),
        expiry_date: Joi.date().min('now').required(),
        status: Joi.string().valid('valid', 'expired', 'suspended', 'revoked').default('valid')
    }),

    // Violation validation
    violation: Joi.object({
        ticket_number: Joi.string().min(5).max(20).required(),
        operator_id: Joi.number().integer().positive().required(),
        vehicle_id: Joi.number().integer().positive().optional(),
        violation_type: Joi.string().min(5).max(100).required(),
        violation_date: Joi.date().max('now').required(),
        location: Joi.string().min(5).max(200).required(),
        fine_amount: Joi.number().positive().precision(2).required(),
        officer_name: Joi.string().min(5).max(100).required(),
        description: Joi.string().max(500).optional().allow(''),
        settlement_status: Joi.string().valid('paid', 'unpaid', 'contested').default('unpaid')
    }),

    // Inspection validation
    inspection: Joi.object({
        vehicle_id: Joi.number().integer().positive().required(),
        inspection_date: Joi.date().required(),
        inspector_name: Joi.string().min(5).max(100).required(),
        inspection_type: Joi.string().valid('annual', 'renewal', 'special', 'random').required(),
        result: Joi.string().valid('passed', 'failed', 'conditional').required(),
        notes: Joi.string().max(500).optional().allow(''),
        next_inspection_date: Joi.date().min('now').optional(),
        certificate_number: Joi.string().max(50).optional().allow('')
    })
};

// Validation helpers
const validatePagination = validate(schemas.pagination, 'query');
const validateId = validate(schemas.id, 'params');
const validateDateRange = validate(schemas.dateRange, 'query');
const validateOperator = validate(schemas.operator);
const validateVehicle = validate(schemas.vehicle);
const validateFranchise = validate(schemas.franchise);
const validateViolation = validate(schemas.violation);
const validateInspection = validate(schemas.inspection);

module.exports = {
    validate,
    schemas,
    validatePagination,
    validateId,
    validateDateRange,
    validateOperator,
    validateVehicle,
    validateFranchise,
    validateViolation,
    validateInspection
};