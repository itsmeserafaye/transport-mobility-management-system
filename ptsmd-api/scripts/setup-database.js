const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');

// Database configuration
const dbConfig = {
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || '',
    multipleStatements: true
};

async function setupDatabase() {
    let connection;
    
    try {
        console.log('Connecting to MySQL server...');
        connection = await mysql.createConnection(dbConfig);
        
        console.log('Creating database...');
        await connection.query('CREATE DATABASE IF NOT EXISTS transport_mobility_db');
        await connection.query('USE transport_mobility_db');
        
        console.log('Creating tables...');
        
        // Create tables one by one
        const tables = [
            `CREATE TABLE IF NOT EXISTS franchise_applications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                applicant_name VARCHAR(255) NOT NULL,
                business_name VARCHAR(255) NOT NULL,
                contact_number VARCHAR(20) NOT NULL,
                email VARCHAR(255) NOT NULL,
                address TEXT NOT NULL,
                route_id INT,
                vehicle_capacity INT NOT NULL,
                application_type ENUM('new', 'renewal', 'transfer') DEFAULT 'new',
                status ENUM('pending', 'under_review', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
                documents JSON,
                remarks TEXT,
                submitted_date DATE,
                processed_date DATE,
                processed_by VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )`,
            
            `CREATE TABLE IF NOT EXISTS routes (
                id INT AUTO_INCREMENT PRIMARY KEY,
                route_name VARCHAR(255) NOT NULL,
                origin VARCHAR(255) NOT NULL,
                destination VARCHAR(255) NOT NULL,
                distance DECIMAL(10,2),
                fare DECIMAL(10,2),
                status ENUM('active', 'inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )`,
            
            `CREATE TABLE IF NOT EXISTS operators (
                id INT AUTO_INCREMENT PRIMARY KEY,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                contact_number VARCHAR(20) NOT NULL,
                email VARCHAR(255),
                address TEXT,
                license_number VARCHAR(50),
                license_expiry DATE,
                status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )`,
            
            `CREATE TABLE IF NOT EXISTS vehicles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                plate_number VARCHAR(20) UNIQUE NOT NULL,
                vehicle_type ENUM('jeepney', 'bus', 'taxi', 'tricycle', 'van') NOT NULL,
                make VARCHAR(100) NOT NULL,
                model VARCHAR(100) NOT NULL,
                year INT NOT NULL,
                color VARCHAR(50),
                engine_number VARCHAR(100),
                chassis_number VARCHAR(100),
                seating_capacity INT NOT NULL,
                operator_id INT,
                route_id INT,
                registration_date DATE,
                expiry_date DATE,
                status ENUM('active', 'inactive', 'suspended', 'expired') DEFAULT 'active',
                insurance_policy VARCHAR(100),
                insurance_expiry DATE,
                last_inspection_date DATE,
                next_inspection_date DATE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (operator_id) REFERENCES operators(id),
                FOREIGN KEY (route_id) REFERENCES routes(id)
            )`,
            
            `CREATE TABLE IF NOT EXISTS traffic_violations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                vehicle_id INT,
                plate_number VARCHAR(20) NOT NULL,
                violation_type VARCHAR(255) NOT NULL,
                violation_date DATE NOT NULL,
                violation_time TIME,
                location TEXT NOT NULL,
                officer_name VARCHAR(255) NOT NULL,
                fine_amount DECIMAL(10,2) NOT NULL,
                status ENUM('pending', 'paid', 'contested', 'dismissed') DEFAULT 'pending',
                payment_date DATE,
                payment_method VARCHAR(50),
                remarks TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )`,
            
            `CREATE TABLE IF NOT EXISTS terminals (
                id INT AUTO_INCREMENT PRIMARY KEY,
                terminal_name VARCHAR(255) NOT NULL,
                location VARCHAR(255) NOT NULL,
                address TEXT NOT NULL,
                capacity INT NOT NULL,
                operating_hours VARCHAR(100),
                contact_person VARCHAR(255),
                contact_number VARCHAR(20),
                facilities TEXT,
                status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )`,
            
            `CREATE TABLE IF NOT EXISTS terminal_assignments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                terminal_id INT NOT NULL,
                vehicle_id INT NOT NULL,
                assigned_date DATE NOT NULL,
                shift_schedule VARCHAR(100),
                remarks TEXT,
                status ENUM('active', 'inactive', 'temporary') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )`,
            
            `CREATE TABLE IF NOT EXISTS vehicle_inspections (
                id INT AUTO_INCREMENT PRIMARY KEY,
                vehicle_id INT NOT NULL,
                inspection_date DATE NOT NULL,
                next_inspection_date DATE,
                inspector_name VARCHAR(255) NOT NULL,
                inspection_type ENUM('regular', 'special', 'renewal') DEFAULT 'regular',
                location VARCHAR(255),
                overall_result ENUM('passed', 'failed', 'conditional') DEFAULT NULL,
                completion_date DATE,
                remarks TEXT,
                status ENUM('scheduled', 'in_progress', 'completed', 'failed', 'cancelled') DEFAULT 'scheduled',
                inspection_time TIME,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )`
        ];
        
        for (const table of tables) {
            try {
                await connection.query(table);
                console.log('✓ Table created successfully');
            } catch (err) {
                console.error('✗ Error creating table:', err.message);
            }
        }
        
        console.log('\nInserting sample data...');
        
        // Insert sample data
        await connection.query(`INSERT IGNORE INTO routes (route_name, origin, destination, distance, fare) VALUES
            ('Route 1', 'City Center', 'Airport', 25.5, 15.00),
            ('Route 2', 'Downtown', 'University', 12.3, 8.50),
            ('Route 3', 'Mall', 'Residential Area', 18.7, 12.00)`);
            
        await connection.query(`INSERT IGNORE INTO operators (first_name, last_name, contact_number, email, license_number) VALUES
            ('Juan', 'Dela Cruz', '09123456789', 'juan.delacruz@email.com', 'LIC123456'),
            ('Maria', 'Santos', '09234567890', 'maria.santos@email.com', 'LIC234567'),
            ('Pedro', 'Garcia', '09345678901', 'pedro.garcia@email.com', 'LIC345678')`);
            
        await connection.query(`INSERT IGNORE INTO terminals (terminal_name, location, address, capacity) VALUES
            ('Central Terminal', 'City Center', '123 Main Street, City Center', 50),
            ('North Terminal', 'North District', '456 North Avenue, North District', 30),
            ('South Terminal', 'South District', '789 South Road, South District', 40)`);
        
        console.log('✅ Database setup completed successfully!');
        console.log('📊 Tables created and sample data inserted');
        
    } catch (error) {
        console.error('❌ Database setup failed:', error.message);
        process.exit(1);
    } finally {
        if (connection) {
            await connection.end();
        }
    }
}

// Run setup if this file is executed directly
if (require.main === module) {
    setupDatabase();
}

module.exports = setupDatabase;