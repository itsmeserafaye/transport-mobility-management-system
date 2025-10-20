const express = require('express');
const router = express.Router();
const { executeQuery } = require('../config/database');

// =============================================
// PTSMD ANALYTICS ENDPOINTS
// =============================================

/**
 * @route GET /api/v1/analytics/violation-heatmap
 * @desc Get violation data for heatmap visualization
 * @access Public
 */
router.get('/violation-heatmap', async (req, res) => {
  try {
    const { startDate, endDate, violationType } = req.query;
    
    let query = `
      SELECT 
        vh.location,
        COUNT(*) as violation_count,
        SUM(vh.fine_amount) as total_fines,
        vh.violation_type,
        AVG(va.risk_level = 'high') * 100 as high_risk_percentage
      FROM violation_history vh
      LEFT JOIN violation_analytics va ON vh.operator_id = va.operator_id
      WHERE vh.location IS NOT NULL AND vh.location != ''
    `;
    
    const params = [];
    
    if (startDate) {
      query += ' AND vh.violation_date >= ?';
      params.push(startDate);
    }
    
    if (endDate) {
      query += ' AND vh.violation_date <= ?';
      params.push(endDate);
    }
    
    if (violationType) {
      query += ' AND vh.violation_type = ?';
      params.push(violationType);
    }
    
    query += `
      GROUP BY vh.location, vh.violation_type
      ORDER BY violation_count DESC
    `;
    
    const result = await executeQuery(query, params);
    
    if (result.success) {
      res.json({
        success: true,
        data: result.data,
        timestamp: new Date().toISOString()
      });
    } else {
      res.status(500).json({
        success: false,
        message: 'Failed to fetch violation heatmap data',
        error: result.error
      });
    }
  } catch (error) {
    res.status(500).json({
      success: false,
      message: 'Internal server error',
      error: error.message
    });
  }
});

/**
 * @route GET /api/v1/analytics/violation-hotspots
 * @desc Get violation hotspots with high concentration areas
 * @access Public
 */
router.get('/violation-hotspots', async (req, res) => {
  try {
    const { limit = 10, threshold = 5 } = req.query;
    
    const query = `
      SELECT 
        vh.location,
        COUNT(*) as violation_count,
        COUNT(DISTINCT vh.operator_id) as unique_operators,
        COUNT(DISTINCT vh.vehicle_id) as unique_vehicles,
        SUM(vh.fine_amount) as total_fines,
        AVG(vh.fine_amount) as avg_fine,
        GROUP_CONCAT(DISTINCT vh.violation_type) as violation_types,
        MAX(vh.violation_date) as latest_violation,
        MIN(vh.violation_date) as earliest_violation
      FROM violation_history vh
      WHERE vh.location IS NOT NULL AND vh.location != ''
      GROUP BY vh.location
      HAVING violation_count >= ?
      ORDER BY violation_count DESC, total_fines DESC
      LIMIT ?
    `;
    
    const result = await executeQuery(query, [threshold, parseInt(limit)]);
    
    if (result.success) {
      res.json({
        success: true,
        data: result.data,
        metadata: {
          threshold: parseInt(threshold),
          limit: parseInt(limit),
          total_hotspots: result.data.length
        },
        timestamp: new Date().toISOString()
      });
    } else {
      res.status(500).json({
        success: false,
        message: 'Failed to fetch violation hotspots',
        error: result.error
      });
    }
  } catch (error) {
    res.status(500).json({
      success: false,
      message: 'Internal server error',
      error: error.message
    });
  }
});

/**
 * @route GET /api/v1/analytics/repeat-offenders
 * @desc Get repeat offender analytics with risk levels
 * @access Public
 */
router.get('/repeat-offenders', async (req, res) => {
  try {
    const { riskLevel, minViolations = 2, limit = 20 } = req.query;
    
    let query = `
      SELECT 
        va.operator_id,
        va.vehicle_id,
        CONCAT(o.first_name, ' ', o.last_name) as operator_name,
        o.contact_number,
        v.plate_number,
        v.vehicle_type,
        va.total_violations,
        va.last_violation_date,
        va.repeat_offender_flag,
        va.risk_level,
        va.compliance_score,
        cs.franchise_status,
        cs.inspection_status,
        SUM(vh.fine_amount) as total_fines,
        COUNT(CASE WHEN vh.settlement_status = 'unpaid' THEN 1 END) as unpaid_violations
      FROM violation_analytics va
      JOIN operators o ON va.operator_id = o.operator_id
      JOIN vehicles v ON va.vehicle_id = v.vehicle_id
      LEFT JOIN compliance_status cs ON va.operator_id = cs.operator_id
      LEFT JOIN violation_history vh ON va.operator_id = vh.operator_id AND va.vehicle_id = vh.vehicle_id
      WHERE va.total_violations >= ?
    `;
    
    const params = [parseInt(minViolations)];
    
    if (riskLevel) {
      query += ' AND va.risk_level = ?';
      params.push(riskLevel);
    }
    
    query += `
      GROUP BY va.operator_id, va.vehicle_id
      ORDER BY va.total_violations DESC, va.compliance_score ASC
      LIMIT ?
    `;
    
    params.push(parseInt(limit));
    
    const result = await executeQuery(query, params);
    
    if (result.success) {
      res.json({
        success: true,
        data: result.data,
        metadata: {
          min_violations: parseInt(minViolations),
          risk_level_filter: riskLevel || 'all',
          limit: parseInt(limit),
          total_repeat_offenders: result.data.length
        },
        timestamp: new Date().toISOString()
      });
    } else {
      res.status(500).json({
        success: false,
        message: 'Failed to fetch repeat offender analytics',
        error: result.error
      });
    }
  } catch (error) {
    res.status(500).json({
      success: false,
      message: 'Internal server error',
      error: error.message
    });
  }
});

/**
 * @route GET /api/v1/analytics/enforcement-deployment
 * @desc Get enforcement deployment recommendations based on violation patterns
 * @access Public
 */
router.get('/enforcement-deployment', async (req, res) => {
  try {
    const { timeframe = '30' } = req.query; // days
    
    const query = `
      SELECT 
        vh.location,
        COUNT(*) as recent_violations,
        COUNT(DISTINCT DATE(vh.violation_date)) as violation_days,
        AVG(COUNT(*)) OVER() as avg_violations_per_location,
        GROUP_CONCAT(DISTINCT vh.violation_type) as common_violations,
        HOUR(vh.created_at) as peak_hour,
        COUNT(CASE WHEN va.risk_level = 'high' THEN 1 END) as high_risk_violations,
        SUM(vh.fine_amount) as potential_revenue,
        CASE 
          WHEN COUNT(*) > (SELECT AVG(location_count) * 1.5 FROM (
            SELECT COUNT(*) as location_count 
            FROM violation_history 
            WHERE violation_date >= DATE_SUB(CURRENT_DATE, INTERVAL ? DAY)
            GROUP BY location
          ) as subq) THEN 'High Priority'
          WHEN COUNT(*) > (SELECT AVG(location_count) FROM (
            SELECT COUNT(*) as location_count 
            FROM violation_history 
            WHERE violation_date >= DATE_SUB(CURRENT_DATE, INTERVAL ? DAY)
            GROUP BY location
          ) as subq) THEN 'Medium Priority'
          ELSE 'Low Priority'
        END as deployment_priority
      FROM violation_history vh
      LEFT JOIN violation_analytics va ON vh.operator_id = va.operator_id
      WHERE vh.violation_date >= DATE_SUB(CURRENT_DATE, INTERVAL ? DAY)
        AND vh.location IS NOT NULL AND vh.location != ''
      GROUP BY vh.location
      ORDER BY recent_violations DESC, high_risk_violations DESC
    `;
    
    const timeframeDays = parseInt(timeframe);
    const result = await executeQuery(query, [timeframeDays, timeframeDays, timeframeDays]);
    
    if (result.success) {
      res.json({
        success: true,
        data: result.data,
        metadata: {
          timeframe_days: timeframeDays,
          analysis_period: `Last ${timeframeDays} days`,
          total_locations: result.data.length
        },
        timestamp: new Date().toISOString()
      });
    } else {
      res.status(500).json({
        success: false,
        message: 'Failed to fetch enforcement deployment data',
        error: result.error
      });
    }
  } catch (error) {
    res.status(500).json({
      success: false,
      message: 'Internal server error',
      error: error.message
    });
  }
});

/**
 * @route GET /api/v1/analytics/dashboard-kpis
 * @desc Get PTSMD-specific KPIs for dashboard
 * @access Public
 */
router.get('/dashboard-kpis', async (req, res) => {
  try {
    // Get violation statistics
    const violationStatsQuery = `
      SELECT 
        COUNT(*) as total_violations,
        COUNT(CASE WHEN settlement_status = 'unpaid' THEN 1 END) as unpaid_violations,
        COUNT(CASE WHEN violation_date >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY) THEN 1 END) as violations_last_30_days,
        SUM(fine_amount) as total_fines,
        SUM(CASE WHEN settlement_status = 'paid' THEN fine_amount ELSE 0 END) as collected_revenue,
        COUNT(DISTINCT location) as violation_locations
      FROM violation_history
    `;
    
    // Get repeat offender statistics
    const repeatOffenderQuery = `
      SELECT 
        COUNT(*) as total_repeat_offenders,
        COUNT(CASE WHEN risk_level = 'high' THEN 1 END) as high_risk_offenders,
        COUNT(CASE WHEN risk_level = 'medium' THEN 1 END) as medium_risk_offenders,
        AVG(compliance_score) as avg_compliance_score
      FROM violation_analytics
      WHERE repeat_offender_flag = TRUE
    `;
    
    // Get hotspot statistics
    const hotspotQuery = `
      SELECT 
        COUNT(*) as total_hotspots,
        MAX(violation_count) as max_violations_per_location,
        AVG(violation_count) as avg_violations_per_location
      FROM (
        SELECT location, COUNT(*) as violation_count
        FROM violation_history
        WHERE location IS NOT NULL AND location != ''
        GROUP BY location
        HAVING COUNT(*) >= 5
      ) as hotspot_data
    `;
    
    const [violationStats, repeatOffenderStats, hotspotStats] = await Promise.all([
      executeQuery(violationStatsQuery),
      executeQuery(repeatOffenderQuery),
      executeQuery(hotspotQuery)
    ]);
    
    if (violationStats.success && repeatOffenderStats.success && hotspotStats.success) {
      res.json({
        success: true,
        data: {
          violations: violationStats.data[0] || {},
          repeat_offenders: repeatOffenderStats.data[0] || {},
          hotspots: hotspotStats.data[0] || {}
        },
        timestamp: new Date().toISOString()
      });
    } else {
      res.status(500).json({
        success: false,
        message: 'Failed to fetch dashboard KPIs',
        error: 'One or more queries failed'
      });
    }
  } catch (error) {
    res.status(500).json({
      success: false,
      message: 'Internal server error',
      error: error.message
    });
  }
});

module.exports = router;