# Setup Instructions

## Required Files Not in Repository

Due to security and size constraints, some files are not included in the repository. Please create/add these files after cloning:

### 1. Database Schema
- Import `database/transport_and_mobility_management.sql` to your MySQL server
- Create database named: `transport_mobility_db`

### 2. Upload Directories
The following directories need to be created with write permissions:
```
uploads/
uploads/documents/
uploads/tickets/
upload/
```

### 3. Logo File
Add your city logo as: `upload/Caloocan_City.png`

### 4. Environment Configuration
Copy `config/database.php` and update database credentials if needed.

## Quick Setup Commands
```bash
# Create required directories
mkdir -p uploads/documents uploads/tickets upload

# Set permissions (Linux/Mac)
chmod 755 uploads/ upload/
chmod 755 uploads/documents uploads/tickets

# Import database
mysql -u root -p transport_mobility_db < database/transport_and_mobility_management.sql
```

## Access URLs
- Administrator: `http://localhost/transport-mobility-management-system/administrator/`
- TPRS Portal: `http://localhost/transport-mobility-management-system/tprs/`