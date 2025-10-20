const { executeQuery, executeTransaction } = require('../config/database');

class TrafficViolationTicketing {
  // =============================================
  // VIOLATION HISTORY CRUD OPERATIONS
  // =============================================
  
  static async createViolationRecord(violationData) {
    const {
      operator_id,
      vehicle_id,
      violation_type,
      violation_date,
      location,
      fine_amount,
      status,
      issued_by,
      description,
      evidence_photos
    } = violationData;

    const query = `
      INSERT INTO violation_history (
        operator_id, vehicle_id, violation_type, 
        violation_date, location, fine_amount, status, 
        issued_by, description, evidence_photos
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    `;
    
    const params = [
      operator_id,
      vehicle_id,
      violation_type,
      violation_date || new Date(),
      location,
      fine_amount,
      status || 'pending',
      issued_by,
      description || null,
      JSON.stringify(evidence_photos || [])
    ];
    
    const result = await executeQuery(query, params);
    return { violation_id: result.insertId, ...violationData };
  }
  
  static async getAllViolations(page = 1, limit = 10, filters = {}) {
    const offset = (page - 1) * limit;
    let query = `
      SELECT vh.*, CONCAT(o.first_name, ' ', o.last_name) as operator_name, o.contact_number, v.plate_number
       FROM violation_history vh
       LEFT JOIN operators o ON vh.operator_id = o.operator_id
       LEFT JOIN vehicles v ON vh.vehicle_id = v.vehicle_id
      WHERE 1=1
    `;
    let countQuery = `
      SELECT COUNT(*) as total 
      FROM violation_history vh
      LEFT JOIN operators o ON vh.operator_id = o.operator_id
      LEFT JOIN vehicles v ON vh.vehicle_id = v.vehicle_id
      WHERE 1=1
    `;
    let params = [];
    
    // Apply filters
    if (filters.status) {
      query += ' AND vh.status = ?';
      countQuery += ' AND vh.status = ?';
      params.push(filters.status);
    }
    
    if (filters.violation_type) {
      query += ' AND vh.violation_type = ?';
      countQuery += ' AND vh.violation_type = ?';
      params.push(filters.violation_type);
    }
    
    if (filters.operator_id) {
      query += ' AND vh.operator_id = ?';
      countQuery += ' AND vh.operator_id = ?';
      params.push(filters.operator_id);
    }
    
    if (filters.vehicle_id) {
      query += ' AND vh.vehicle_id = ?';
      countQuery += ' AND vh.vehicle_id = ?';
      params.push(filters.vehicle_id);
    }
    
    if (filters.date_from) {
      query += ' AND vh.violation_date >= ?';
      countQuery += ' AND vh.violation_date >= ?';
      params.push(filters.date_from);
    }
    
    if (filters.date_to) {
      query += ' AND vh.violation_date <= ?';
      countQuery += ' AND vh.violation_date <= ?';
      params.push(filters.date_to);
    }
    
    if (filters.location) {
      query += ' AND vh.location LIKE ?';
      countQuery += ' AND vh.location LIKE ?';
      params.push(`%${filters.location}%`);
    }
    
    if (filters.issued_by) {
      query += ' AND vh.issued_by LIKE ?';
      countQuery += ' AND vh.issued_by LIKE ?';
      params.push(`%${filters.issued_by}%`);
    }
    
    if (filters.search) {
      query += ' AND (CONCAT(o.first_name, " ", o.last_name) LIKE ? OR o.contact_number LIKE ? OR v.plate_number LIKE ?)';
       countQuery += ' AND (CONCAT(o.first_name, " ", o.last_name) LIKE ? OR o.contact_number LIKE ? OR v.plate_number LIKE ?)';
      const searchTerm = `%${filters.search}%`;
      params.push(searchTerm, searchTerm, searchTerm);
    }
    
    query += ' ORDER BY vh.violation_date DESC LIMIT ? OFFSET ?';
    const queryParams = [...params, limit, offset];
    
    const [violations, totalResult] = await Promise.all([
      executeQuery(query, queryParams),
      executeQuery(countQuery, params)
    ]);
    
    return {
      success: true,
      data: violations.data || violations,
      pagination: {
        current_page: page,
        per_page: limit,
        total: totalResult.data ? totalResult.data[0].total : totalResult[0].total,
        total_pages: Math.ceil((totalResult.data ? totalResult.data[0].total : totalResult[0].total) / limit)
      }
    };
  }
  
  static async getViolationHistoryById(violationId) {
    const query = `
      SELECT vh.*, CONCAT(o.first_name, ' ', o.last_name) as operator_name, o.contact_number, o.email,
             v.plate_number, v.vehicle_type, v.make, v.model
      FROM violation_history vh
      LEFT JOIN operators o ON vh.operator_id = o.operator_id
      LEFT JOIN vehicles v ON vh.vehicle_id = v.vehicle_id
      WHERE vh.violation_id = ?
    `;
    
    const result = await executeQuery(query, [violationId]);
    return result.data && result.data.length > 0 ? result.data[0] : (result.length > 0 ? result[0] : null);
  }
  
  static async updateViolation(violationId, updateData) {
    const fields = [];
    const params = [];
    
    Object.keys(updateData).forEach(key => {
      if (updateData[key] !== undefined) {
        if (key === 'evidence_photos') {
          fields.push(`${key} = ?`);
          params.push(JSON.stringify(updateData[key]));
        } else {
          fields.push(`${key} = ?`);
          params.push(updateData[key]);
        }
      }
    });
    
    if (fields.length === 0) {
      return { success: false, error: 'No fields to update' };
    }
    
    params.push(violationId);
    const query = `UPDATE violation_history SET ${fields.join(', ')} WHERE violation_id = ?`;
    
    return await executeQuery(query, params);
  }
  
  static async deleteViolation(violationId) {
    const query = 'DELETE FROM violation_history WHERE violation_id = ?';
    return await executeQuery(query, [violationId]);
  }
  
  // =============================================
  // VIOLATION SETTLEMENT OPERATIONS
  // =============================================
  
  static async settleViolation(violationId, settlementData) {
    const updateData = {
      status: 'paid',
      settlement_date: settlementData.settlement_date || new Date().toISOString(),
      payment_method: settlementData.payment_method,
      receipt_number: settlementData.receipt_number
    };
    
    return await this.updateViolation(violationId, updateData);
  }
  
  static async contestViolation(violationId, contestData) {
    const updateData = {
      status: 'contested',
      contest_date: contestData.contest_date || new Date().toISOString(),
      contest_reason: contestData.contest_reason
    };
    
    return await this.updateViolation(violationId, updateData);
  }
  
  static async dismissViolation(violationId, reason) {
    const updateData = {
      status: 'dismissed',
      contest_reason: reason,
      settlement_date: new Date().toISOString()
    };
    
    return await this.updateViolation(violationId, updateData);
  }
  
  // =============================================
  // VIOLATION ANALYTICS CRUD OPERATIONS
  // =============================================
  
  static async createViolationAnalytics(analyticsData) {
    const analyticsId = `VA${Date.now()}`;
    const query = `
      INSERT INTO violation_analytics (
        analytics_id, analysis_date, violation_type, location, 
        total_violations, total_revenue, repeat_offenders, settlement_rate, 
        hotspot_ranking, trend_direction, recommendations
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    `;
    
    const params = [
      analyticsId,
      analyticsData.analysis_date,
      analyticsData.violation_type,
      analyticsData.location,
      analyticsData.total_violations,
      analyticsData.total_revenue,
      analyticsData.repeat_offenders,
      analyticsData.settlement_rate,
      analyticsData.hotspot_ranking || null,
      analyticsData.trend_direction,
      analyticsData.recommendations || null
    ];
    
    const result = await executeQuery(query, params);
    if (result.success) {
      return { success: true, analytics_id: analyticsId, data: result.data };
    }
    return result;
  }
  
  static async getAllViolationAnalytics(filters = {}) {
    let query = 'SELECT * FROM violation_analytics WHERE 1=1';
    const params = [];
    
    if (filters.violation_type) {
      query += ' AND violation_type = ?';
      params.push(filters.violation_type);
    }
    
    if (filters.location) {
      query += ' AND location LIKE ?';
      params.push(`%${filters.location}%`);
    }
    
    if (filters.date_from) {
      query += ' AND analysis_date >= ?';
      params.push(filters.date_from);
    }
    
    if (filters.date_to) {
      query += ' AND analysis_date <= ?';
      params.push(filters.date_to);
    }
    
    if (filters.trend_direction) {
      query += ' AND trend_direction = ?';
      params.push(filters.trend_direction);
    }
    
    query += ' ORDER BY analysis_date DESC, hotspot_ranking ASC';
    
    return await executeQuery(query, params);
  }
  
  static async getViolationAnalyticsById(analyticsId) {
    const query = 'SELECT * FROM violation_analytics WHERE analytics_id = ?';
    return await executeQuery(query, [analyticsId]);
  }
  
  // =============================================
  // REVENUE REPORTS CRUD OPERATIONS
  // =============================================
  
  static async createRevenueReport(reportData) {
    const reportId = `RR${Date.now()}`;
    const query = `
      INSERT INTO revenue_reports (
        report_id, report_date, report_period, total_violations, 
        total_fines_issued, total_collected, collection_rate, 
        pending_amount, contested_amount, dismissed_amount, generated_by
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    `;
    
    const params = [
      reportId,
      reportData.report_date,
      reportData.report_period,
      reportData.total_violations,
      reportData.total_fines_issued,
      reportData.total_collected,
      reportData.collection_rate,
      reportData.pending_amount,
      reportData.contested_amount,
      reportData.dismissed_amount,
      reportData.generated_by
    ];
    
    const result = await executeQuery(query, params);
    if (result.success) {
      return { success: true, report_id: reportId, data: result.data };
    }
    return result;
  }
  
  static async getAllRevenueReports(filters = {}) {
    let query = 'SELECT * FROM revenue_reports WHERE 1=1';
    const params = [];
    
    if (filters.report_period) {
      query += ' AND report_period = ?';
      params.push(filters.report_period);
    }
    
    if (filters.date_from) {
      query += ' AND report_date >= ?';
      params.push(filters.date_from);
    }
    
    if (filters.date_to) {
      query += ' AND report_date <= ?';
      params.push(filters.date_to);
    }
    
    if (filters.generated_by) {
      query += ' AND generated_by = ?';
      params.push(filters.generated_by);
    }
    
    query += ' ORDER BY report_date DESC';
    
    return await executeQuery(query, params);
  }
  
  static async getRevenueReportById(reportId) {
    const query = 'SELECT * FROM revenue_reports WHERE report_id = ?';
    return await executeQuery(query, [reportId]);
  }
  
  // =============================================
  // ANALYTICS AND INSIGHTS
  // =============================================
  
  static async getViolationSummary(filters = {}) {
    let query = `
      SELECT 
        COUNT(*) as total_violations,
        SUM(fine_amount) as total_fines,
        SUM(CASE WHEN status = 'paid' THEN fine_amount ELSE 0 END) as collected_amount,
        SUM(CASE WHEN status = 'pending' THEN fine_amount ELSE 0 END) as pending_amount,
        SUM(CASE WHEN status = 'contested' THEN fine_amount ELSE 0 END) as contested_amount,
        SUM(CASE WHEN status = 'dismissed' THEN fine_amount ELSE 0 END) as dismissed_amount,
        ROUND((SUM(CASE WHEN status = 'paid' THEN fine_amount ELSE 0 END) / SUM(fine_amount)) * 100, 2) as collection_rate
      FROM violation_history 
      WHERE 1=1
    `;
    const params = [];
    
    if (filters.date_from) {
      query += ' AND DATE(violation_date) >= ?';
      params.push(filters.date_from);
    }
    
    if (filters.date_to) {
      query += ' AND DATE(violation_date) <= ?';
      params.push(filters.date_to);
    }
    
    return await executeQuery(query, params);
  }
  
  // CRUD operations for violation analytics
  static async createViolationAnalytics(analyticsData) {
    const {
      analysis_period, total_violations, paid_violations, pending_violations,
      contested_violations, dismissed_violations, total_revenue, analysis_date
    } = analyticsData;
    
    const query = `
      INSERT INTO violation_analytics (
        analysis_period, total_violations, paid_violations, pending_violations,
        contested_violations, dismissed_violations, total_revenue, analysis_date
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    `;
    
    try {
      const result = await executeQuery(query, [
        analysis_period, total_violations, paid_violations, pending_violations,
        contested_violations, dismissed_violations, total_revenue, analysis_date
      ]);
      
      return {
        success: true,
        analytics_id: result.insertId,
        message: 'Violation analytics created successfully'
      };
    } catch (error) {
      return { success: false, message: 'Error creating violation analytics', error: error.message };
    }
  }
  
  static async updateViolationAnalytics(analyticsId, analyticsData) {
    const allowedFields = [
      'analysis_period', 'total_violations', 'paid_violations', 'pending_violations',
      'contested_violations', 'dismissed_violations', 'total_revenue', 'analysis_date'
    ];
    
    const updateFields = [];
    const values = [];
    
    for (const [key, value] of Object.entries(analyticsData)) {
      if (allowedFields.includes(key) && value !== undefined) {
        updateFields.push(`${key} = ?`);
        values.push(value);
      }
    }
    
    if (updateFields.length === 0) {
      return { success: false, message: 'No valid fields to update' };
    }
    
    values.push(analyticsId);
    const query = `UPDATE violation_analytics SET ${updateFields.join(', ')} WHERE analytics_id = ?`;
    
    try {
      const result = await executeQuery(query, values);
      
      if (result.affectedRows > 0) {
        return { success: true, message: 'Violation analytics updated successfully' };
      } else {
        return { success: false, message: 'Violation analytics not found' };
      }
    } catch (error) {
      return { success: false, message: 'Error updating violation analytics', error: error.message };
    }
  }
  
  static async deleteViolationAnalytics(analyticsId) {
    const query = 'DELETE FROM violation_analytics WHERE analytics_id = ?';
    
    try {
      const result = await executeQuery(query, [analyticsId]);
      
      if (result.affectedRows > 0) {
        return { success: true, message: 'Violation analytics deleted successfully' };
      } else {
        return { success: false, message: 'Violation analytics not found' };
      }
    } catch (error) {
      return { success: false, message: 'Error deleting violation analytics', error: error.message };
    }
  }
  
  static async getViolationsByType(filters = {}) {
    let query = `
      SELECT 
        violation_type,
        COUNT(*) as violation_count,
        SUM(fine_amount) as total_fines,
        SUM(CASE WHEN settlement_status = 'paid' THEN fine_amount ELSE 0 END) as collected_amount,
        ROUND(AVG(fine_amount), 2) as avg_fine_amount
      FROM violation_history 
      WHERE 1=1
    `;
    const params = [];
    
    if (filters.date_from) {
      query += ' AND DATE(violation_date) >= ?';
      params.push(filters.date_from);
    }
    
    if (filters.date_to) {
      query += ' AND DATE(violation_date) <= ?';
      params.push(filters.date_to);
    }
    
    query += ' GROUP BY violation_type ORDER BY violation_count DESC';
    
    return await executeQuery(query, params);
  }
  
  static async getViolationsByLocation(filters = {}) {
    let query = `
      SELECT 
        violation_location,
        COUNT(*) as violation_count,
        SUM(fine_amount) as total_fines,
        COUNT(DISTINCT operator_id) as unique_violators
      FROM violation_history 
      WHERE 1=1
    `;
    const params = [];
    
    if (filters.date_from) {
      query += ' AND DATE(violation_date) >= ?';
      params.push(filters.date_from);
    }
    
    if (filters.date_to) {
      query += ' AND DATE(violation_date) <= ?';
      params.push(filters.date_to);
    }
    
    query += ' GROUP BY violation_location ORDER BY violation_count DESC LIMIT 10';
    
    return await executeQuery(query, params);
  }
  
  static async getRepeatOffenders(filters = {}) {
    let query = `
      SELECT 
        v.operator_id,
        o.first_name,
        o.last_name,
        o.contact_number,
        COUNT(*) as violation_count,
        SUM(v.fine_amount) as total_fines,
        SUM(CASE WHEN v.settlement_status = 'paid' THEN v.fine_amount ELSE 0 END) as paid_amount,
        MAX(v.violation_date) as last_violation_date
      FROM violation_history v 
      LEFT JOIN operators o ON v.operator_id = o.operator_id 
      WHERE 1=1
    `;
    const params = [];
    
    if (filters.date_from) {
      query += ' AND DATE(v.violation_date) >= ?';
      params.push(filters.date_from);
    }
    
    if (filters.date_to) {
      query += ' AND DATE(v.violation_date) <= ?';
      params.push(filters.date_to);
    }
    
    query += ` 
      GROUP BY v.operator_id, o.first_name, o.last_name, o.contact_number 
      HAVING violation_count >= ? 
      ORDER BY violation_count DESC
    `;
    params.push(filters.min_violations || 3);
    
    return await executeQuery(query, params);
  }
  
  static async getViolationTrends(period = 'monthly') {
    let dateFormat;
    switch (period) {
      case 'daily':
        dateFormat = '%Y-%m-%d';
        break;
      case 'weekly':
        dateFormat = '%Y-%u';
        break;
      case 'monthly':
        dateFormat = '%Y-%m';
        break;
      case 'yearly':
        dateFormat = '%Y';
        break;
      default:
        dateFormat = '%Y-%m';
    }
    
    const query = `
      SELECT 
        DATE_FORMAT(violation_date, ?) as period,
        COUNT(*) as violation_count,
        SUM(fine_amount) as total_fines,
        SUM(CASE WHEN settlement_status = 'paid' THEN fine_amount ELSE 0 END) as collected_amount
      FROM violation_history 
      WHERE violation_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
      GROUP BY DATE_FORMAT(violation_date, ?)
      ORDER BY period ASC
    `;
    
    return await executeQuery(query, [dateFormat, dateFormat]);
  }

  static async getRevenueSummary(filters = {}) {
    const { date_from, date_to, group_by = 'month' } = filters;
    
    let dateFormat;
    switch (group_by) {
      case 'day':
        dateFormat = '%Y-%m-%d';
        break;
      case 'week':
        dateFormat = '%Y-%u';
        break;
      case 'month':
        dateFormat = '%Y-%m';
        break;
      case 'year':
        dateFormat = '%Y';
        break;
      default:
        dateFormat = '%Y-%m';
    }
    
    let query = `
      SELECT 
        DATE_FORMAT(rc.collection_date, ?) as period,
        COUNT(DISTINCT rc.collection_id) as total_collections,
        SUM(rc.amount_collected) as collected_amount,
        COUNT(DISTINCT vh.violation_id) as total_violations,
        SUM(vh.fine_amount) as total_fines,
        ROUND((SUM(rc.amount_collected) / SUM(vh.fine_amount)) * 100, 2) as collection_rate
      FROM revenue_collections rc
      LEFT JOIN violation_history vh ON rc.violation_id = vh.violation_id
      WHERE 1=1
    `;
    const params = [dateFormat];
    
    if (date_from) {
      query += ' AND DATE(rc.collection_date) >= ?';
      params.push(date_from);
    } else {
      query += ' AND rc.collection_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)';
    }
    
    if (date_to) {
      query += ' AND DATE(rc.collection_date) <= ?';
      params.push(date_to);
    }
    
    query += ' GROUP BY DATE_FORMAT(rc.collection_date, ?) ORDER BY period ASC';
    params.push(dateFormat);
    
    const result = await executeQuery(query, params);
    return { success: true, data: result };
  }
  
  static async getOfficerPerformance(filters = {}) {
    let query = `
      SELECT 
        officer_name,
        officer_badge,
        COUNT(*) as tickets_issued,
        SUM(fine_amount) as total_fines_issued,
        SUM(CASE WHEN settlement_status = 'paid' THEN fine_amount ELSE 0 END) as collected_amount,
        ROUND((SUM(CASE WHEN settlement_status = 'paid' THEN fine_amount ELSE 0 END) / SUM(fine_amount)) * 100, 2) as collection_rate
      FROM violation_history 
      WHERE 1=1
    `;
    const params = [];
    
    if (filters.date_from) {
      query += ' AND DATE(violation_date) >= ?';
      params.push(filters.date_from);
    }
    
    if (filters.date_to) {
      query += ' AND DATE(violation_date) <= ?';
      params.push(filters.date_to);
    }
    
    if (filters.officer_name) {
      query += ' AND officer_name LIKE ?';
      params.push(`%${filters.officer_name}%`);
    }
    
    query += ' GROUP BY officer_name, officer_badge ORDER BY tickets_issued DESC';
    
    return await executeQuery(query, params);
  }
  
  // =============================================
  // REVENUE COLLECTIONS CRUD OPERATIONS
  // =============================================
  
  // CRUD operations for revenue collections
  static async createRevenueCollection(collectionData) {
    const {
      collection_date, total_collections, daily_revenue, payment_method,
      avg_fine_amount, collection_period
    } = collectionData;
    
    const query = `
      INSERT INTO revenue_collections (
        collection_date, total_collections, daily_revenue, payment_method,
        avg_fine_amount, collection_period
      ) VALUES (?, ?, ?, ?, ?, ?)
    `;
    
    try {
      const result = await executeQuery(query, [
        collection_date, total_collections, daily_revenue, payment_method,
        avg_fine_amount, collection_period
      ]);
      
      return {
        success: true,
        collection_id: result.insertId,
        message: 'Revenue collection created successfully'
      };
    } catch (error) {
      return { success: false, message: 'Error creating revenue collection', error: error.message };
    }
  }
  
  static async getAllRevenueCollections(page = 1, limit = 10, filters = {}) {
    const offset = (page - 1) * limit;
    let query = `
      SELECT * FROM revenue_collections
      WHERE 1=1
    `;
    let countQuery = `SELECT COUNT(*) as total FROM revenue_collections WHERE 1=1`;
    let params = [];
    
    if (filters.payment_method) {
      query += ' AND payment_method = ?';
      countQuery += ' AND payment_method = ?';
      params.push(filters.payment_method);
    }
    
    if (filters.collection_period) {
      query += ' AND collection_period = ?';
      countQuery += ' AND collection_period = ?';
      params.push(filters.collection_period);
    }
    
    if (filters.date_from) {
      query += ' AND collection_date >= ?';
      countQuery += ' AND collection_date >= ?';
      params.push(filters.date_from);
    }
    
    if (filters.date_to) {
      query += ' AND collection_date <= ?';
      countQuery += ' AND collection_date <= ?';
      params.push(filters.date_to);
    }
    
    query += ' ORDER BY collection_date DESC LIMIT ? OFFSET ?';
    const queryParams = [...params, limit, offset];
    
    const [collections, totalResult] = await Promise.all([
      executeQuery(query, queryParams),
      executeQuery(countQuery, params)
    ]);
    
    return {
      success: true,
      data: collections,
      pagination: {
        current_page: page,
        per_page: limit,
        total: totalResult[0].total,
        total_pages: Math.ceil(totalResult[0].total / limit)
      }
    };
  }
  
  static async getRevenueCollectionById(collectionId) {
    const query = 'SELECT * FROM revenue_collections WHERE collection_id = ?';
    const result = await executeQuery(query, [collectionId]);
    return result.length > 0 ? result[0] : null;
  }
  
  static async updateRevenueCollection(collectionId, collectionData) {
    const allowedFields = [
      'collection_date', 'total_collections', 'daily_revenue', 'payment_method',
      'avg_fine_amount', 'collection_period'
    ];
    
    const updateFields = [];
    const values = [];
    
    for (const [key, value] of Object.entries(collectionData)) {
      if (allowedFields.includes(key) && value !== undefined) {
        updateFields.push(`${key} = ?`);
        values.push(value);
      }
    }
    
    if (updateFields.length === 0) {
      return { success: false, message: 'No valid fields to update' };
    }
    
    values.push(collectionId);
    const query = `UPDATE revenue_collections SET ${updateFields.join(', ')} WHERE collection_id = ?`;
    
    try {
      const result = await executeQuery(query, values);
      
      if (result.affectedRows > 0) {
        return { success: true, message: 'Revenue collection updated successfully' };
      } else {
        return { success: false, message: 'Revenue collection not found' };
      }
    } catch (error) {
      return { success: false, message: 'Error updating revenue collection', error: error.message };
    }
  }
  
  static async deleteRevenueCollection(collectionId) {
    const query = 'DELETE FROM revenue_collections WHERE collection_id = ?';
    
    try {
      const result = await executeQuery(query, [collectionId]);
      
      if (result.affectedRows > 0) {
        return { success: true, message: 'Revenue collection deleted successfully' };
      } else {
        return { success: false, message: 'Revenue collection not found' };
      }
    } catch (error) {
      return { success: false, message: 'Error deleting revenue collection', error: error.message };
    }
  }
  
  // =============================================
  // AUTOMATED REVENUE REPORT GENERATION
  // =============================================
  
  static async generateAutomatedRevenueReport(period, date, generatedBy) {
    // Get violation summary for the period
    const summaryResult = await this.getViolationSummary({ 
      date_from: date, 
      date_to: date 
    });
    
    if (!summaryResult.success || summaryResult.data.length === 0) {
      return { success: false, error: 'No data found for the specified period' };
    }
    
    const summary = summaryResult.data[0];
    
    const reportData = {
      report_date: date,
      report_period: period,
      total_violations: summary.total_violations,
      total_fines_issued: summary.total_fines,
      total_collected: summary.collected_amount,
      collection_rate: summary.collection_rate,
      pending_amount: summary.pending_amount,
      contested_amount: summary.contested_amount,
      dismissed_amount: summary.dismissed_amount,
      generated_by: generatedBy
    };
    
    return await this.createRevenueReport(reportData);
  }
}

module.exports = TrafficViolationTicketing;