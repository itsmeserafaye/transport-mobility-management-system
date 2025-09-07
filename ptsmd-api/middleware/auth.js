const jwt = require('jsonwebtoken');
const { executeQuery } = require('../config/database');

// JWT Authentication middleware
const authenticateToken = async (req, res, next) => {
    try {
        const authHeader = req.headers['authorization'];
        const token = authHeader && authHeader.split(' ')[1]; // Bearer TOKEN

        if (!token) {
            return res.status(401).json({
                success: false,
                message: 'Access token is required'
            });
        }

        jwt.verify(token, process.env.JWT_SECRET || 'ptsmd_secret_key', async (err, decoded) => {
            if (err) {
                return res.status(403).json({
                    success: false,
                    message: 'Invalid or expired token'
                });
            }

            // Optional: Verify user still exists and is active
            if (decoded.userId) {
                const userQuery = 'SELECT id, username, role, status FROM users WHERE id = ? AND status = "active"';
                const userResult = await executeQuery(userQuery, [decoded.userId]);
                
                if (!userResult.success || userResult.data.length === 0) {
                    return res.status(403).json({
                        success: false,
                        message: 'User account is inactive or not found'
                    });
                }
                
                req.user = userResult.data[0];
            } else {
                req.user = decoded;
            }

            next();
        });
    } catch (error) {
        res.status(500).json({
            success: false,
            message: 'Authentication error',
            error: error.message
        });
    }
};

// Optional authentication (for public endpoints that can benefit from user context)
const optionalAuth = async (req, res, next) => {
    try {
        const authHeader = req.headers['authorization'];
        const token = authHeader && authHeader.split(' ')[1];

        if (!token) {
            req.user = null;
            return next();
        }

        jwt.verify(token, process.env.JWT_SECRET || 'ptsmd_secret_key', async (err, decoded) => {
            if (err) {
                req.user = null;
            } else {
                req.user = decoded;
            }
            next();
        });
    } catch (error) {
        req.user = null;
        next();
    }
};

// Role-based authorization middleware
const requireRole = (roles) => {
    return (req, res, next) => {
        if (!req.user) {
            return res.status(401).json({
                success: false,
                message: 'Authentication required'
            });
        }

        const userRole = req.user.role || req.user.user_type;
        const allowedRoles = Array.isArray(roles) ? roles : [roles];

        if (!allowedRoles.includes(userRole)) {
            return res.status(403).json({
                success: false,
                message: 'Insufficient permissions'
            });
        }

        next();
    };
};

// API Key authentication (for external integrations)
const authenticateApiKey = async (req, res, next) => {
    try {
        const apiKey = req.headers['x-api-key'];

        if (!apiKey) {
            return res.status(401).json({
                success: false,
                message: 'API key is required'
            });
        }

        // Check if API key exists and is active
        const keyQuery = 'SELECT * FROM api_keys WHERE key_value = ? AND status = "active" AND (expires_at IS NULL OR expires_at > NOW())';
        const keyResult = await executeQuery(keyQuery, [apiKey]);

        if (!keyResult.success || keyResult.data.length === 0) {
            return res.status(403).json({
                success: false,
                message: 'Invalid or expired API key'
            });
        }

        const apiKeyData = keyResult.data[0];
        
        // Update last used timestamp
        await executeQuery(
            'UPDATE api_keys SET last_used_at = NOW(), usage_count = usage_count + 1 WHERE id = ?',
            [apiKeyData.id]
        );

        req.apiKey = apiKeyData;
        next();
    } catch (error) {
        res.status(500).json({
            success: false,
            message: 'API key authentication error',
            error: error.message
        });
    }
};

// Generate JWT token
const generateToken = (payload, expiresIn = '24h') => {
    return jwt.sign(payload, process.env.JWT_SECRET || 'ptsmd_secret_key', {
        expiresIn
    });
};

// Verify JWT token
const verifyToken = (token) => {
    try {
        return jwt.verify(token, process.env.JWT_SECRET || 'ptsmd_secret_key');
    } catch (error) {
        return null;
    }
};

module.exports = {
    authenticateToken,
    optionalAuth,
    requireRole,
    authenticateApiKey,
    generateToken,
    verifyToken
};