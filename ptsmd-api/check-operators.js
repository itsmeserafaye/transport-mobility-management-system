const mysql = require('mysql2/promise');
require('dotenv').config();

async function checkOperators() {
    try {
        const connection = await mysql.createConnection({
            host: process.env.DB_HOST || 'localhost',
            user: process.env.DB_USER || 'root',
            password: process.env.DB_PASSWORD || '',
            database: process.env.DB_NAME || 'transport_mobility_db'
        });

        console.log('\n--- operators table ---');
        const [operatorColumns] = await connection.execute('DESCRIBE operators');
        operatorColumns.forEach(col => {
            console.log(`  ${col.Field} (${col.Type})`);
        });
        
        console.log('\n--- vehicles table ---');
        const [vehicleColumns] = await connection.execute('DESCRIBE vehicles');
        vehicleColumns.forEach(col => {
            console.log(`  ${col.Field} (${col.Type})`);
        });
        
        await connection.end();
    } catch (error) {
        console.error('Database error:', error.message);
    }
}

checkOperators();