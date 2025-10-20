const { executeQuery, executeTransaction } = require('../config/database');

class VehicleInspectionRegistration {
  // =============================================
  // INSPECTIONS CRUD OPERATIONS
  // =============================================
  
  // Create new inspection record
  static async createInspectionRecord(inspectionData) {
    const {
      vehicle_id, inspector_name, inspection_date, inspection_type,
      inspection_status, inspection_results, next_inspection_date,
      certificate_number, certificate_expiry, notes
    } = inspectionData;
    
    const query = `
      INSERT INTO inspection_records (
        vehicle_id, inspector_name, inspection_date, inspection_type,
        inspection_status, inspection_results, next_inspection_date,
        certificate_number, certificate_expiry, notes
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    `;
    
    try {
      const result = await executeQuery(query, [
        vehicle_id, inspector_name, inspection_date, inspection_type,
        inspection_status, JSON.stringify(inspection_results), next_inspection_date,
        certificate_number, certificate_expiry, notes
      ]);
      
      return {
        success: true,
        inspection_id: result.insertId,
        message: 'Inspection record created successfully'
      };
    } catch (error) {
      return { success: false, message: 'Error creating inspection record', error: error.message };
    }
  }
  
  // Get all inspection records with pagination and filters
  static async getAllInspectionRecords(page = 1, limit = 10, filters = {}) {
    const offset = (page - 1) * limit;
    let query = `
      SELECT ir.*, v.plate_number, v.vehicle_type, v.make, v.model,
             CONCAT(o.first_name, ' ', o.last_name) as operator_name, o.contact_number
      FROM inspection_records ir
      LEFT JOIN vehicles v ON ir.vehicle_id = v.vehicle_id
      LEFT JOIN operators o ON v.operator_id = o.operator_id
      WHERE 1=1
    `;
    let countQuery = `
      SELECT COUNT(*) as total 
      FROM inspection_records ir
      LEFT JOIN vehicles v ON ir.vehicle_id = v.vehicle_id
      LEFT JOIN operators o ON v.operator_id = o.operator_id
      WHERE 1=1
    `;
    let params = [];
    
    // Apply filters
    if (filters.inspection_status) {
      query += ' AND ir.inspection_status = ?';
      countQuery += ' AND ir.inspection_status = ?';
      params.push(filters.inspection_status);
    }
    
    if (filters.inspection_type) {
      query += ' AND ir.inspection_type = ?';
      countQuery += ' AND ir.inspection_type = ?';
      params.push(filters.inspection_type);
    }
    
    if (filters.vehicle_id) {
      query += ' AND ir.vehicle_id = ?';
      countQuery += ' AND ir.vehicle_id = ?';
      params.push(filters.vehicle_id);
    }
    
    if (filters.operator_id) {
      query += ' AND o.operator_id = ?';
      countQuery += ' AND o.operator_id = ?';
      params.push(filters.operator_id);
    }
    
    if (filters.date_from) {
      query += ' AND DATE(ir.inspection_date) >= ?';
      countQuery += ' AND DATE(ir.inspection_date) >= ?';
      params.push(filters.date_from);
    }
    
    if (filters.date_to) {
      query += ' AND DATE(ir.inspection_date) <= ?';
      countQuery += ' AND DATE(ir.inspection_date) <= ?';
      params.push(filters.date_to);
    }
    
    if (filters.inspector_name) {
      query += ' AND ir.inspector_name LIKE ?';
      countQuery += ' AND ir.inspector_name LIKE ?';
      params.push(`%${filters.inspector_name}%`);
    }
    
    if (filters.expiring_soon) {
      const daysAhead = filters.expiring_soon || 30;
      query += ' AND ir.certificate_expiry <= DATE_ADD(CURDATE(), INTERVAL ? DAY)';
      countQuery += ' AND ir.certificate_expiry <= DATE_ADD(CURDATE(), INTERVAL ? DAY)';
      params.push(daysAhead);
    }
    
    if (filters.search) {
      query += ' AND (v.plate_number LIKE ? OR CONCAT(o.first_name, " ", o.last_name) LIKE ? OR ir.inspector_name LIKE ?)';
      countQuery += ' AND (v.plate_number LIKE ? OR CONCAT(o.first_name, " ", o.last_name) LIKE ? OR ir.inspector_name LIKE ?)';
      const searchTerm = `%${filters.search}%`;
      params.push(searchTerm, searchTerm, searchTerm);
    }
    
    query += ' ORDER BY ir.inspection_date DESC LIMIT ? OFFSET ?';
    const queryParams = [...params, limit, offset];
    
    try {
      const [inspections, totalResult] = await Promise.all([
        executeQuery(query, queryParams),
        executeQuery(countQuery, params)
      ]);
      
      const total = totalResult && totalResult[0] ? totalResult[0].total : 0;
      
      return {
        success: true,
        data: inspections || [],
        pagination: {
          current_page: page,
          per_page: limit,
          total: total,
          total_pages: Math.ceil(total / limit)
        }
      };
    } catch (error) {
      return {
        success: false,
        message: 'Error fetching inspection records',
        error: error.message
      };
    }
  }
  
  // Get inspection record by ID
  static async getInspectionRecordById(inspectionId) {
    const query = `
      SELECT ir.*, v.plate_number, v.vehicle_type, v.make, v.model,
             CONCAT(o.first_name, ' ', o.last_name) as operator_name, o.contact_number, o.email
      FROM inspection_records ir
      LEFT JOIN vehicles v ON ir.vehicle_id = v.vehicle_id
      LEFT JOIN operators o ON v.operator_id = o.operator_id
      WHERE ir.inspection_id = ?
    `;
    
    const result = await executeQuery(query, [inspectionId]);
    return result.length > 0 ? result[0] : null;
  }
  
  // Update inspection record
  static async updateInspectionRecord(inspectionId, updateData) {
    const allowedFields = [
      'inspector_name', 'inspection_date', 'inspection_type', 'inspection_status',
      'inspection_results', 'next_inspection_date', 'certificate_number',
      'certificate_expiry', 'notes'
    ];
    
    const fields = [];
    const params = [];
    
    Object.keys(updateData).forEach(key => {
      if (allowedFields.includes(key) && updateData[key] !== undefined) {
        if (key === 'inspection_results') {
          fields.push(`${key} = ?`);
          params.push(JSON.stringify(updateData[key]));
        } else {
          fields.push(`${key} = ?`);
          params.push(updateData[key]);
        }
      }
    });
    
    if (fields.length === 0) {
      return { success: false, error: 'No valid fields to update' };
    }
    
    params.push(inspectionId);
    const query = `UPDATE inspection_records SET ${fields.join(', ')} WHERE inspection_id = ?`;
    
    return await executeQuery(query, params);
  }
  
  // Delete inspection record
  static async deleteInspectionRecord(inspectionId) {
    const query = 'DELETE FROM inspection_records WHERE inspection_id = ?';
    return await executeQuery(query, [inspectionId]);
  }
  
  // =============================================
  // INSPECTION SCHEDULING AND MANAGEMENT
  // =============================================
  
  // Schedule inspection using existing createInspectionRecord method
  static async scheduleInspection(scheduleData) {
    const inspectionData = {
      vehicle_id: scheduleData.vehicle_id,
      inspector_name: scheduleData.inspector_name || null,
      inspection_date: scheduleData.inspection_date,
      inspection_type: scheduleData.inspection_type,
      inspection_status: 'scheduled',
      next_inspection_date: scheduleData.next_inspection_date,
      notes: scheduleData.notes || null
    };
    
    return await this.createInspectionRecord(inspectionData);
  }
  
  // Complete inspection using existing updateInspectionRecord method
  static async completeInspection(inspectionId, completionData) {
    const updateData = {
      inspection_status: completionData.passed ? 'passed' : 'failed',
      inspection_results: completionData.inspection_results || {},
      certificate_number: completionData.certificate_number,
      certificate_expiry: completionData.certificate_expiry,
      notes: completionData.notes
    };
    
    return await this.updateInspectionRecord(inspectionId, updateData);
  }
  
  // =============================================
  // ANALYTICS AND REPORTS
  // =============================================
  
  static async getInspectionSummary(filters = {}) {
    let query = `
      SELECT 
        COUNT(*) as total_inspections,
        SUM(CASE WHEN inspection_status = 'passed' THEN 1 ELSE 0 END) as passed_inspections,
        SUM(CASE WHEN inspection_status = 'failed' THEN 1 ELSE 0 END) as failed_inspections,
        SUM(CASE WHEN inspection_status = 'scheduled' THEN 1 ELSE 0 END) as scheduled_inspections,
        SUM(CASE WHEN inspection_status = 'pending' THEN 1 ELSE 0 END) as pending_inspections
      FROM inspection_records 
      WHERE 1=1
    `;
    const params = [];
    
    if (filters.date_from) {
      query += ' AND DATE(inspection_date) >= ?';
      params.push(filters.date_from);
    }
    
    if (filters.date_to) {
      query += ' AND DATE(inspection_date) <= ?';
      params.push(filters.date_to);
    }
    
    return await executeQuery(query, params);
  }
  
  static async getExpiringInspections(days = 30) {
    const query = `
      SELECT 
        ir.inspection_id,
        ir.vehicle_id,
        v.plate_number,
        ir.certificate_expiry as expiry_date,
        DATEDIFF(ir.certificate_expiry, CURDATE()) as days_until_expiry,
        o.operator_name,
        o.contact_person,
        o.phone_number
      FROM inspection_records ir
      LEFT JOIN vehicles v ON ir.vehicle_id = v.vehicle_id
      LEFT JOIN operators o ON v.operator_id = o.operator_id
      WHERE ir.certificate_expiry <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
        AND ir.inspection_status = 'passed'
      ORDER BY days_until_expiry ASC
    `;
    
    return await executeQuery(query, [days]);
  }
  
  static async getInspectionsByType(filters = {}) {
    let query = `
      SELECT 
        inspection_type,
        COUNT(*) as inspection_count,
        SUM(CASE WHEN inspection_status = 'passed' THEN 1 ELSE 0 END) as passed_count,
        SUM(CASE WHEN inspection_status = 'failed' THEN 1 ELSE 0 END) as failed_count
      FROM inspection_records 
      WHERE 1=1
    `;
    const params = [];
    
    if (filters.date_from) {
      query += ' AND DATE(inspection_date) >= ?';
      params.push(filters.date_from);
    }
    
    if (filters.date_to) {
      query += ' AND DATE(inspection_date) <= ?';
      params.push(filters.date_to);
    }
    
    query += ' GROUP BY inspection_type ORDER BY inspection_count DESC';
    
    return await executeQuery(query, params);
  }
  
  static async getInspectorPerformance(filters = {}) {
    let query = `
      SELECT 
        inspector_name,
        COUNT(*) as inspections_conducted,
        SUM(CASE WHEN inspection_status = 'passed' THEN 1 ELSE 0 END) as passed_inspections,
        SUM(CASE WHEN inspection_status = 'failed' THEN 1 ELSE 0 END) as failed_inspections,
        ROUND((SUM(CASE WHEN inspection_status = 'passed' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as pass_rate
      FROM inspection_records 
      WHERE inspector_name IS NOT NULL
    `;
    const params = [];
    
    if (filters.date_from) {
      query += ' AND DATE(inspection_date) >= ?';
      params.push(filters.date_from);
    }
    
    if (filters.date_to) {
      query += ' AND DATE(inspection_date) <= ?';
      params.push(filters.date_to);
    }
    
    if (filters.inspector_name) {
      query += ' AND inspector_name LIKE ?';
      params.push(`%${filters.inspector_name}%`);
    }
    
    query += ' GROUP BY inspector_name ORDER BY inspections_conducted DESC';
    
    return await executeQuery(query, params);
  }
}

module.exports = VehicleInspectionRegistration;