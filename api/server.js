require('dotenv').config();
const express = require('express');
const cors = require('cors');
const helmet = require('helmet');
const rateLimit = require('express-rate-limit');
const compression = require('compression');
const morgan = require('morgan');
const { testConnection } = require('./config/database');

// Import route modules
const puvRoutes = require('./routes/puvDatabase');
const franchiseRoutes = require('./routes/franchiseManagement');
const violationRoutes = require('./routes/trafficViolationTicketing');
const inspectionRoutes = require('./routes/vehicleInspectionRegistration');
const parkingRoutes = require('./routes/parkingTerminalManagement');
const ptsmdAnalyticsRoutes = require('./routes/ptsmdAnalytics');

const app = express();
const PORT = process.env.PORT || 3000;
const API_PREFIX = process.env.API_PREFIX || '/api/v1';

// =============================================
// MIDDLEWARE CONFIGURATION
// =============================================

// Security middleware
app.use(helmet({
  contentSecurityPolicy: {
    directives: {
      defaultSrc: ["'self'"],
      styleSrc: ["'self'", "'unsafe-inline'"],
      scriptSrc: ["'self'"],
      imgSrc: ["'self'", "data:", "https:"],
      connectSrc: ["'self'", "http://localhost:3000", "http://127.0.0.1:3000"],
    },
  },
  crossOriginEmbedderPolicy: false
}));

// CORS configuration
const corsOptions = {
  origin: process.env.CORS_ORIGIN ? process.env.CORS_ORIGIN.split(',') : ['http://localhost:3000', 'http://localhost:3001', 'http://localhost:8000'],
  credentials: true,
  optionsSuccessStatus: 200,
  methods: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
  allowedHeaders: ['Content-Type', 'Authorization', 'X-Requested-With']
};
app.use(cors(corsOptions));

// Rate limiting
const limiter = rateLimit({
  windowMs: parseInt(process.env.RATE_LIMIT_WINDOW_MS) || 15 * 60 * 1000, // 15 minutes
  max: parseInt(process.env.RATE_LIMIT_MAX_REQUESTS) || 100, // limit each IP to 100 requests per windowMs
  message: {
    error: 'Too many requests from this IP, please try again later.',
    retryAfter: Math.ceil((parseInt(process.env.RATE_LIMIT_WINDOW_MS) || 15 * 60 * 1000) / 1000)
  },
  standardHeaders: true,
  legacyHeaders: false
});
app.use(limiter);

// Compression middleware
app.use(compression());

// Logging middleware
if (process.env.NODE_ENV === 'development') {
  app.use(morgan('dev'));
} else {
  app.use(morgan('combined'));
}

// Body parsing middleware
app.use(express.json({ limit: '10mb' }));
app.use(express.urlencoded({ extended: true, limit: '10mb' }));

// Request timestamp middleware
app.use((req, res, next) => {
  req.timestamp = new Date().toISOString();
  next();
});

// =============================================
// HEALTH CHECK AND INFO ENDPOINTS
// =============================================

// Health check endpoint
app.get('/health', async (req, res) => {
  try {
    const dbStatus = await testConnection();
    res.status(200).json({
      status: 'OK',
      timestamp: req.timestamp,
      uptime: process.uptime(),
      environment: process.env.NODE_ENV || 'development',
      database: dbStatus.success ? 'Connected' : 'Disconnected',
      version: process.env.API_VERSION || '1.0.0'
    });
  } catch (error) {
    res.status(503).json({
      status: 'Service Unavailable',
      timestamp: req.timestamp,
      error: 'Database connection failed'
    });
  }
});

// API info endpoint
app.get('/info', (req, res) => {
  res.json({
    name: 'Transport and Mobility Management System API',
    version: process.env.API_VERSION || '1.0.0',
    description: 'RESTful API for managing transport and mobility operations',
    environment: process.env.NODE_ENV || 'development',
    endpoints: {
      health: '/health',
      puvDatabase: `${API_PREFIX}/puv`,
      franchiseManagement: `${API_PREFIX}/franchise`,
      trafficViolationTicketing: `${API_PREFIX}/violations`,
      vehicleInspectionRegistration: `${API_PREFIX}/inspections`,
      parkingTerminalManagement: `${API_PREFIX}/parking`,
      ptsmdAnalytics: `${API_PREFIX}/analytics`
    },
    documentation: 'See README.md for detailed API documentation'
  });
});

// =============================================
// API ROUTES
// =============================================

// Mount API routes with prefix
app.use(`${API_PREFIX}/puv`, puvRoutes);
app.use(`${API_PREFIX}/franchise`, franchiseRoutes);
app.use(`${API_PREFIX}/violations`, violationRoutes);
app.use(`${API_PREFIX}/inspections`, inspectionRoutes);
app.use(`${API_PREFIX}/parking`, parkingRoutes);
app.use(`${API_PREFIX}/analytics`, ptsmdAnalyticsRoutes);

// Root endpoint
app.get('/', (req, res) => {
  res.json({
    message: 'Welcome to Transport and Mobility Management System API',
    version: process.env.API_VERSION || '1.0.0',
    timestamp: req.timestamp,
    endpoints: {
      info: '/info',
      health: '/health',
      api: API_PREFIX
    }
  });
});

// =============================================
// ERROR HANDLING MIDDLEWARE
// =============================================

// 404 handler
app.use('*', (req, res) => {
  res.status(404).json({
    error: 'Endpoint not found',
    message: `The requested endpoint ${req.originalUrl} does not exist`,
    timestamp: req.timestamp,
    availableEndpoints: {
      info: '/info',
      health: '/health',
      api: API_PREFIX
    }
  });
});

// Global error handler
app.use((error, req, res, next) => {
  console.error('Global Error Handler:', {
    timestamp: req.timestamp,
    method: req.method,
    url: req.originalUrl,
    error: error.message,
    stack: process.env.NODE_ENV === 'development' ? error.stack : undefined
  });

  // Handle specific error types
  if (error.type === 'entity.parse.failed') {
    return res.status(400).json({
      error: 'Invalid JSON',
      message: 'Request body contains invalid JSON',
      timestamp: req.timestamp
    });
  }

  if (error.type === 'entity.too.large') {
    return res.status(413).json({
      error: 'Payload too large',
      message: 'Request body exceeds size limit',
      timestamp: req.timestamp
    });
  }

  // Default error response
  const statusCode = error.statusCode || error.status || 500;
  res.status(statusCode).json({
    error: statusCode === 500 ? 'Internal Server Error' : error.message,
    message: statusCode === 500 ? 'An unexpected error occurred' : error.message,
    timestamp: req.timestamp,
    ...(process.env.NODE_ENV === 'development' && { stack: error.stack })
  });
});

// =============================================
// SERVER STARTUP
// =============================================

// Graceful shutdown handler
const gracefulShutdown = (signal) => {
  console.log(`\n${signal} received. Starting graceful shutdown...`);
  
  server.close((err) => {
    if (err) {
      console.error('Error during server shutdown:', err);
      process.exit(1);
    }
    
    console.log('Server closed successfully.');
    process.exit(0);
  });
  
  // Force shutdown after 10 seconds
  setTimeout(() => {
    console.error('Forced shutdown after timeout');
    process.exit(1);
  }, 10000);
};

// Start server
const server = app.listen(PORT, '0.0.0.0', async () => {
  console.log('='.repeat(60));
  console.log('🚀 TRANSPORT AND MOBILITY MANAGEMENT SYSTEM API');
  console.log('='.repeat(60));
  console.log(`📍 Server running on: http://localhost:${PORT}`);
  console.log(`🌍 Environment: ${process.env.NODE_ENV || 'development'}`);
  console.log(`📊 API Version: ${process.env.API_VERSION || '1.0.0'}`);
  console.log(`🔗 API Base URL: http://localhost:${PORT}${API_PREFIX}`);
  console.log(`📋 Health Check: http://localhost:${PORT}/health`);
  console.log(`📖 API Info: http://localhost:${PORT}/info`);
  console.log('='.repeat(60));
  
  // Test database connection on startup
  try {
    const dbStatus = await testConnection();
    if (dbStatus.success) {
      console.log('✅ Database connection: SUCCESS');
    } else {
      console.log('❌ Database connection: FAILED');
      console.log('⚠️  Server started but database is not available');
    }
  } catch (error) {
    console.log('❌ Database connection: ERROR');
    console.log('⚠️  Server started but database connection failed:', error.message);
  }
  
  console.log('='.repeat(60));
  console.log('📚 Available Endpoints:');
  console.log(`   • PUV Database: ${API_PREFIX}/puv`);
  console.log(`   • Franchise Management: ${API_PREFIX}/franchise`);
  console.log(`   • Traffic Violations: ${API_PREFIX}/violations`);
  console.log(`   • Vehicle Inspections: ${API_PREFIX}/inspections`);
  console.log(`   • Parking & Terminals: ${API_PREFIX}/parking`);
  console.log('='.repeat(60));
  console.log('🎯 Ready to accept requests!');
  console.log('   Press Ctrl+C to stop the server');
  console.log('='.repeat(60));
});

// Handle graceful shutdown
process.on('SIGTERM', () => gracefulShutdown('SIGTERM'));
process.on('SIGINT', () => gracefulShutdown('SIGINT'));

// Handle uncaught exceptions
process.on('uncaughtException', (error) => {
  console.error('Uncaught Exception:', error);
  gracefulShutdown('UNCAUGHT_EXCEPTION');
});

// Handle unhandled promise rejections
process.on('unhandledRejection', (reason, promise) => {
  console.error('Unhandled Rejection at:', promise, 'reason:', reason);
  gracefulShutdown('UNHANDLED_REJECTION');
});

module.exports = app;