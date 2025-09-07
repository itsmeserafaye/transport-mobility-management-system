const { executeQuery, executeTransaction } = require('../config/database');

class ParkingTerminalManagement {
  // =============================================
  // TERMINALS CRUD OPERATIONS
  // =============================================
  
  static async createTerminal(terminalData) {
    const terminalId = `TRM-${Date.now()}`;
    const query = `
      INSERT INTO terminals (
        terminal_id, terminal_name, terminal_code, location, 
        address, capacity, current_occupancy, terminal_type, 
        operating_hours, contact_person, contact_number, 
        latitude, longitude, status
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    `;
    
    const params = [
      terminalId,
      terminalData.terminal_name,
      terminalData.terminal_code,
      terminalData.location,
      terminalData.address,
      terminalData.capacity,
      terminalData.current_occupancy || 0,
      terminalData.terminal_type || 'main',
      terminalData.operating_hours,
      terminalData.contact_person,
      terminalData.contact_number,
      terminalData.latitude || null,
      terminalData.longitude || null,
      terminalData.status || 'active'
    ];
    
    const result = await executeQuery(query, params);
    if (result.success) {
      return { success: true, terminal_id: terminalId, data: result.data };
    }
    return result;
  }
  
  static async getAllTerminals(filters = {}) {
    let query = `
      SELECT t.*, 
             COUNT(ta.assignment_id) as active_assignments,
             ROUND((t.current_occupancy / t.capacity) * 100, 2) as utilization_rate
      FROM terminals t 
      LEFT JOIN terminal_assignments ta ON t.terminal_id = ta.terminal_id AND ta.status = 'active'
      WHERE 1=1
    `;
    const params = [];
    
    if (filters.terminal_type) {
      query += ' AND t.terminal_type = ?';
      params.push(filters.terminal_type);
    }
    
    if (filters.location) {
      query += ' AND t.location LIKE ?';
      params.push(`%${filters.location}%`);
    }
    
    if (filters.status) {
      query += ' AND t.status = ?';
      params.push(filters.status);
    }
    
    if (filters.terminal_code) {
      query += ' AND t.terminal_code = ?';
      params.push(filters.terminal_code);
    }
    
    query += ' GROUP BY t.terminal_id ORDER BY t.terminal_name ASC';
    
    if (filters.limit) {
      query += ' LIMIT ?';
      params.push(parseInt(filters.limit));
    }
    
    return await executeQuery(query, params);
  }
  
  static async getTerminalById(terminalId) {
    const query = `
      SELECT t.*, 
             COUNT(ta.assignment_id) as active_assignments,
             ROUND((t.current_occupancy / t.capacity) * 100, 2) as utilization_rate
      FROM terminals t 
      LEFT JOIN terminal_assignments ta ON t.terminal_id = ta.terminal_id AND ta.status = 'active'
      WHERE t.terminal_id = ?
      GROUP BY t.terminal_id
    `;
    return await executeQuery(query, [terminalId]);
  }
  
  static async updateTerminal(terminalId, updateData) {
    const allowedFields = [
      'terminal_name', 'terminal_code', 'location', 'address', 
      'capacity', 'current_occupancy', 'terminal_type', 'operating_hours',
      'contact_person', 'contact_number', 'latitude', 'longitude', 'status'
    ];
    
    const fields = [];
    const params = [];
    
    Object.keys(updateData).forEach(key => {
      if (updateData[key] !== undefined && allowedFields.includes(key)) {
        fields.push(`${key} = ?`);
        params.push(updateData[key]);
      }
    });
    
    if (fields.length === 0) {
      return { success: false, error: 'No valid fields to update' };
    }
    
    params.push(terminalId);
    const query = `UPDATE terminals SET ${fields.join(', ')} WHERE terminal_id = ?`;
    
    return await executeQuery(query, params);
  }
  
  static async deleteTerminal(terminalId) {
    const query = 'DELETE FROM terminals WHERE terminal_id = ?';
    return await executeQuery(query, [terminalId]);
  }
  
  // =============================================
  // TERMINAL ASSIGNMENTS CRUD OPERATIONS
  // =============================================
  
  static async createTerminalAssignment(assignmentData) {
    const assignmentId = `TA-${Date.now()}`;
    const query = `
      INSERT INTO terminal_assignments (
        assignment_id, terminal_id, operator_id, vehicle_id, 
        franchise_id, assignment_type, route_assigned, 
        start_date, end_date, assignment_date, status, 
        assigned_by, remarks
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    `;
    
    const params = [
      assignmentId,
      assignmentData.terminal_id,
      assignmentData.operator_id,
      assignmentData.vehicle_id || null,
      assignmentData.franchise_id || null,
      assignmentData.assignment_type || 'permanent',
      assignmentData.route_assigned || null,
      assignmentData.start_date,
      assignmentData.end_date || null,
      assignmentData.assignment_date || new Date().toISOString().split('T')[0],
      assignmentData.status || 'active',
      assignmentData.assigned_by,
      assignmentData.remarks || null
    ];
    
    const result = await executeQuery(query, params);
    if (result.success) {
      return { success: true, assignment_id: assignmentId, data: result.data };
    }
    return result;
  }
  
  static async getAllTerminalAssignments(filters = {}) {
    let query = `
      SELECT ta.*, t.terminal_name, t.location as terminal_location,
             o.first_name, o.last_name, o.contact_number,
             v.plate_number, v.vehicle_type, v.make, v.model,
             f.franchise_name
      FROM terminal_assignments ta 
      LEFT JOIN terminals t ON ta.terminal_id = t.terminal_id 
      LEFT JOIN operators o ON ta.operator_id = o.operator_id 
      LEFT JOIN vehicles v ON ta.vehicle_id = v.vehicle_id 
      LEFT JOIN franchises f ON ta.franchise_id = f.franchise_id 
      WHERE 1=1
    `;
    const params = [];
    
    if (filters.terminal_id) {
      query += ' AND ta.terminal_id = ?';
      params.push(filters.terminal_id);
    }
    
    if (filters.operator_id) {
      query += ' AND ta.operator_id = ?';
      params.push(filters.operator_id);
    }
    
    if (filters.vehicle_id) {
      query += ' AND ta.vehicle_id = ?';
      params.push(filters.vehicle_id);
    }
    
    if (filters.franchise_id) {
      query += ' AND ta.franchise_id = ?';
      params.push(filters.franchise_id);
    }
    
    if (filters.assignment_type) {
      query += ' AND ta.assignment_type = ?';
      params.push(filters.assignment_type);
    }
    
    if (filters.status) {
      query += ' AND ta.status = ?';
      params.push(filters.status);
    }
    
    if (filters.active_only) {
      query += ' AND ta.status = "active"';
    }
    
    query += ' ORDER BY ta.assignment_date DESC';
    
    if (filters.limit) {
      query += ' LIMIT ?';
      params.push(parseInt(filters.limit));
    }
    
    return await executeQuery(query, params);
  }
  
  static async getTerminalAssignmentById(assignmentId) {
    const query = `
      SELECT ta.*, t.terminal_name, t.location as terminal_location, t.address,
             o.first_name, o.last_name, o.contact_number, o.address as operator_address,
             v.plate_number, v.vehicle_type, v.make, v.model,
             f.franchise_name, f.contact_person
      FROM terminal_assignments ta 
      LEFT JOIN terminals t ON ta.terminal_id = t.terminal_id 
      LEFT JOIN operators o ON ta.operator_id = o.operator_id 
      LEFT JOIN vehicles v ON ta.vehicle_id = v.vehicle_id 
      LEFT JOIN franchises f ON ta.franchise_id = f.franchise_id 
      WHERE ta.assignment_id = ?
    `;
    return await executeQuery(query, [assignmentId]);
  }
  
  static async updateTerminalAssignment(assignmentId, updateData) {
    const allowedFields = [
      'terminal_id', 'operator_id', 'vehicle_id', 'franchise_id',
      'assignment_type', 'route_assigned', 'start_date', 'end_date',
      'status', 'remarks'
    ];
    
    const fields = [];
    const params = [];
    
    Object.keys(updateData).forEach(key => {
      if (updateData[key] !== undefined && allowedFields.includes(key)) {
        fields.push(`${key} = ?`);
        params.push(updateData[key]);
      }
    });
    
    if (fields.length === 0) {
      return { success: false, error: 'No valid fields to update' };
    }
    
    params.push(assignmentId);
    const query = `UPDATE terminal_assignments SET ${fields.join(', ')} WHERE assignment_id = ?`;
    
    return await executeQuery(query, params);
  }
  
  static async deleteTerminalAssignment(assignmentId) {
    const query = 'DELETE FROM terminal_assignments WHERE assignment_id = ?';
    return await executeQuery(query, [assignmentId]);
  }
  
  // =============================================
  // TERMINAL ASSIGNMENT OPERATIONS
  // =============================================
  
  static async assignOperatorToTerminal(assignmentData) {
    // Check if operator is already assigned to another terminal
    const existingAssignment = await this.getAllTerminalAssignments({
      operator_id: assignmentData.operator_id,
      active_only: true
    });
    
    if (existingAssignment.success && existingAssignment.data.length > 0) {
      return { success: false, error: 'Operator is already assigned to another terminal' };
    }
    
    return await this.createTerminalAssignment(assignmentData);
  }
  
  static async reassignOperator(assignmentId, newTerminalId, reassignedBy, remarks = null) {
    // End current assignment
    const endResult = await this.updateTerminalAssignment(assignmentId, {
      status: 'inactive',
      end_date: new Date().toISOString().split('T')[0],
      remarks: remarks || 'Reassigned to another terminal'
    });
    
    if (!endResult.success) {
      return endResult;
    }
    
    // Get current assignment details
    const currentAssignment = await this.getTerminalAssignmentById(assignmentId);
    if (!currentAssignment.success || currentAssignment.data.length === 0) {
      return { success: false, error: 'Assignment not found' };
    }
    
    const assignment = currentAssignment.data[0];
    
    // Create new assignment
    const newAssignmentData = {
      terminal_id: newTerminalId,
      operator_id: assignment.operator_id,
      vehicle_id: assignment.vehicle_id,
      franchise_id: assignment.franchise_id,
      assignment_type: assignment.assignment_type,
      route_assigned: assignment.route_assigned,
      start_date: new Date().toISOString().split('T')[0],
      assigned_by: reassignedBy,
      remarks: `Reassigned from terminal ${assignment.terminal_id}`
    };
    
    return await this.createTerminalAssignment(newAssignmentData);
  }
  
  static async endAssignment(assignmentId, endData) {
    const updateData = {
      status: 'inactive',
      end_date: endData.end_date || new Date().toISOString().split('T')[0],
      remarks: endData.remarks || 'Assignment ended'
    };
    
    return await this.updateTerminalAssignment(assignmentId, updateData);
  }
  
  static async getOperatorCurrentAssignment(operatorId) {
    const query = `
      SELECT ta.*, t.terminal_name, t.location as terminal_location, t.address,
             v.plate_number, v.vehicle_type, v.make, v.model,
             f.franchise_name
      FROM terminal_assignments ta 
      LEFT JOIN terminals t ON ta.terminal_id = t.terminal_id 
      LEFT JOIN vehicles v ON ta.vehicle_id = v.vehicle_id 
      LEFT JOIN franchises f ON ta.franchise_id = f.franchise_id 
      WHERE ta.operator_id = ? AND ta.status = 'active'
      ORDER BY ta.assignment_date DESC
      LIMIT 1
    `;
    return await executeQuery(query, [operatorId]);
  }
  
  // =============================================
  // ANALYTICS AND REPORTS
  // =============================================
  
  static async getTerminalAssignmentStats(terminalId, filters = {}) {
    let query = `
      SELECT 
        t.terminal_name,
        t.capacity,
        t.current_occupancy,
        COUNT(ta.assignment_id) as total_assignments,
        SUM(CASE WHEN ta.status = 'active' THEN 1 ELSE 0 END) as active_assignments,
        SUM(CASE WHEN ta.status = 'inactive' THEN 1 ELSE 0 END) as inactive_assignments,
        ROUND((t.current_occupancy / t.capacity) * 100, 2) as utilization_rate
      FROM terminals t 
      LEFT JOIN terminal_assignments ta ON t.terminal_id = ta.terminal_id 
      WHERE t.terminal_id = ?
    `;
    const params = [terminalId];
    
    if (filters.date_from) {
      query += ' AND DATE(ta.assignment_date) >= ?';
      params.push(filters.date_from);
    }
    
    if (filters.date_to) {
      query += ' AND DATE(ta.assignment_date) <= ?';
      params.push(filters.date_to);
    }
    
    query += ' GROUP BY t.terminal_id, t.terminal_name, t.capacity, t.current_occupancy';
    
    return await executeQuery(query, params);
  }
  
  static async getAssignmentsByTerminal(filters = {}) {
    let query = `
      SELECT 
        t.terminal_id,
        t.terminal_name,
        t.location,
        COUNT(ta.assignment_id) as total_assignments,
        SUM(CASE WHEN ta.status = 'active' THEN 1 ELSE 0 END) as active_assignments,
        SUM(CASE WHEN ta.assignment_type = 'permanent' THEN 1 ELSE 0 END) as permanent_assignments,
        SUM(CASE WHEN ta.assignment_type = 'temporary' THEN 1 ELSE 0 END) as temporary_assignments
      FROM terminals t 
      LEFT JOIN terminal_assignments ta ON t.terminal_id = ta.terminal_id 
      WHERE 1=1
    `;
    const params = [];
    
    if (filters.date_from) {
      query += ' AND DATE(ta.assignment_date) >= ?';
      params.push(filters.date_from);
    }
    
    if (filters.date_to) {
      query += ' AND DATE(ta.assignment_date) <= ?';
      params.push(filters.date_to);
    }
    
    query += ' GROUP BY t.terminal_id, t.terminal_name, t.location ORDER BY active_assignments DESC';
    
    return await executeQuery(query, params);
  }
  
  static async getAssignmentTrends(period = 'daily', filters = {}) {
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
      default:
        dateFormat = '%Y-%m-%d';
    }
    
    let query = `
      SELECT 
        DATE_FORMAT(assignment_date, ?) as period,
        COUNT(*) as total_assignments,
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_assignments,
        COUNT(DISTINCT terminal_id) as terminals_involved,
        COUNT(DISTINCT operator_id) as operators_assigned
      FROM terminal_assignments 
      WHERE 1=1
    `;
    const params = [dateFormat];
    
    if (filters.terminal_id) {
      query += ' AND terminal_id = ?';
      params.push(filters.terminal_id);
    }
    
    if (filters.date_from) {
      query += ' AND DATE(assignment_date) >= ?';
      params.push(filters.date_from);
    }
    
    if (filters.date_to) {
      query += ' AND DATE(assignment_date) <= ?';
      params.push(filters.date_to);
    }
    
    query += ' GROUP BY DATE_FORMAT(assignment_date, ?) ORDER BY period ASC';
    params.push(dateFormat);
    
    return await executeQuery(query, params);
  }
  
  static async getCurrentTerminalOccupancy() {
    const query = `
      SELECT 
        t.terminal_id,
        t.terminal_name,
        t.location,
        t.capacity,
        t.current_occupancy,
        COUNT(ta.assignment_id) as active_assignments,
        ROUND((t.current_occupancy / t.capacity) * 100, 2) as utilization_rate,
        ROUND((COUNT(ta.assignment_id) / t.capacity) * 100, 2) as assignment_rate
      FROM terminals t 
      LEFT JOIN terminal_assignments ta ON t.terminal_id = ta.terminal_id AND ta.status = 'active'
      WHERE t.status = 'active'
      GROUP BY t.terminal_id, t.terminal_name, t.location, t.capacity, t.current_occupancy
      ORDER BY utilization_rate DESC
    `;
    
    return await executeQuery(query);
  }
  
  static async getFrequentOperators(filters = {}) {
    let query = `
      SELECT 
        ta.operator_id,
        o.first_name,
        o.last_name,
        o.contact_number,
        COUNT(*) as assignment_count,
        COUNT(DISTINCT ta.terminal_id) as terminals_assigned,
        MIN(ta.assignment_date) as first_assignment,
        MAX(ta.assignment_date) as last_assignment
      FROM terminal_assignments ta 
      LEFT JOIN operators o ON ta.operator_id = o.operator_id 
      WHERE ta.operator_id IS NOT NULL
    `;
    const params = [];
    
    if (filters.terminal_id) {
      query += ' AND ta.terminal_id = ?';
      params.push(filters.terminal_id);
    }
    
    if (filters.date_from) {
      query += ' AND DATE(ta.assignment_date) >= ?';
      params.push(filters.date_from);
    }
    
    if (filters.date_to) {
      query += ' AND DATE(ta.assignment_date) <= ?';
      params.push(filters.date_to);
    }
    
    query += ` 
      GROUP BY ta.operator_id, o.first_name, o.last_name, o.contact_number 
      HAVING assignment_count >= ? 
      ORDER BY assignment_count DESC
    `;
    params.push(filters.min_assignments || 3);
    
    return await executeQuery(query, params);
   }

   // =============================================
   // TERMINAL CAPACITY TRACKING OPERATIONS
   // =============================================

   static async createCapacityTracking(trackingData) {
     const trackingId = `TCT-${Date.now()}`;
     const query = `
       INSERT INTO terminal_capacity_tracking (
         tracking_id, terminal_id, date_recorded, total_capacity,
         occupied_slots, available_slots, utilization_rate,
         peak_hours, recorded_by
       ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
     `;
     
     const params = [
       trackingId,
       trackingData.terminal_id,
       trackingData.date_recorded || new Date().toISOString().split('T')[0],
       trackingData.total_capacity,
       trackingData.occupied_slots,
       trackingData.available_slots,
       trackingData.utilization_rate,
       trackingData.peak_hours || null,
       trackingData.recorded_by
     ];
     
     const result = await executeQuery(query, params);
     if (result.success) {
       return { success: true, tracking_id: trackingId, data: result.data };
     }
     return result;
   }

   static async getCapacityTrackingHistory(terminalId, filters = {}) {
     let query = `
       SELECT tct.*, t.terminal_name, t.location
       FROM terminal_capacity_tracking tct
       LEFT JOIN terminals t ON tct.terminal_id = t.terminal_id
       WHERE tct.terminal_id = ?
     `;
     const params = [terminalId];
     
     if (filters.date_from) {
       query += ' AND tct.date_recorded >= ?';
       params.push(filters.date_from);
     }
     
     if (filters.date_to) {
       query += ' AND tct.date_recorded <= ?';
       params.push(filters.date_to);
     }
     
     query += ' ORDER BY tct.date_recorded DESC';
     
     if (filters.limit) {
       query += ' LIMIT ?';
       params.push(parseInt(filters.limit));
     }
     
     return await executeQuery(query, params);
   }

   static async updateTerminalOccupancy(terminalId, occupancyData) {
     const updateData = {
       current_occupancy: occupancyData.current_occupancy
     };
     
     const updateResult = await this.updateTerminal(terminalId, updateData);
     
     if (updateResult.success && occupancyData.record_tracking) {
       // Record capacity tracking
       const terminal = await this.getTerminalById(terminalId);
       if (terminal.success && terminal.data.length > 0) {
         const terminalData = terminal.data[0];
         const trackingData = {
           terminal_id: terminalId,
           total_capacity: terminalData.capacity,
           occupied_slots: occupancyData.current_occupancy,
           available_slots: terminalData.capacity - occupancyData.current_occupancy,
           utilization_rate: Math.round((occupancyData.current_occupancy / terminalData.capacity) * 100 * 100) / 100,
           peak_hours: occupancyData.peak_hours || null,
           recorded_by: occupancyData.recorded_by || 'System'
         };
         
         await this.createCapacityTracking(trackingData);
       }
     }
     
     return updateResult;
   }
}

module.exports = ParkingTerminalManagement;