const express = require('express');
const { executeQuery } = require('../config/database');
const router = express.Router();

// Get violation heatmap data
router.get('/violation-heatmap', async (req, res) => {
    try {
        const { timeframe = 30 } = req.query; // Default to last 30 days
        
        const query = `
            SELECT 
                vh.location,
                COUNT(*) as violation_count,
                AVG(vh.fine_amount) as avg_fine,
                DATE(vh.created_at) as violation_date
            FROM violation_history vh
            WHERE vh.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            AND vh.violation_location IS NOT NULL
             GROUP BY vh.violation_location, DATE(vh.created_at)
            ORDER BY violation_count DESC
        `;
        
        const result = await executeQuery(query, [timeframe]);
        
        if (!result.success) {
            throw new Error(result.error);
        }
        
        const results = result.data;
        
        // Process data for heatmap visualization
        const heatmapData = results.map(row => ({
            location: row.location,
            count: row.violation_count,
            avgFine: parseFloat(row.avg_fine || 0),
            date: row.violation_date,
            intensity: Math.min(row.violation_count / 10, 1) // Normalize intensity 0-1
        }));
        
        res.json({
            success: true,
            data: heatmapData,
            timeframe: timeframe,
            totalViolations: results.reduce((sum, row) => sum + row.violation_count, 0)
        });
    } catch (error) {
        console.error('Error fetching violation heatmap:', error);
        res.status(500).json({
            success: false,
            message: 'Failed to fetch violation heatmap data',
            error: error.message
        });
    }
});

// Get violation hotspots
router.get('/hotspots', async (req, res) => {
    try {
        const { limit = 10, timeframe = 30 } = req.query;
        
        const query = `
            SELECT 
                vh.location,
                COUNT(*) as violation_count,
                SUM(vh.fine_amount) as total_fines,
                COUNT(DISTINCT vh.operator_id) as unique_operators,
                CASE 
                    WHEN COUNT(*) >= 20 THEN 'High'
                    WHEN COUNT(*) >= 10 THEN 'Medium'
                    ELSE 'Low'
                END as risk_level,
                'Traffic Violation' as violation_types
            FROM violation_history vh
             WHERE vh.violation_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             AND vh.location IS NOT NULL
             AND vh.location != ''
             GROUP BY vh.location
            HAVING violation_count > 0
            ORDER BY violation_count DESC
            LIMIT ?
        `;
        
        const result = await executeQuery(query, [timeframe, parseInt(limit)]);
        
        if (!result.success) {
            throw new Error(result.error);
        }
        
        const results = result.data;
        
        const hotspots = results.map(row => ({
            location: row.location,
            violations: row.violation_count,
            totalFines: parseFloat(row.total_fines || 0),
            uniqueOperators: row.unique_operators,
            risk: row.risk_level,
            violationTypes: row.violation_types
        }));
        
        res.json({
            success: true,
            data: hotspots,
            timeframe: timeframe
        });
    } catch (error) {
        console.error('Error fetching violation hotspots:', error);
        res.status(500).json({
            success: false,
            message: 'Failed to fetch violation hotspots',
            error: error.message
        });
    }
});

// Get repeat offenders analytics
router.get('/repeat-offenders', async (req, res) => {
    try {
        const { riskLevel, limit = 20, timeframe = 90, minViolations = 3 } = req.query;
        
        let whereClause = '';
        let params = [parseInt(timeframe), parseInt(minViolations)];
        
        if (riskLevel && riskLevel !== 'all') {
            whereClause = `AND (
                CASE 
                    WHEN COUNT(*) >= 5 THEN 'high'
                    WHEN COUNT(*) >= 3 THEN 'medium'
                    ELSE 'low'
                END
            ) = ?`;
            params.push(riskLevel.toLowerCase());
        }
        
        const query = `
            SELECT 
                va.operator_id,
                CONCAT(o.first_name, ' ', o.last_name) as operator_name,
                o.license_number,
                va.total_violations as violation_count,
                va.last_violation_date,
                (va.total_violations * 100) as total_fines,
                CASE 
                    WHEN va.total_violations >= 5 THEN 'high'
                    WHEN va.total_violations >= 3 THEN 'medium'
                    ELSE 'low'
                END as risk_level,
                'Traffic Violation' as violation_types
            FROM violation_analytics va
            INNER JOIN operators o ON va.operator_id = o.operator_id
            WHERE va.last_violation_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            AND va.total_violations >= ?
            ${whereClause}
            ORDER BY va.total_violations DESC, va.last_violation_date DESC
            LIMIT ${parseInt(limit)}
        `;
        
        const result = await executeQuery(query, params);
        
        if (!result.success) {
            throw new Error(result.error);
        }
        
        const results = result.data;
        
        const repeatOffenders = results.map(row => ({
            operatorId: row.operator_id,
            operatorName: row.operator_name,
            licenseNumber: row.license_number,
            violations: row.violation_count,
            lastViolation: row.last_violation_date,
            risk: row.risk_level,
            totalFines: parseFloat(row.total_fines || 0),
            violationTypes: row.violation_types
        }));
        
        res.json({
            success: true,
            data: repeatOffenders,
            timeframe: timeframe,
            riskLevel: riskLevel || 'all'
        });
    } catch (error) {
        console.error('Error fetching repeat offenders:', error);
        res.status(500).json({
            success: false,
            message: 'Failed to fetch repeat offenders data',
            error: error.message
        });
    }
});

// Get enforcement deployment recommendations
router.get('/enforcement-deployment', async (req, res) => {
    try {
        const { timeframe = 30 } = req.query;
        
        const query = `
            SELECT 
                vh.location as area,
                COUNT(*) as violation_count,
                COUNT(DISTINCT vh.operator_id) as unique_operators,
                COUNT(DISTINCT DATE(vh.violation_date)) as active_days,
                CASE 
                    WHEN COUNT(*) >= 30 THEN 'High'
                    WHEN COUNT(*) >= 15 THEN 'Medium'
                    ELSE 'Low'
                END as priority,
                AVG(vh.fine_amount) as avg_fine_amount
            FROM violation_history vh
            INNER JOIN operators o ON vh.operator_id = o.operator_id
            WHERE vh.violation_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                AND vh.location IS NOT NULL
                AND vh.location != ''
            GROUP BY vh.location
            HAVING violation_count >= 1
            ORDER BY violation_count DESC, unique_operators DESC
        `;
        
        const result = await executeQuery(query, [timeframe]);
        
        if (!result.success) {
            throw new Error(result.error);
        }
        
        const results = result.data;
        
        // Generate recommendations
        const recommendations = results.map(row => {
            // Calculate recommended units based on violation count
            const recommendedUnits = Math.min(Math.ceil(row.violation_count / 20), 6);
            
            return {
                area: row.area,
                priority: row.priority,
                recommendedUnits: recommendedUnits,
                totalViolations: row.violation_count,
                uniqueOperators: row.unique_operators,
                activeDays: row.active_days,
                avgFineAmount: parseFloat(row.avg_fine_amount || 0)
            };
        }).slice(0, 10); // Limit to top 10 recommendations
        
        res.json({
            success: true,
            data: recommendations,
            timeframe: timeframe
        });
    } catch (error) {
        console.error('Error fetching enforcement deployment data:', error);
        res.status(500).json({
            success: false,
            message: 'Failed to fetch enforcement deployment recommendations',
            error: error.message
        });
    }
});

// Get analytics summary
router.get('/summary', async (req, res) => {
    try {
        const { timeframe = 30 } = req.query;
        
        const queries = {
            totalViolations: `
                SELECT SUM(va.total_violations) as count 
                FROM violation_analytics va
                WHERE va.last_violation_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            `,
            totalRevenue: `
                SELECT (SUM(va.total_violations) * 100) as amount 
                FROM violation_analytics va
                WHERE va.last_violation_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            `,
            activeVehicles: `
                SELECT COUNT(DISTINCT va.operator_id) as count 
                FROM violation_analytics va
                INNER JOIN operators o ON va.operator_id = o.operator_id
                WHERE va.last_violation_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            `,
            hotspotCount: `
                SELECT COUNT(DISTINCT va.operator_id) as count 
                FROM violation_analytics va
                WHERE va.last_violation_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            `
        };
        
        const summary = {};
        
        for (const [key, query] of Object.entries(queries)) {
            const result = await executeQuery(query, [timeframe]);
            
            if (!result.success) {
                throw new Error(result.error);
            }
            
            const results = result.data;
            summary[key] = key === 'totalRevenue' ? parseFloat(results[0].amount || 0) : results[0].count;
        }
        
        res.json({
            success: true,
            data: summary,
            timeframe: timeframe
        });
    } catch (error) {
        console.error('Error fetching analytics summary:', error);
        res.status(500).json({
            success: false,
            message: 'Failed to fetch analytics summary',
            error: error.message
        });
    }
});

module.exports = router;