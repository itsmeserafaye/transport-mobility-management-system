class Vehicle {
    constructor(data = {}) {
        this.id = data.id || null;
        this.plate_number = data.plate_number || '';
        this.vehicle_type = data.vehicle_type || '';
        this.make = data.make || '';
        this.model = data.model || '';
        this.year = data.year || null;
        this.color = data.color || '';
        this.engine_number = data.engine_number || '';
        this.chassis_number = data.chassis_number || '';
        this.seating_capacity = data.seating_capacity || null;
        this.operator_id = data.operator_id || null;
        this.route_id = data.route_id || null;
        this.registration_date = data.registration_date || null;
        this.expiry_date = data.expiry_date || null;
        this.status = data.status || 'active';
        this.insurance_policy = data.insurance_policy || null;
        this.insurance_expiry = data.insurance_expiry || null;
        this.last_inspection_date = data.last_inspection_date || null;
        this.next_inspection_date = data.next_inspection_date || null;
        this.created_at = data.created_at || null;
        this.updated_at = data.updated_at || null;
    }

    // Validation methods
    static validateCreate(data) {
        const errors = [];

        if (!data.plate_number || !/^[A-Z0-9\-\s]{6,10}$/.test(data.plate_number.toUpperCase())) {
            errors.push('Valid plate number is required (6-10 characters, letters and numbers only)');
        }

        if (!data.vehicle_type || !['jeepney', 'bus', 'taxi', 'tricycle', 'van'].includes(data.vehicle_type.toLowerCase())) {
            errors.push('Valid vehicle type is required (jeepney, bus, taxi, tricycle, van)');
        }

        if (!data.make || data.make.trim().length < 2) {
            errors.push('Vehicle make is required (minimum 2 characters)');
        }

        if (!data.model || data.model.trim().length < 2) {
            errors.push('Vehicle model is required (minimum 2 characters)');
        }

        const currentYear = new Date().getFullYear();
        if (!data.year || data.year < 1990 || data.year > currentYear + 1) {
            errors.push(`Vehicle year must be between 1990 and ${currentYear + 1}`);
        }

        if (!data.engine_number || data.engine_number.trim().length < 5) {
            errors.push('Engine number is required (minimum 5 characters)');
        }

        if (!data.chassis_number || data.chassis_number.trim().length < 10) {
            errors.push('Chassis number is required (minimum 10 characters)');
        }

        if (!data.seating_capacity || data.seating_capacity < 1 || data.seating_capacity > 100) {
            errors.push('Seating capacity must be between 1 and 100');
        }

        if (!data.operator_id || !Number.isInteger(Number(data.operator_id))) {
            errors.push('Valid operator is required');
        }

        if (data.registration_date && !this.isValidDate(data.registration_date)) {
            errors.push('Valid registration date is required');
        }

        if (data.expiry_date && !this.isValidDate(data.expiry_date)) {
            errors.push('Valid expiry date is required');
        }

        if (data.insurance_expiry && !this.isValidDate(data.insurance_expiry)) {
            errors.push('Valid insurance expiry date is required');
        }

        return {
            isValid: errors.length === 0,
            errors
        };
    }

    static validateUpdate(data) {
        const errors = [];

        if (data.plate_number && !/^[A-Z0-9\-\s]{6,10}$/.test(data.plate_number.toUpperCase())) {
            errors.push('Valid plate number is required (6-10 characters, letters and numbers only)');
        }

        if (data.vehicle_type && !['jeepney', 'bus', 'taxi', 'tricycle', 'van'].includes(data.vehicle_type.toLowerCase())) {
            errors.push('Valid vehicle type is required (jeepney, bus, taxi, tricycle, van)');
        }

        if (data.year) {
            const currentYear = new Date().getFullYear();
            if (data.year < 1990 || data.year > currentYear + 1) {
                errors.push(`Vehicle year must be between 1990 and ${currentYear + 1}`);
            }
        }

        if (data.seating_capacity && (data.seating_capacity < 1 || data.seating_capacity > 100)) {
            errors.push('Seating capacity must be between 1 and 100');
        }

        if (data.status && !['active', 'inactive', 'suspended', 'expired'].includes(data.status)) {
            errors.push('Valid status is required (active, inactive, suspended, expired)');
        }

        return {
            isValid: errors.length === 0,
            errors
        };
    }

    static isValidDate(dateString) {
        const date = new Date(dateString);
        return date instanceof Date && !isNaN(date);
    }

    // Data transformation methods
    static sanitizeInput(data) {
        return {
            plate_number: data.plate_number?.toUpperCase().trim(),
            vehicle_type: data.vehicle_type?.toLowerCase().trim(),
            make: data.make?.trim(),
            model: data.model?.trim(),
            year: parseInt(data.year),
            color: data.color?.trim(),
            engine_number: data.engine_number?.toUpperCase().trim(),
            chassis_number: data.chassis_number?.toUpperCase().trim(),
            seating_capacity: parseInt(data.seating_capacity),
            operator_id: parseInt(data.operator_id),
            route_id: data.route_id ? parseInt(data.route_id) : null,
            registration_date: data.registration_date,
            expiry_date: data.expiry_date,
            status: data.status?.toLowerCase(),
            insurance_policy: data.insurance_policy?.trim(),
            insurance_expiry: data.insurance_expiry
        };
    }

    static formatForResponse(data) {
        return {
            ...data,
            registration_date: data.registration_date ? new Date(data.registration_date).toISOString().split('T')[0] : null,
            expiry_date: data.expiry_date ? new Date(data.expiry_date).toISOString().split('T')[0] : null,
            insurance_expiry: data.insurance_expiry ? new Date(data.insurance_expiry).toISOString().split('T')[0] : null,
            last_inspection_date: data.last_inspection_date ? new Date(data.last_inspection_date).toISOString().split('T')[0] : null,
            next_inspection_date: data.next_inspection_date ? new Date(data.next_inspection_date).toISOString().split('T')[0] : null,
            created_at: data.created_at ? new Date(data.created_at).toISOString() : null,
            updated_at: data.updated_at ? new Date(data.updated_at).toISOString() : null
        };
    }

    // Business logic methods
    static getStatusColor(status) {
        const colors = {
            active: '#10b981',
            inactive: '#6b7280',
            suspended: '#f59e0b',
            expired: '#ef4444'
        };
        return colors[status] || '#6b7280';
    }

    static getStatusLabel(status) {
        const labels = {
            active: 'Active',
            inactive: 'Inactive',
            suspended: 'Suspended',
            expired: 'Expired'
        };
        return labels[status] || 'Unknown';
    }

    static getVehicleTypeLabel(type) {
        const labels = {
            jeepney: 'Jeepney',
            bus: 'Bus',
            taxi: 'Taxi',
            tricycle: 'Tricycle',
            van: 'Van'
        };
        return labels[type] || 'Unknown';
    }

    static isExpiringSoon(expiryDate, daysThreshold = 30) {
        if (!expiryDate) return false;
        
        const expiry = new Date(expiryDate);
        const today = new Date();
        const diffTime = expiry - today;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        return diffDays <= daysThreshold && diffDays >= 0;
    }

    static isExpired(expiryDate) {
        if (!expiryDate) return false;
        
        const expiry = new Date(expiryDate);
        const today = new Date();
        
        return expiry < today;
    }

    static getComplianceStatus(vehicle) {
        const issues = [];
        
        if (this.isExpired(vehicle.expiry_date)) {
            issues.push('Registration expired');
        } else if (this.isExpiringSoon(vehicle.expiry_date)) {
            issues.push('Registration expiring soon');
        }
        
        if (this.isExpired(vehicle.insurance_expiry)) {
            issues.push('Insurance expired');
        } else if (this.isExpiringSoon(vehicle.insurance_expiry)) {
            issues.push('Insurance expiring soon');
        }
        
        if (this.isExpired(vehicle.next_inspection_date)) {
            issues.push('Inspection overdue');
        } else if (this.isExpiringSoon(vehicle.next_inspection_date)) {
            issues.push('Inspection due soon');
        }
        
        return {
            status: issues.length === 0 ? 'compliant' : 'non_compliant',
            issues
        };
    }
}

module.exports = Vehicle;