const mysql = require('mysql2/promise');
require('dotenv').config();

// Create connection pool
const pool = mysql.createPool({
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || '',
    database: process.env.DB_NAME || 'transport_management',
    port: process.env.DB_PORT || 3306,
    connectionLimit: parseInt(process.env.DB_CONNECTION_LIMIT) || 10,
    acquireTimeout: parseInt(process.env.DB_ACQUIRE_TIMEOUT) || 60000,
    charset: 'utf8mb4',
    ssl: false
});

// Test database connection
const testConnection = async () => {
    try {
        const connection = await pool.getConnection();
        console.log('✅ PTSMD Database connected successfully');
        connection.release();
        return true;
    } catch (error) {
        console.error('❌ PTSMD Database connection failed:', error.message);
        return false;
    }
};

// Execute query with error handling
const executeQuery = async (query, params = []) => {
    try {
        const [rows] = await pool.execute(query, params);
        return {
            success: true,
            data: rows,
            error: null
        };
    } catch (error) {
        console.error('Database query error:', error.message);
        return {
            success: false,
            data: null,
            error: error.message
        };
    }
};

// Get database statistics
const getStats = async () => {
    try {
        const [rows] = await pool.execute('SELECT COUNT(*) as total_connections FROM information_schema.processlist WHERE db = ?', [dbConfig.database]);
        return {
            activeConnections: rows[0].total_connections,
            poolConfig: {
                connectionLimit: dbConfig.connectionLimit,
                queueLimit: dbConfig.queueLimit
            }
        };
    } catch (error) {
        return {
            error: error.message
        };
    }
};

// Close all connections
const closePool = async () => {
    try {
        await pool.end();
        console.log('✅ PTSMD Database pool closed');
    } catch (error) {
        console.error('❌ Error closing database pool:', error.message);
    }
};

module.exports = {
    pool,
    testConnection,
    executeQuery,
    getStats,
    closePool
};