const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');
require('dotenv').config();

const Database = require('./config/database');

// Import routes
const puvDatabaseRoutes = require('./routes/puv-database');
const franchiseRoutes = require('./routes/franchise');
const trafficViolationRoutes = require('./routes/traffic-violation');
const vehicleInspectionRoutes = require('./routes/vehicle-inspection');
const parkingTerminalRoutes = require('./routes/parking-terminal');

const app = express();
const PORT = process.env.PORT || 4000;
const API_PREFIX = process.env.API_PREFIX || '/api';
const API_VERSION = process.env.API_VERSION || 'v1';

// Initialize database
const database = new Database();

// Middleware
app.use(helmet());
app.use(cors({
    origin: process.env.CORS_ORIGIN || '*',
    credentials: true
}));

// Rate limiting
const limiter = rateLimit({
    windowMs: parseInt(process.env.RATE_LIMIT_WINDOW_MS) || 15 * 60 * 1000, // 15 minutes
    max: parseInt(process.env.RATE_LIMIT_MAX_REQUESTS) || 100, // limit each IP to 100 requests per windowMs
    message: {
        error: 'Too many requests from this IP, please try again later.',
        code: 'RATE_LIMIT_EXCEEDED'
    }
});
app.use(limiter);

app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));

// Make database available to routes
app.use((req, res, next) => {
    req.db = database;
    next();
});

// Health check endpoint
app.get('/health', async (req, res) => {
    try {
        const dbStatus = await database.testConnection();
        res.json({
            status: 'OK',
            timestamp: new Date().toISOString(),
            service: 'TPRS API',
            version: '1.0.0',
            database: dbStatus ? 'Connected' : 'Disconnected'
        });
    } catch (error) {
        res.status(500).json({
            status: 'ERROR',
            timestamp: new Date().toISOString(),
            service: 'TPRS API',
            error: error.message
        });
    }
});

// API Routes
const apiRouter = express.Router();

// API base endpoint
apiRouter.get('/', (req, res) => {
    res.json({
        message: 'TPRS API v1',
        version: '1.0.0',
        description: 'Transport Public Regulation System API',
        endpoints: {
            'puv-database': `${API_PREFIX}/${API_VERSION}/puv-database`,
            'franchise': `${API_PREFIX}/${API_VERSION}/franchise`,
            'traffic-violation': `${API_PREFIX}/${API_VERSION}/traffic-violation`,
            'vehicle-inspection': `${API_PREFIX}/${API_VERSION}/vehicle-inspection`,
            'parking-terminal': `${API_PREFIX}/${API_VERSION}/parking-terminal`
        },
        documentation: 'Available endpoints for each module can be accessed via GET requests'
    });
});

// Mount module routes
apiRouter.use('/puv-database', puvDatabaseRoutes);
apiRouter.use('/franchise', franchiseRoutes);
apiRouter.use('/traffic-violation', trafficViolationRoutes);
apiRouter.use('/vehicle-inspection', vehicleInspectionRoutes);
apiRouter.use('/parking-terminal', parkingTerminalRoutes);

// Mount API router
app.use(`${API_PREFIX}/${API_VERSION}`, apiRouter);

// Root endpoint
app.get('/', (req, res) => {
    res.json({
        message: 'TPRS API Server',
        version: '1.0.0',
        description: 'Transport Public Regulation System API',
        endpoints: {
            health: '/health',
            api: `${API_PREFIX}/${API_VERSION}`,
            modules: [
                'puv-database',
                'franchise',
                'traffic-violation',
                'vehicle-inspection',
                'parking-terminal'
            ]
        }
    });
});

// 404 handler
app.use('*', (req, res) => {
    res.status(404).json({
        error: 'Endpoint not found',
        message: `The requested endpoint ${req.originalUrl} does not exist`,
        code: 'ENDPOINT_NOT_FOUND'
    });
});

// Global error handler
app.use((error, req, res, next) => {
    console.error('Global error handler:', error);
    
    res.status(error.status || 500).json({
        error: error.message || 'Internal Server Error',
        code: error.code || 'INTERNAL_ERROR',
        timestamp: new Date().toISOString()
    });
});

// Graceful shutdown
process.on('SIGTERM', async () => {
    console.log('SIGTERM received, shutting down gracefully');
    await database.close();
    process.exit(0);
});

process.on('SIGINT', async () => {
    console.log('SIGINT received, shutting down gracefully');
    await database.close();
    process.exit(0);
});

// Start server
app.listen(PORT, async () => {
    console.log(`🚀 TPRS API Server running on port ${PORT}`);
    console.log(`📍 API Base URL: http://localhost:${PORT}${API_PREFIX}/${API_VERSION}`);
    console.log(`🏥 Health Check: http://localhost:${PORT}/health`);
    
    // Test database connection
    await database.testConnection();
});