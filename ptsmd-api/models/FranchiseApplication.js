class FranchiseApplication {
    constructor(data = {}) {
        this.id = data.id || null;
        this.operator_id = data.operator_id || '';
        this.business_name = data.business_name || '';
        this.contact_number = data.contact_number || '';
        this.email = data.email || '';
        this.address = data.address || '';
        this.route_id = data.route_id || null;
        this.vehicle_capacity = data.vehicle_capacity || null;
        this.application_type = data.application_type || 'new';
        this.status = data.status || 'pending';
        this.documents = data.documents || null;
        this.remarks = data.remarks || null;
        this.submitted_date = data.submitted_date || null;
        this.processed_date = data.processed_date || null;
        this.processed_by = data.processed_by || null;
        this.created_at = data.created_at || null;
        this.updated_at = data.updated_at || null;
    }

    // Validation methods
    static validateCreate(data) {
        const errors = [];

        if (!data.operator_id || data.operator_id.trim().length < 2) {
            errors.push('Operator ID is required');
        }

        if (!data.business_name || data.business_name.trim().length < 2) {
            errors.push('Business name must be at least 2 characters long');
        }

        if (!data.contact_number || !/^[0-9+\-\s()]{10,15}$/.test(data.contact_number)) {
            errors.push('Valid contact number is required');
        }

        if (!data.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
            errors.push('Valid email address is required');
        }

        if (!data.address || data.address.trim().length < 10) {
            errors.push('Complete address is required (minimum 10 characters)');
        }

        if (!data.route_id || !Number.isInteger(Number(data.route_id))) {
            errors.push('Valid route selection is required');
        }

        if (!data.vehicle_capacity || data.vehicle_capacity < 1 || data.vehicle_capacity > 100) {
            errors.push('Vehicle capacity must be between 1 and 100');
        }

        if (!['new', 'renewal', 'transfer'].includes(data.application_type)) {
            errors.push('Valid application type is required');
        }

        return {
            isValid: errors.length === 0,
            errors
        };
    }

    static validateStatusUpdate(data) {
        const errors = [];
        const validStatuses = ['pending', 'under_review', 'approved', 'rejected', 'cancelled'];

        if (!data.status || !validStatuses.includes(data.status)) {
            errors.push('Valid status is required');
        }

        if (data.status === 'rejected' && (!data.remarks || data.remarks.trim().length < 5)) {
            errors.push('Rejection reason is required (minimum 5 characters)');
        }

        return {
            isValid: errors.length === 0,
            errors
        };
    }

    // Data transformation methods
    static sanitizeInput(data) {
        return {
            operator_id: data.operator_id?.trim(),
            business_name: data.business_name?.trim(),
            contact_number: data.contact_number?.replace(/[^0-9+\-\s()]/g, ''),
            email: data.email?.toLowerCase().trim(),
            address: data.address?.trim(),
            route_id: parseInt(data.route_id),
            vehicle_capacity: parseInt(data.vehicle_capacity),
            application_type: data.application_type?.toLowerCase(),
            documents: data.documents,
            remarks: data.remarks?.trim()
        };
    }

    static formatForResponse(data) {
        return {
            ...data,
            submitted_date: data.submitted_date ? new Date(data.submitted_date).toISOString().split('T')[0] : null,
            processed_date: data.processed_date ? new Date(data.processed_date).toISOString().split('T')[0] : null,
            created_at: data.created_at ? new Date(data.created_at).toISOString() : null,
            updated_at: data.updated_at ? new Date(data.updated_at).toISOString() : null
        };
    }

    // Business logic methods
    static getStatusColor(status) {
        const colors = {
            pending: '#fbbf24',
            under_review: '#3b82f6',
            approved: '#10b981',
            rejected: '#ef4444',
            cancelled: '#6b7280'
        };
        return colors[status] || '#6b7280';
    }

    static getStatusLabel(status) {
        const labels = {
            pending: 'Pending Review',
            under_review: 'Under Review',
            approved: 'Approved',
            rejected: 'Rejected',
            cancelled: 'Cancelled'
        };
        return labels[status] || 'Unknown';
    }

    static canUpdateStatus(currentStatus, newStatus) {
        const allowedTransitions = {
            pending: ['under_review', 'cancelled'],
            under_review: ['approved', 'rejected', 'pending'],
            approved: ['cancelled'],
            rejected: ['under_review'],
            cancelled: []
        };
        
        return allowedTransitions[currentStatus]?.includes(newStatus) || false;
    }
}

module.exports = FranchiseApplication;