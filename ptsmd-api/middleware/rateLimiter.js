const rateLimit = require('express-rate-limit');
const { executeQuery } = require('../config/database');

// General API rate limiter
const generalLimiter = rateLimit({
    windowMs: 15 * 60 * 1000, // 15 minutes
    max: 100, // Limit each IP to 100 requests per windowMs
    message: {
        success: false,
        message: 'Too many requests from this IP, please try again later.',
        retryAfter: '15 minutes'
    },
    standardHeaders: true, // Return rate limit info in the `RateLimit-*` headers
    legacyHeaders: false, // Disable the `X-RateLimit-*` headers
    skip: (req) => {
        // Skip rate limiting for health checks
        return req.path === '/health' || req.path === '/';
    }
});

// Strict rate limiter for authentication endpoints
const authLimiter = rateLimit({
    windowMs: 15 * 60 * 1000, // 15 minutes
    max: 5, // Limit each IP to 5 login attempts per windowMs
    message: {
        success: false,
        message: 'Too many authentication attempts, please try again later.',
        retryAfter: '15 minutes'
    },
    standardHeaders: true,
    legacyHeaders: false,
    skipSuccessfulRequests: true // Don't count successful requests
});

// Create operation rate limiter
const createLimiter = rateLimit({
    windowMs: 60 * 1000, // 1 minute
    max: 10, // Limit each IP to 10 create operations per minute
    message: {
        success: false,
        message: 'Too many create operations, please slow down.',
        retryAfter: '1 minute'
    },
    standardHeaders: true,
    legacyHeaders: false
});

// Update operation rate limiter
const updateLimiter = rateLimit({
    windowMs: 60 * 1000, // 1 minute
    max: 20, // Limit each IP to 20 update operations per minute
    message: {
        success: false,
        message: 'Too many update operations, please slow down.',
        retryAfter: '1 minute'
    },
    standardHeaders: true,
    legacyHeaders: false
});

// Search operation rate limiter
const searchLimiter = rateLimit({
    windowMs: 60 * 1000, // 1 minute
    max: 30, // Limit each IP to 30 search operations per minute
    message: {
        success: false,
        message: 'Too many search operations, please slow down.',
        retryAfter: '1 minute'
    },
    standardHeaders: true,
    legacyHeaders: false
});

// Custom rate limiter with database tracking
const createCustomLimiter = (options = {}) => {
    const {
        windowMs = 15 * 60 * 1000,
        max = 100,
        message = 'Rate limit exceeded',
        keyGenerator = (req) => req.ip,
        skipSuccessfulRequests = false,
        skipFailedRequests = false
    } = options;

    return async (req, res, next) => {
        try {
            const key = keyGenerator(req);
            const now = new Date();
            const windowStart = new Date(now.getTime() - windowMs);

            // Clean up old entries
            await executeQuery(
                'DELETE FROM rate_limits WHERE created_at < ?',
                [windowStart]
            );

            // Count current requests in window
            const countResult = await executeQuery(
                'SELECT COUNT(*) as count FROM rate_limits WHERE identifier = ? AND created_at >= ?',
                [key, windowStart]
            );

            const currentCount = countResult.success ? countResult.data[0].count : 0;

            if (currentCount >= max) {
                return res.status(429).json({
                    success: false,
                    message: typeof message === 'string' ? message : message.message,
                    retryAfter: Math.ceil(windowMs / 1000)
                });
            }

            // Record this request
            await executeQuery(
                'INSERT INTO rate_limits (identifier, endpoint, created_at) VALUES (?, ?, ?)',
                [key, req.path, now]
            );

            // Add rate limit headers
            res.set({
                'X-RateLimit-Limit': max,
                'X-RateLimit-Remaining': Math.max(0, max - currentCount - 1),
                'X-RateLimit-Reset': new Date(now.getTime() + windowMs)
            });

            next();
        } catch (error) {
            // If rate limiting fails, log error but don't block request
            console.error('Rate limiting error:', error);
            next();
        }
    };
};

// IP-based rate limiter with whitelist
const createIpLimiter = (options = {}) => {
    const {
        whitelist = [],
        ...rateLimitOptions
    } = options;

    return rateLimit({
        ...rateLimitOptions,
        skip: (req) => {
            // Skip rate limiting for whitelisted IPs
            const clientIp = req.ip || req.connection.remoteAddress;
            return whitelist.includes(clientIp);
        }
    });
};

// User-based rate limiter (requires authentication)
const createUserLimiter = (options = {}) => {
    const {
        windowMs = 15 * 60 * 1000,
        max = 200,
        message = 'User rate limit exceeded'
    } = options;

    return async (req, res, next) => {
        try {
            if (!req.user || !req.user.id) {
                return next(); // Skip if no authenticated user
            }

            const userId = req.user.id;
            const now = new Date();
            const windowStart = new Date(now.getTime() - windowMs);

            // Count user requests in window
            const countResult = await executeQuery(
                'SELECT COUNT(*) as count FROM user_rate_limits WHERE user_id = ? AND created_at >= ?',
                [userId, windowStart]
            );

            const currentCount = countResult.success ? countResult.data[0].count : 0;

            if (currentCount >= max) {
                return res.status(429).json({
                    success: false,
                    message,
                    retryAfter: Math.ceil(windowMs / 1000)
                });
            }

            // Record this request
            await executeQuery(
                'INSERT INTO user_rate_limits (user_id, endpoint, created_at) VALUES (?, ?, ?)',
                [userId, req.path, now]
            );

            // Clean up old entries (do this occasionally)
            if (Math.random() < 0.1) { // 10% chance
                await executeQuery(
                    'DELETE FROM user_rate_limits WHERE created_at < ?',
                    [windowStart]
                );
            }

            next();
        } catch (error) {
            console.error('User rate limiting error:', error);
            next();
        }
    };
};

// Endpoint-specific rate limiters
const endpointLimiters = {
    // Authentication endpoints
    '/auth/login': authLimiter,
    '/auth/register': authLimiter,
    '/auth/forgot-password': authLimiter,
    
    // Create operations
    'POST /api/franchise-management/applications': createLimiter,
    'POST /api/puv-database/vehicles': createLimiter,
    'POST /api/traffic-violation/violations': createLimiter,
    'POST /api/parking-terminal/terminals': createLimiter,
    'POST /api/vehicle-inspection/inspections': createLimiter,
    
    // Search operations
    'GET /api/*/search': searchLimiter,
    'GET /api/*/statistics': searchLimiter
};

// Middleware to apply endpoint-specific rate limiting
const applyEndpointLimiter = (req, res, next) => {
    const method = req.method;
    const path = req.path;
    const key = `${method} ${path}`;
    
    // Check for exact match
    if (endpointLimiters[key]) {
        return endpointLimiters[key](req, res, next);
    }
    
    // Check for pattern match
    for (const [pattern, limiter] of Object.entries(endpointLimiters)) {
        if (pattern.includes('*') && new RegExp(pattern.replace('*', '.*')).test(key)) {
            return limiter(req, res, next);
        }
    }
    
    // Apply general limiter if no specific limiter found
    return generalLimiter(req, res, next);
};

module.exports = {
    generalLimiter,
    authLimiter,
    createLimiter,
    updateLimiter,
    searchLimiter,
    createCustomLimiter,
    createIpLimiter,
    createUserLimiter,
    applyEndpointLimiter
};