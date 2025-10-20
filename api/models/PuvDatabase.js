const { executeQuery } = require('../config/database');

class PuvDatabase {
  // Create a new operator
  static async createOperator(operatorData) {
    const {
      operator_name,
      license_number,
      contact_person,
      phone_number,
      email,
      address,
      business_type,
      registration_date
    } = operatorData;

    const query = `
      INSERT INTO operators (
        operator_name, license_number, contact_person, phone_number, 
        email, address, business_type, registration_date
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    `;

    const params = [
      operator_name, license_number, contact_person, phone_number,
      email, address, business_type, registration_date || new Date()
    ];

    const result = await executeQuery(query, params);
    return { operator_id: result.insertId, ...operatorData };
  }
  
  static async getAllOperators(filters = {}) {
    let query = 'SELECT * FROM operators WHERE 1=1';
    const params = [];
    
    if (filters.status) {
      query += ' AND status = ?';
      params.push(filters.status);
    }
    
    if (filters.search) {
      query += ' AND (operator_name LIKE ? OR contact_person LIKE ? OR license_number LIKE ?)';
      const searchTerm = `%${filters.search}%`;
      params.push(searchTerm, searchTerm, searchTerm);
    }
    
    query += ' ORDER BY date_registered DESC';
    
    if (filters.limit) {
      query += ' LIMIT ?';
      params.push(parseInt(filters.limit));
    }
    
    return await executeQuery(query, params);
  }
  
  static async getOperatorById(operatorId) {
    const query = 'SELECT * FROM operators WHERE operator_id = ?';
    const result = await executeQuery(query, [operatorId]);
    if (result.length > 0) {
      return { success: true, data: result[0] };
    }
    return { success: false, message: 'Operator not found' };
  }
  
  static async updateOperator(operatorId, updateData) {
    const allowedFields = [
      'operator_name', 'license_number', 'contact_person', 'phone_number', 
      'email', 'address', 'business_type', 'registration_date'
    ];
    
    const updateFields = [];
    const params = [];
    
    Object.keys(updateData).forEach(key => {
      if (allowedFields.includes(key) && updateData[key] !== undefined) {
        updateFields.push(`${key} = ?`);
        params.push(updateData[key]);
      }
    });
    
    if (updateFields.length === 0) {
      return { success: false, message: 'No valid fields to update' };
    }
    
    params.push(operatorId);
    const query = `UPDATE operators SET ${updateFields.join(', ')} WHERE operator_id = ?`;
    
    const result = await executeQuery(query, params);
    if (result.affectedRows > 0) {
      return { success: true, message: 'Operator updated successfully' };
    }
    return { success: false, message: 'Operator not found or no changes made' };
  }
  
  static async deleteOperator(operatorId) {
    const query = 'DELETE FROM operators WHERE operator_id = ?';
    return await executeQuery(query, [operatorId]);
  }
  
  // =============================================
  // VEHICLES CRUD OPERATIONS
  // =============================================
  
  static async createVehicle(vehicleData) {
    const {
      operator_id,
      plate_number,
      vehicle_type,
      make_model,
      year_manufactured,
      engine_number,
      chassis_number,
      seating_capacity,
      fuel_type,
      color,
      status
    } = vehicleData;

    const query = `
      INSERT INTO vehicles (
        operator_id, plate_number, vehicle_type, 
        make_model, year_manufactured, engine_number, chassis_number, 
        seating_capacity, fuel_type, color, status
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    `;
    
    const params = [
      operator_id,
      plate_number,
      vehicle_type,
      make_model,
      year_manufactured,
      engine_number,
      chassis_number,
      seating_capacity,
      fuel_type,
      color,
      status || 'active'
    ];
    
    const result = await executeQuery(query, params);
    return { vehicle_id: result.insertId, ...vehicleData };
  }
  
  static async getAllVehicles(filters = {}) {
    let query = `
      SELECT v.*, o.operator_name, o.contact_person 
      FROM vehicles v 
      LEFT JOIN operators o ON v.operator_id = o.operator_id 
      WHERE 1=1
    `;
    const params = [];
    
    if (filters.operator_id) {
      query += ' AND v.operator_id = ?';
      params.push(filters.operator_id);
    }
    
    if (filters.vehicle_type) {
      query += ' AND v.vehicle_type = ?';
      params.push(filters.vehicle_type);
    }
    
    if (filters.status) {
      query += ' AND v.status = ?';
      params.push(filters.status);
    }
    
    if (filters.search) {
      query += ' AND (v.plate_number LIKE ? OR v.make LIKE ? OR v.model LIKE ?)';
      const searchTerm = `%${filters.search}%`;
      params.push(searchTerm, searchTerm, searchTerm);
    }
    
    query += ' ORDER BY v.created_at DESC';
    
    if (filters.limit) {
      query += ' LIMIT ?';
      params.push(parseInt(filters.limit));
    }
    
    return await executeQuery(query, params);
  }
  
  static async getVehicleById(vehicleId) {
    const query = `
      SELECT v.*, o.operator_name, o.contact_person, o.phone_number 
      FROM vehicles v 
      LEFT JOIN operators o ON v.operator_id = o.operator_id 
      WHERE v.vehicle_id = ?
    `;
    return await executeQuery(query, [vehicleId]);
  }
  
  static async updateVehicle(vehicleId, updateData) {
    const fields = [];
    const params = [];
    
    Object.keys(updateData).forEach(key => {
      if (updateData[key] !== undefined) {
        fields.push(`${key} = ?`);
        params.push(updateData[key]);
      }
    });
    
    if (fields.length === 0) {
      return { success: false, error: 'No fields to update' };
    }
    
    params.push(vehicleId);
    const query = `UPDATE vehicles SET ${fields.join(', ')} WHERE vehicle_id = ?`;
    
    return await executeQuery(query, params);
  }
  
  static async deleteVehicle(vehicleId) {
    const query = 'DELETE FROM vehicles WHERE vehicle_id = ?';
    return await executeQuery(query, [vehicleId]);
  }
  
  // =============================================
  // COMPLIANCE STATUS CRUD OPERATIONS
  // =============================================
  
  static async createComplianceStatus(complianceData) {
    const complianceId = `CS${Date.now()}`;
    const query = `
      INSERT INTO compliance_status (
        compliance_id, operator_id, vehicle_id, franchise_validity, 
        inspection_status, last_inspection_date, next_inspection_due, 
        violation_count, compliance_score
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    `;
    
    const params = [
      complianceId,
      complianceData.operator_id,
      complianceData.vehicle_id,
      complianceData.franchise_validity,
      complianceData.inspection_status,
      complianceData.last_inspection_date || null,
      complianceData.next_inspection_due || null,
      complianceData.violation_count || 0,
      complianceData.compliance_score || 100.00
    ];
    
    const result = await executeQuery(query, params);
    if (result.success) {
      return { success: true, compliance_id: complianceId, data: result.data };
    }
    return result;
  }
  
  static async getAllComplianceStatus(filters = {}) {
    let query = `
      SELECT cs.*, o.operator_name, o.contact_person, v.plate_number, v.vehicle_type 
      FROM compliance_status cs 
      LEFT JOIN operators o ON cs.operator_id = o.operator_id 
      LEFT JOIN vehicles v ON cs.vehicle_id = v.vehicle_id 
      WHERE 1=1
    `;
    const params = [];
    
    if (filters.franchise_validity) {
      query += ' AND cs.franchise_validity = ?';
      params.push(filters.franchise_validity);
    }
    
    if (filters.inspection_status) {
      query += ' AND cs.inspection_status = ?';
      params.push(filters.inspection_status);
    }
    
    if (filters.operator_id) {
      query += ' AND cs.operator_id = ?';
      params.push(filters.operator_id);
    }
    
    query += ' ORDER BY cs.last_updated DESC';
    
    return await executeQuery(query, params);
  }
  
  static async getComplianceById(complianceId) {
    const query = `
      SELECT cs.*, o.operator_name, o.contact_person, v.plate_number, v.vehicle_type 
      FROM compliance_status cs 
      LEFT JOIN operators o ON cs.operator_id = o.operator_id 
      LEFT JOIN vehicles v ON cs.vehicle_id = v.vehicle_id 
      WHERE cs.compliance_id = ?
    `;
    return await executeQuery(query, [complianceId]);
  }
  
  static async updateComplianceStatus(complianceId, updateData) {
    const fields = [];
    const params = [];
    
    Object.keys(updateData).forEach(key => {
      if (updateData[key] !== undefined) {
        fields.push(`${key} = ?`);
        params.push(updateData[key]);
      }
    });
    
    if (fields.length === 0) {
      return { success: false, error: 'No fields to update' };
    }
    
    params.push(complianceId);
    const query = `UPDATE compliance_status SET ${fields.join(', ')} WHERE compliance_id = ?`;
    
    return await executeQuery(query, params);
  }
  
  // =============================================
  // VIOLATION HISTORY CRUD OPERATIONS
  // =============================================
  
  static async createViolationHistory(violationData) {
    const violationHistoryId = `VH${Date.now()}`;
    const query = `
      INSERT INTO violation_history (
        violation_history_id, operator_id, vehicle_id, violation_id, 
        violation_date, violation_type, settlement_status, amount, repeat_offender_flag
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    `;
    
    const params = [
      violationHistoryId,
      violationData.operator_id,
      violationData.vehicle_id,
      violationData.violation_id,
      violationData.violation_date,
      violationData.violation_type,
      violationData.settlement_status || 'pending',
      violationData.amount,
      violationData.repeat_offender_flag || false
    ];
    
    const result = await executeQuery(query, params);
    if (result.success) {
      return { success: true, violation_history_id: violationHistoryId, data: result.data };
    }
    return result;
  }
  
  static async getAllViolationHistory(filters = {}) {
    let query = `
      SELECT vh.*, o.operator_name, o.contact_person, v.plate_number 
      FROM violation_history vh 
      LEFT JOIN operators o ON vh.operator_id = o.operator_id 
      LEFT JOIN vehicles v ON vh.vehicle_id = v.vehicle_id 
      WHERE 1=1
    `;
    const params = [];
    
    if (filters.operator_id) {
      query += ' AND vh.operator_id = ?';
      params.push(filters.operator_id);
    }
    
    if (filters.vehicle_id) {
      query += ' AND vh.vehicle_id = ?';
      params.push(filters.vehicle_id);
    }
    
    if (filters.settlement_status) {
      query += ' AND vh.settlement_status = ?';
      params.push(filters.settlement_status);
    }
    
    if (filters.repeat_offender_flag !== undefined) {
      query += ' AND vh.repeat_offender_flag = ?';
      params.push(filters.repeat_offender_flag);
    }
    
    query += ' ORDER BY vh.violation_date DESC';
    
    return await executeQuery(query, params);
  }
  
  static async getViolationHistoryById(violationHistoryId) {
    const query = `
      SELECT vh.*, o.operator_name, o.contact_person, v.plate_number 
      FROM violation_history vh 
      LEFT JOIN operators o ON vh.operator_id = o.operator_id 
      LEFT JOIN vehicles v ON vh.vehicle_id = v.vehicle_id 
      WHERE vh.violation_history_id = ?
    `;
    return await executeQuery(query, [violationHistoryId]);
  }
  
  static async updateViolationHistory(violationHistoryId, updateData) {
    const fields = [];
    const params = [];
    
    Object.keys(updateData).forEach(key => {
      if (updateData[key] !== undefined) {
        fields.push(`${key} = ?`);
        params.push(updateData[key]);
      }
    });
    
    if (fields.length === 0) {
      return { success: false, error: 'No fields to update' };
    }
    
    params.push(violationHistoryId);
    const query = `UPDATE violation_history SET ${fields.join(', ')} WHERE violation_history_id = ?`;
    
    return await executeQuery(query, params);
  }
  
  // =============================================
  // COMPLIANCE REPORTS CRUD OPERATIONS
  // =============================================
  
  // Create compliance report
  static async createComplianceReport(reportData) {
    const {
      operator_id,
      vehicle_id,
      report_type,
      report_period_start,
      report_period_end,
      compliance_score,
      violations_count,
      recommendations,
      generated_by,
      status
    } = reportData;

    const query = `
      INSERT INTO compliance_reports (
        operator_id, vehicle_id, report_type, report_period_start, 
        report_period_end, compliance_score, violations_count, 
        recommendations, generated_by, status
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    `;

    const params = [
      operator_id, vehicle_id, report_type, report_period_start,
      report_period_end, compliance_score, violations_count,
      recommendations, generated_by, status || 'draft'
    ];

    const result = await executeQuery(query, params);
    return { report_id: result.insertId, ...reportData };
  }

  // Get all compliance reports
  static async getAllComplianceReports(filters = {}) {
    let query = `
      SELECT cr.*, o.operator_name, o.contact_person, v.plate_number 
      FROM compliance_reports cr 
      LEFT JOIN operators o ON cr.operator_id = o.operator_id 
      LEFT JOIN vehicles v ON cr.vehicle_id = v.vehicle_id 
      WHERE 1=1
    `;
    let params = [];

    if (filters.operator_id) {
      query += ' AND cr.operator_id = ?';
      params.push(filters.operator_id);
    }

    if (filters.report_type) {
      query += ' AND cr.report_type = ?';
      params.push(filters.report_type);
    }

    if (filters.status) {
      query += ' AND cr.status = ?';
      params.push(filters.status);
    }

    query += ' ORDER BY cr.created_at DESC';
    
    if (filters.limit) {
      query += ' LIMIT ?';
      params.push(parseInt(filters.limit));
    }

    const result = await executeQuery(query, params);
    return result;
  }

  // Get compliance report by ID
  static async getComplianceReportById(reportId) {
    const query = `
      SELECT cr.*, o.operator_name, o.contact_person, v.plate_number 
      FROM compliance_reports cr 
      LEFT JOIN operators o ON cr.operator_id = o.operator_id 
      LEFT JOIN vehicles v ON cr.vehicle_id = v.vehicle_id 
      WHERE cr.report_id = ?
    `;

    const result = await executeQuery(query, [reportId]);
    if (result.length > 0) {
      return { success: true, data: result[0] };
    }
    return { success: false, message: 'Compliance report not found' };
  }

  // Update compliance report
  static async updateComplianceReport(reportId, updateData) {
    const allowedFields = [
      'report_type', 'report_period_start', 'report_period_end',
      'compliance_score', 'violations_count', 'recommendations', 'status'
    ];
    
    const updateFields = [];
    const params = [];
    
    Object.keys(updateData).forEach(key => {
      if (allowedFields.includes(key) && updateData[key] !== undefined) {
        updateFields.push(`${key} = ?`);
        params.push(updateData[key]);
      }
    });
    
    if (updateFields.length === 0) {
      return { success: false, message: 'No valid fields to update' };
    }
    
    params.push(reportId);
    const query = `UPDATE compliance_reports SET ${updateFields.join(', ')} WHERE report_id = ?`;
    
    const result = await executeQuery(query, params);
    if (result.affectedRows > 0) {
      return { success: true, message: 'Compliance report updated successfully' };
    }
    return { success: false, message: 'Compliance report not found or no changes made' };
  }

  // Delete compliance report
  static async deleteComplianceReport(reportId) {
    const query = 'DELETE FROM compliance_reports WHERE report_id = ?';
    const result = await executeQuery(query, [reportId]);
    
    if (result.affectedRows > 0) {
      return { success: true, message: 'Compliance report deleted successfully' };
    }
    return { success: false, message: 'Compliance report not found' };
  }

  // =============================================
  // ANALYTICS AND REPORTS
  // =============================================
  
  static async getComplianceReport() {
    const query = `
      SELECT 
        COUNT(*) as total_operators,
        SUM(CASE WHEN franchise_validity = 'valid' THEN 1 ELSE 0 END) as valid_franchises,
        SUM(CASE WHEN inspection_status = 'compliant' THEN 1 ELSE 0 END) as compliant_inspections,
        AVG(compliance_score) as avg_compliance_score,
        SUM(violation_count) as total_violations
      FROM compliance_status
    `;
    return await executeQuery(query);
  }
  
  static async getViolationSummary(operatorId) {
    const query = `
      SELECT 
        COUNT(*) as total_violations,
        SUM(amount) as total_amount,
        SUM(CASE WHEN settlement_status = 'paid' THEN 1 ELSE 0 END) as paid_violations,
        SUM(CASE WHEN settlement_status = 'pending' THEN 1 ELSE 0 END) as pending_violations,
        MAX(violation_date) as last_violation_date
      FROM violation_history 
      WHERE operator_id = ?
    `;
    return await executeQuery(query, [operatorId]);
  }
}

module.exports = PuvDatabase;