const express = require('express');
const cors = require('cors');
const path = require('path');
require('dotenv').config();

const app = express();
const PORT = process.env.PTSMD_PORT || 3001;

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// Import routes
const franchiseRoutes = require('./routes/franchiseManagement');
const puvRoutes = require('./routes/puvDatabase');
const trafficViolationRoutes = require('./routes/trafficViolation');
const parkingTerminalRoutes = require('./routes/parkingTerminal');
const vehicleInspectionRoutes = require('./routes/vehicleInspection');
const analyticsRoutes = require('./routes/analytics'); 

// API Routes
app.use('/api/franchise-management', franchiseRoutes);
app.use('/api/puv-database', puvRoutes);
app.use('/api/traffic-violation', trafficViolationRoutes);
app.use('/api/parking-terminal', parkingTerminalRoutes);
app.use('/api/vehicle-inspection', vehicleInspectionRoutes);
app.use('/api/analytics', analyticsRoutes);

// Health check endpoint
app.get('/api/health', (req, res) => {
    res.json({
        status: 'OK',
        message: 'PTSMD API Server is running',
        timestamp: new Date().toISOString(),
        port: PORT
    });
});

// Root endpoint
app.get('/', (req, res) => {
    res.json({
        message: 'PTSMD API Server',
        version: '1.0.0',
        endpoints: {
            health: '/api/health',
            franchise: '/api/franchise',
            puv: '/api/puv',
            trafficViolation: '/api/traffic-violation',
            parkingTerminal: '/api/parking-terminal',
            vehicleInspection: '/api/vehicle-inspection',
            analytics: '/api/analytics'
        }
    });
});

// Error handling middleware
app.use((err, req, res, next) => {
    console.error('Error:', err.stack);
    res.status(500).json({
        error: 'Internal Server Error',
        message: err.message,
        timestamp: new Date().toISOString()
    });
});

// 404 handler
app.use('*', (req, res) => {
    res.status(404).json({
        error: 'Not Found',
        message: `Route ${req.originalUrl} not found`,
        timestamp: new Date().toISOString()
    });
});

// Start server
app.listen(PORT, () => {
    console.log(`PTSMD API Server running on port ${PORT}`);
    console.log(`Health check: http://localhost:${PORT}/api/health`);
    console.log(`API Documentation: http://localhost:${PORT}`);
});

module.exports = app;