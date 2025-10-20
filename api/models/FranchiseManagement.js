const { executeQuery, executeTransaction } = require('../config/database');

class FranchiseManagement {
  // =============================================
  // FRANCHISE APPLICATIONS CRUD OPERATIONS
  // =============================================
  
  static async createApplication(applicationData) {
    const {
      operator_id,
      route_id,
      application_type,
      application_date,
      status,
      documents_submitted,
      processing_fee_paid,
      remarks
    } = applicationData;

    const query = `
      INSERT INTO franchise_applications (
        operator_id, route_id, application_type, 
        application_date, status, documents_submitted, 
        processing_fee_paid, remarks
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    `;
    
    const params = [
      operator_id,
      route_id,
      application_type,
      application_date || new Date(),
      status || 'pending',
      JSON.stringify(documents_submitted || []),
      processing_fee_paid || false,
      remarks || null
    ];
    
    const result = await executeQuery(query, params);
    return { application_id: result.insertId, ...applicationData };
  }
  
  static async getAllApplications(page = 1, limit = 10, filters = {}) {
    const offset = (page - 1) * limit;
    let query = `
      SELECT fa.*, CONCAT(o.first_name, ' ', o.last_name) as operator_name, o.contact_number
      FROM franchise_applications fa
      LEFT JOIN operators o ON fa.operator_id = o.operator_id
      WHERE 1=1
    `;
    let countQuery = `
      SELECT COUNT(*) as total 
      FROM franchise_applications fa
      LEFT JOIN operators o ON fa.operator_id = o.operator_id
      WHERE 1=1
    `;
    let params = [];
    
    // Apply filters
    if (filters.status) {
      query += ' AND fa.status = ?';
      countQuery += ' AND fa.status = ?';
      params.push(filters.status);
    }
    
    if (filters.application_type) {
      query += ' AND fa.application_type = ?';
      countQuery += ' AND fa.application_type = ?';
      params.push(filters.application_type);
    }
    
    if (filters.operator_id) {
      query += ' AND fa.operator_id = ?';
      countQuery += ' AND fa.operator_id = ?';
      params.push(filters.operator_id);
    }
    
    if (filters.search) {
      query += ' AND (CONCAT(o.first_name, " ", o.last_name) LIKE ? OR fa.application_id LIKE ?)';
      countQuery += ' AND (CONCAT(o.first_name, " ", o.last_name) LIKE ? OR fa.application_id LIKE ?)';
      const searchTerm = `%${filters.search}%`;
      params.push(searchTerm, searchTerm);
    }
    
    query += ' ORDER BY fa.application_date DESC LIMIT ? OFFSET ?';
    const queryParams = [...params, limit, offset];
    
    try {
      const [applications, totalResult] = await Promise.all([
        executeQuery(query, queryParams),
        executeQuery(countQuery, params)
      ]);
      
      const total = totalResult && totalResult[0] ? totalResult[0].total : 0;
      
      return {
        success: true,
        data: applications || [],
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
        message: 'Error fetching franchise applications',
        error: error.message
      };
    }
  }
  
  static async getApplicationById(applicationId) {
    const query = `
      SELECT fa.*, CONCAT(o.first_name, ' ', o.last_name) as operator_name, o.contact_number, o.email
      FROM franchise_applications fa
      LEFT JOIN operators o ON fa.operator_id = o.operator_id
      WHERE fa.application_id = ?
    `;
    
    const result = await executeQuery(query, [applicationId]);
    if (result.length > 0) {
      return { success: true, data: result[0] };
    }
    return { success: false, message: 'Application not found' };
  }
  
  static async updateApplication(applicationId, updateData) {
    const allowedFields = [
      'route_id', 'application_type', 'status', 'documents_submitted', 
      'processing_fee_paid', 'remarks'
    ];
    
    const updateFields = [];
    const params = [];
    
    Object.keys(updateData).forEach(key => {
      if (allowedFields.includes(key) && updateData[key] !== undefined) {
        if (key === 'documents_submitted' && typeof updateData[key] === 'object') {
          updateFields.push(`${key} = ?`);
          params.push(JSON.stringify(updateData[key]));
        } else {
          updateFields.push(`${key} = ?`);
          params.push(updateData[key]);
        }
      }
    });
    
    if (updateFields.length === 0) {
      return { success: false, message: 'No valid fields to update' };
    }
    
    params.push(applicationId);
    const query = `UPDATE franchise_applications SET ${updateFields.join(', ')} WHERE application_id = ?`;
    
    const result = await executeQuery(query, params);
    if (result.affectedRows > 0) {
      return { success: true, message: 'Application updated successfully' };
    }
    return { success: false, message: 'Application not found or no changes made' };
  }
  
  static async deleteApplication(applicationId) {
    const query = 'DELETE FROM franchise_applications WHERE application_id = ?';
    return await executeQuery(query, [applicationId]);
  }
  
  // =============================================
  // DOCUMENTS CRUD OPERATIONS
  // =============================================
  
  static async createDocument(documentData) {
    const documentId = `DOC${Date.now()}`;
    const query = `
      INSERT INTO documents (
        document_id, application_id, document_type, document_name, 
        file_path, file_size, mime_type, version_number, expiry_date, 
        verification_status, metadata
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    `;
    
    const params = [
      documentId,
      documentData.application_id,
      documentData.document_type,
      documentData.document_name,
      documentData.file_path,
      documentData.file_size,
      documentData.mime_type,
      documentData.version_number || 1,
      documentData.expiry_date || null,
      documentData.verification_status || 'pending',
      JSON.stringify(documentData.metadata || {})
    ];
    
    const result = await executeQuery(query, params);
    if (result.success) {
      return { success: true, document_id: documentId, data: result.data };
    }
    return result;
  }
  
  static async getAllDocuments(filters = {}) {
    let query = `
      SELECT d.*, fa.application_type, o.first_name, o.last_name 
      FROM documents d 
      LEFT JOIN franchise_applications fa ON d.application_id = fa.application_id 
      LEFT JOIN operators o ON fa.operator_id = o.operator_id 
      WHERE 1=1
    `;
    const params = [];
    
    if (filters.application_id) {
      query += ' AND d.application_id = ?';
      params.push(filters.application_id);
    }
    
    if (filters.document_type) {
      query += ' AND d.document_type = ?';
      params.push(filters.document_type);
    }
    
    if (filters.verification_status) {
      query += ' AND d.verification_status = ?';
      params.push(filters.verification_status);
    }
    
    query += ' ORDER BY d.upload_date DESC';
    
    return await executeQuery(query, params);
  }
  
  static async getDocumentById(documentId) {
    const query = `
      SELECT d.*, fa.application_type, o.first_name, o.last_name 
      FROM documents d 
      LEFT JOIN franchise_applications fa ON d.application_id = fa.application_id 
      LEFT JOIN operators o ON fa.operator_id = o.operator_id 
      WHERE d.document_id = ?
    `;
    return await executeQuery(query, [documentId]);
  }
  
  static async updateDocument(documentId, updateData) {
    const fields = [];
    const params = [];
    
    Object.keys(updateData).forEach(key => {
      if (updateData[key] !== undefined) {
        if (key === 'metadata') {
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
    
    params.push(documentId);
    const query = `UPDATE documents SET ${fields.join(', ')} WHERE document_id = ?`;
    
    return await executeQuery(query, params);
  }
  
  static async deleteDocument(documentId) {
    const query = 'DELETE FROM documents WHERE document_id = ?';
    return await executeQuery(query, [documentId]);
  }
  
  // =============================================
  // FRANCHISES CRUD OPERATIONS
  // =============================================
  
  static async createFranchise(franchiseData) {
    const franchiseId = `FR${Date.now()}`;
    const query = `
      INSERT INTO franchises (
        franchise_id, application_id, operator_id, vehicle_id, 
        franchise_number, issue_date, expiry_date, franchise_status, 
        conditions
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    `;
    
    const params = [
      franchiseId,
      franchiseData.application_id,
      franchiseData.operator_id,
      franchiseData.vehicle_id,
      franchiseData.franchise_number,
      franchiseData.issue_date,
      franchiseData.expiry_date,
      franchiseData.franchise_status || 'active',
      franchiseData.conditions || null
    ];
    
    const result = await executeQuery(query, params);
    if (result.success) {
      return { success: true, franchise_id: franchiseId, data: result.data };
    }
    return result;
  }
  
  static async getAllFranchises(filters = {}) {
    let query = `
      SELECT f.*, o.first_name, o.last_name, v.plate_number, v.vehicle_type 
      FROM franchises f 
      LEFT JOIN operators o ON f.operator_id = o.operator_id 
      LEFT JOIN vehicles v ON f.vehicle_id = v.vehicle_id 
      WHERE 1=1
    `;
    const params = [];
    
    if (filters.franchise_status) {
      query += ' AND f.franchise_status = ?';
      params.push(filters.franchise_status);
    }
    
    if (filters.operator_id) {
      query += ' AND f.operator_id = ?';
      params.push(filters.operator_id);
    }
    

    
    if (filters.expiring_soon) {
      query += ' AND f.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)';
    }
    
    query += ' ORDER BY f.created_at DESC';
    
    return await executeQuery(query, params);
  }
  
  static async getFranchiseById(franchiseId) {
    const query = `
      SELECT f.*, o.first_name, o.last_name, o.contact_number, 
             v.plate_number, v.vehicle_type 
      FROM franchises f 
      LEFT JOIN operators o ON f.operator_id = o.operator_id 
      LEFT JOIN vehicles v ON f.vehicle_id = v.vehicle_id 
      WHERE f.franchise_id = ?
    `;
    return await executeQuery(query, [franchiseId]);
  }
  
  static async updateFranchise(franchiseId, updateData) {
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
    
    params.push(franchiseId);
    const query = `UPDATE franchises SET ${fields.join(', ')} WHERE franchise_id = ?`;
    
    return await executeQuery(query, params);
  }
  
  static async deleteFranchise(franchiseId) {
    const query = 'DELETE FROM franchises WHERE franchise_id = ?';
    return await executeQuery(query, [franchiseId]);
  }
  
  // =============================================
  // ROUTES CRUD OPERATIONS
  // =============================================
  
  static async createRoute(routeData) {
    const routeId = `RT${Date.now()}`;
    const query = `
      INSERT INTO routes (
        route_id, route_name, route_code, origin, destination, 
        distance_km, estimated_travel_time, fare_amount, route_description, 
        waypoints, status
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    `;
    
    const params = [
      routeId,
      routeData.route_name,
      routeData.route_code,
      routeData.origin,
      routeData.destination,
      routeData.distance_km,
      routeData.estimated_travel_time,
      routeData.fare_amount,
      routeData.route_description || null,
      JSON.stringify(routeData.waypoints || []),
      routeData.status || 'active'
    ];
    
    const result = await executeQuery(query, params);
    if (result.success) {
      return { success: true, route_id: routeId, data: result.data };
    }
    return result;
  }
  
  static async getAllRoutes(filters = {}) {
    let query = 'SELECT * FROM routes WHERE 1=1';
    const params = [];
    
    if (filters.status) {
      query += ' AND status = ?';
      params.push(filters.status);
    }
    
    if (filters.search) {
      query += ' AND (route_name LIKE ? OR route_code LIKE ? OR origin LIKE ? OR destination LIKE ?)';
      const searchTerm = `%${filters.search}%`;
      params.push(searchTerm, searchTerm, searchTerm, searchTerm);
    }
    
    query += ' ORDER BY created_at DESC';
    
    return await executeQuery(query, params);
  }
  
  static async getRouteById(routeId) {
    const query = 'SELECT * FROM routes WHERE route_id = ?';
    return await executeQuery(query, [routeId]);
  }
  
  static async updateRoute(routeId, updateData) {
    const fields = [];
    const params = [];
    
    Object.keys(updateData).forEach(key => {
      if (updateData[key] !== undefined) {
        if (key === 'waypoints') {
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
    
    params.push(routeId);
    const query = `UPDATE routes SET ${fields.join(', ')} WHERE route_id = ?`;
    
    return await executeQuery(query, params);
  }
  
  static async deleteRoute(routeId) {
    const query = 'DELETE FROM routes WHERE route_id = ?';
    return await executeQuery(query, [routeId]);
  }
  
  // =============================================
  // SCHEDULES CRUD OPERATIONS
  // =============================================
  
  static async createSchedule(scheduleData) {
    const scheduleId = `SCH${Date.now()}`;
    const query = `
      INSERT INTO schedules (
        schedule_id, route_id, franchise_id, departure_time, arrival_time, 
        frequency_minutes, operating_days, effective_date, end_date, status
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    `;
    
    const params = [
      scheduleId,
      scheduleData.route_id,
      scheduleData.franchise_id,
      scheduleData.departure_time,
      scheduleData.arrival_time,
      scheduleData.frequency_minutes,
      scheduleData.operating_days,
      scheduleData.effective_date,
      scheduleData.end_date || null,
      scheduleData.status || 'active'
    ];
    
    const result = await executeQuery(query, params);
    if (result.success) {
      return { success: true, schedule_id: scheduleId, data: result.data };
    }
    return result;
  }
  
  static async getAllSchedules(filters = {}) {
    let query = `
      SELECT s.*, r.route_name, r.route_code, f.franchise_number, 
             o.first_name, o.last_name, v.plate_number 
      FROM schedules s 
      LEFT JOIN routes r ON s.route_id = r.route_id 
      LEFT JOIN franchises f ON s.franchise_id = f.franchise_id 
      LEFT JOIN operators o ON f.operator_id = o.operator_id 
      LEFT JOIN vehicles v ON f.vehicle_id = v.vehicle_id 
      WHERE 1=1
    `;
    const params = [];
    
    if (filters.route_id) {
      query += ' AND s.route_id = ?';
      params.push(filters.route_id);
    }
    
    if (filters.franchise_id) {
      query += ' AND s.franchise_id = ?';
      params.push(filters.franchise_id);
    }
    
    if (filters.status) {
      query += ' AND s.status = ?';
      params.push(filters.status);
    }
    
    query += ' ORDER BY s.departure_time ASC';
    
    return await executeQuery(query, params);
  }
  
  static async getScheduleById(scheduleId) {
    const query = `
      SELECT s.*, r.route_name, r.route_code, f.franchise_number, 
             o.first_name, o.last_name, v.plate_number 
      FROM schedules s 
      LEFT JOIN routes r ON s.route_id = r.route_id 
      LEFT JOIN franchises f ON s.franchise_id = f.franchise_id 
      LEFT JOIN operators o ON f.operator_id = o.operator_id 
      LEFT JOIN vehicles v ON f.vehicle_id = v.vehicle_id 
      WHERE s.schedule_id = ?
    `;
    return await executeQuery(query, [scheduleId]);
  }
  
  static async updateSchedule(scheduleId, updateData) {
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
    
    params.push(scheduleId);
    const query = `UPDATE schedules SET ${fields.join(', ')} WHERE schedule_id = ?`;
    
    return await executeQuery(query, params);
  }
  
  static async deleteSchedule(scheduleId) {
    const query = 'DELETE FROM schedules WHERE schedule_id = ?';
    return await executeQuery(query, [scheduleId]);
  }
  
  // =============================================
  // APPLICATION DOCUMENTS CRUD OPERATIONS
  // =============================================
  
  // Upload document for application
  static async uploadDocument(documentData) {
    const {
      application_id,
      document_type,
      file_name,
      file_path,
      file_size,
      uploaded_by
    } = documentData;

    const query = `
      INSERT INTO application_documents (
        application_id, document_type, 
        file_name, file_path, file_size, uploaded_by, upload_date
      ) VALUES (?, ?, ?, ?, ?, ?, ?)
    `;
    
    const params = [
      application_id,
      document_type,
      file_name,
      file_path,
      file_size,
      uploaded_by,
      new Date()
    ];
    
    const result = await executeQuery(query, params);
    return { document_id: result.insertId, ...documentData };
  }

  static async getApplicationDocuments(applicationId) {
    const query = `
      SELECT * FROM application_documents 
      WHERE application_id = ? 
      ORDER BY upload_date DESC
    `;
    return await executeQuery(query, [applicationId]);
  }

  static async deleteApplicationDocument(documentId) {
    const query = 'DELETE FROM application_documents WHERE document_id = ?';
    return await executeQuery(query, [documentId]);
  }

  // =============================================
  // FRANCHISE RECORDS CRUD OPERATIONS
  // =============================================

  static async createFranchiseRecord(recordData) {
    const {
      application_id,
      franchise_number,
      issue_date,
      expiry_date,
      status,
      conditions,
      issued_by
    } = recordData;

    const query = `
      INSERT INTO franchise_records (
        application_id, franchise_number, issue_date, 
        expiry_date, status, conditions, issued_by
      ) VALUES (?, ?, ?, ?, ?, ?, ?)
    `;
    
    const params = [
      application_id,
      franchise_number,
      issue_date,
      expiry_date,
      status || 'active',
      conditions,
      issued_by
    ];
    
    const result = await executeQuery(query, params);
    return { franchise_record_id: result.insertId, ...recordData };
  }

  static async getAllFranchiseRecords(filters = {}) {
    let query = `
      SELECT fr.*, fa.operator_id, o.operator_name
      FROM franchise_records fr
      LEFT JOIN franchise_applications fa ON fr.application_id = fa.application_id
      LEFT JOIN operators o ON fa.operator_id = o.operator_id
      WHERE 1=1
    `;
    const params = [];
    
    if (filters.status) {
      query += ' AND fr.status = ?';
      params.push(filters.status);
    }
    
    if (filters.expiring_soon) {
      query += ' AND fr.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)';
    }
    
    query += ' ORDER BY fr.issue_date DESC';
    
    return await executeQuery(query, params);
  }

  static async getFranchiseRecordById(recordId) {
    const query = `
      SELECT fr.*, fa.operator_id, o.operator_name, o.contact_person
      FROM franchise_records fr
      LEFT JOIN franchise_applications fa ON fr.application_id = fa.application_id
      LEFT JOIN operators o ON fa.operator_id = o.operator_id
      WHERE fr.franchise_record_id = ?
    `;
    return await executeQuery(query, [recordId]);
  }

  static async updateFranchiseRecord(recordId, updateData) {
    const allowedFields = ['status', 'expiry_date', 'conditions'];
    
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
    
    params.push(recordId);
    const query = `UPDATE franchise_records SET ${updateFields.join(', ')} WHERE franchise_record_id = ?`;
    
    const result = await executeQuery(query, params);
    if (result.affectedRows > 0) {
      return { success: true, message: 'Franchise record updated successfully' };
    }
    return { success: false, message: 'Franchise record not found or no changes made' };
  }

  // =============================================
  // OFFICIAL ROUTES CRUD OPERATIONS
  // =============================================

  static async createOfficialRoute(routeData) {
    const {
      route_name,
      route_code,
      origin,
      destination,
      distance_km,
      estimated_travel_time,
      fare_matrix,
      status
    } = routeData;

    const query = `
      INSERT INTO official_routes (
        route_name, route_code, origin, destination, 
        distance_km, estimated_travel_time, fare_matrix, status
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    `;
    
    const params = [
      route_name,
      route_code,
      origin,
      destination,
      distance_km,
      estimated_travel_time,
      JSON.stringify(fare_matrix || {}),
      status || 'active'
    ];
    
    const result = await executeQuery(query, params);
    return { route_id: result.insertId, ...routeData };
  }

  static async getAllOfficialRoutes(filters = {}) {
    let query = 'SELECT * FROM official_routes WHERE 1=1';
    const params = [];
    
    if (filters.status) {
      query += ' AND status = ?';
      params.push(filters.status);
    }
    
    if (filters.search) {
      query += ' AND (route_name LIKE ? OR route_code LIKE ? OR origin LIKE ? OR destination LIKE ?)';
      const searchTerm = `%${filters.search}%`;
      params.push(searchTerm, searchTerm, searchTerm, searchTerm);
    }
    
    query += ' ORDER BY route_name ASC';
    
    return await executeQuery(query, params);
  }

  static async getOfficialRouteById(routeId) {
    const query = 'SELECT * FROM official_routes WHERE route_id = ?';
    return await executeQuery(query, [routeId]);
  }

  static async updateOfficialRoute(routeId, updateData) {
    const fields = [];
    const params = [];
    
    Object.keys(updateData).forEach(key => {
      if (updateData[key] !== undefined) {
        if (key === 'fare_matrix') {
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
    
    params.push(routeId);
    const query = `UPDATE official_routes SET ${fields.join(', ')} WHERE route_id = ?`;
    
    return await executeQuery(query, params);
  }

  static async deleteOfficialRoute(routeId) {
    const query = 'DELETE FROM official_routes WHERE route_id = ?';
    return await executeQuery(query, [routeId]);
  }

  // =============================================
  // ROUTE SCHEDULES CRUD OPERATIONS
  // =============================================

  static async createRouteSchedule(scheduleData) {
    const {
      route_id,
      operator_id,
      departure_time,
      arrival_time,
      frequency_minutes,
      operating_days,
      effective_date,
      end_date,
      status
    } = scheduleData;

    const query = `
      INSERT INTO route_schedules (
        route_id, operator_id, departure_time, arrival_time, 
        frequency_minutes, operating_days, effective_date, end_date, status
      ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    `;
    
    const params = [
      route_id,
      operator_id,
      departure_time,
      arrival_time,
      frequency_minutes,
      operating_days,
      effective_date,
      end_date,
      status || 'active'
    ];
    
    const result = await executeQuery(query, params);
    return { schedule_id: result.insertId, ...scheduleData };
  }

  static async getAllRouteSchedules(filters = {}) {
    let query = `
      SELECT rs.*, or.route_name, or.route_code, o.operator_name
      FROM route_schedules rs
      LEFT JOIN official_routes or ON rs.route_id = or.route_id
      LEFT JOIN operators o ON rs.operator_id = o.operator_id
      WHERE 1=1
    `;
    const params = [];
    
    if (filters.route_id) {
      query += ' AND rs.route_id = ?';
      params.push(filters.route_id);
    }
    
    if (filters.operator_id) {
      query += ' AND rs.operator_id = ?';
      params.push(filters.operator_id);
    }
    
    if (filters.status) {
      query += ' AND rs.status = ?';
      params.push(filters.status);
    }
    
    query += ' ORDER BY rs.departure_time ASC';
    
    return await executeQuery(query, params);
  }

  static async getRouteScheduleById(scheduleId) {
    const query = `
      SELECT rs.*, or.route_name, or.route_code, o.operator_name
      FROM route_schedules rs
      LEFT JOIN official_routes or ON rs.route_id = or.route_id
      LEFT JOIN operators o ON rs.operator_id = o.operator_id
      WHERE rs.schedule_id = ?
    `;
    return await executeQuery(query, [scheduleId]);
  }

  static async updateRouteSchedule(scheduleId, updateData) {
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
    
    params.push(scheduleId);
    const query = `UPDATE route_schedules SET ${fields.join(', ')} WHERE schedule_id = ?`;
    
    return await executeQuery(query, params);
  }

  static async deleteRouteSchedule(scheduleId) {
    const query = 'DELETE FROM route_schedules WHERE schedule_id = ?';
    return await executeQuery(query, [scheduleId]);
  }

  // =============================================
  // ANALYTICS AND REPORTS
  // =============================================
  
  static async getApplicationWorkflowReport() {
    const query = `
      SELECT 
        workflow_status,
        COUNT(*) as count,
        AVG(DATEDIFF(CURDATE(), application_date)) as avg_processing_days
      FROM franchise_applications 
      GROUP BY workflow_status
    `;
    return await executeQuery(query);
  }
  
  static async getFranchiseExpiryReport() {
    const query = `
      SELECT 
        COUNT(*) as total_franchises,
        SUM(CASE WHEN expiry_date <= CURDATE() THEN 1 ELSE 0 END) as expired,
        SUM(CASE WHEN expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as expiring_soon,
        SUM(CASE WHEN franchise_status = 'active' THEN 1 ELSE 0 END) as active_franchises
      FROM franchises
    `;
    return await executeQuery(query);
  }
  
  static async getRouteUtilizationReport() {
    const query = `
      SELECT 
        r.route_id,
        r.route_name,
        r.route_code,
        COUNT(f.franchise_id) as assigned_franchises,
        COUNT(s.schedule_id) as active_schedules
      FROM routes r 
      LEFT JOIN franchises f ON r.route_id = f.route_id AND f.franchise_status = 'active'
      LEFT JOIN schedules s ON r.route_id = s.route_id AND s.status = 'active'
      GROUP BY r.route_id, r.route_name, r.route_code
      ORDER BY assigned_franchises DESC
    `;
    return await executeQuery(query);
  }
}

module.exports = FranchiseManagement;