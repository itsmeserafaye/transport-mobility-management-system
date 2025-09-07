const mysql = require('mysql2/promise');
require('dotenv').config();

async function checkDatabaseSchema() {
    try {
        const connection = await mysql.createConnection({
            host: process.env.DB_HOST || 'localhost',
            user: process.env.DB_USER || 'root',
            password: process.env.DB_PASSWORD || '',
            database: process.env.DB_NAME || 'transport_mobility_db'
        });

        console.log('Connected to database:', process.env.DB_NAME || 'transport_mobility_db');
        
        // Show all tables
        const [tables] = await connection.execute('SHOW TABLES');
        console.log('\nAvailable tables:');
        tables.forEach(table => {
            console.log('-', Object.values(table)[0]);
        });
        
        // Check if franchise_applications table exists
        const franchiseTables = tables.filter(table => 
            Object.values(table)[0].toLowerCase().includes('franchise')
        );
        
        if (franchiseTables.length > 0) {
            console.log('\nFranchise-related tables:');
            for (const table of franchiseTables) {
                const tableName = Object.values(table)[0];
                console.log(`\n--- ${tableName} ---`);
                const [columns] = await connection.execute(`DESCRIBE ${tableName}`);
                columns.forEach(col => {
                    console.log(`  ${col.Field} (${col.Type})`);
                });
            }
        }
        
        await connection.end();
    } catch (error) {
        console.error('Database error:', error.message);
    }
}

checkDatabaseSchema();