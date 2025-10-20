# Transport and Mobility Management System - Startup Procedure

## Prerequisites
- XAMPP installed and configured
- Node.js installed
- All project dependencies installed

## Step-by-Step Startup Procedure

### 1. Start XAMPP Services
1. Open XAMPP Control Panel
2. Start **Apache** service
3. Start **MySQL** service
4. Verify both services are running (green status)

### 2. Start API Servers

#### PTSMD API Server
1. Open terminal/command prompt
2. Navigate to: `C:\xampp\htdocs\transport_and_mobility_management_system\ptsmd-api`
3. Run: `npm start`
4. Verify server starts on port 3000

#### TPRS API Server
1. Open another terminal/command prompt
2. Navigate to: `C:\xampp\htdocs\transport_and_mobility_management_system\tprs-api`
3. Run: `npm start`
4. Verify server starts on port 3001

### 3. Access the System

#### Administrator Portal
- URL: `http://localhost/transport_and_mobility_management_system/administrator/`
- All modules should be accessible through the navigation menu

#### Tested Modules (All Working)
- **PUV Database**
  - Vehicle and Operator Records
  - Compliance Status Management
  - Violation History Integration

- **Franchise Management**
  - Document Repository
  - Franchise Lifecycle Management
  - Route and Schedule Publication

- **Traffic Violation Ticketing**
  - Violation Record Management
  - Linking and Analytics
  - Revenue Integration

- **Vehicle Inspection and Registration**
  - Inspection Scheduling
  - Inspection Result Recording
  - Inspection History Tracking

- **Parking and Terminal Management**
  - Public Transparency
  - Roster and Delivery
  - Terminal Assignment Management

### 4. Verification Steps

1. **Check API Servers**:
   ```bash
   curl http://localhost:3000/health
   curl http://localhost:3001/health
   ```

2. **Check Administrator Portal**:
   ```bash
   curl http://localhost/transport_and_mobility_management_system/administrator/
   ```

3. **Test Module Access** (example):
   ```bash
   curl http://localhost/transport_and_mobility_management_system/administrator/puv_database/vehicle_and_operator_records/
   ```

### 5. Common Issues and Solutions

#### Issue: "getStatistics() function not found"
**Solution**: The system has been fixed with proper includes for `compliance_status_management/functions.php` in all required modules.

#### Issue: API servers not starting
**Solution**: 
1. Check if ports 3000 and 3001 are available
2. Verify Node.js dependencies are installed (`npm install`)
3. Check for any error messages in terminal

#### Issue: Apache/MySQL not starting
**Solution**:
1. Check if ports 80 and 3306 are available
2. Run XAMPP as administrator
3. Check XAMPP error logs

### 6. Shutdown Procedure

1. Stop API servers (Ctrl+C in terminals)
2. Stop Apache and MySQL services in XAMPP
3. Close XAMPP Control Panel

## Notes

- Always start XAMPP services before starting API servers
- Keep terminals open while using the system
- All administrator modules have been tested and are functional
- The system uses a modular architecture with proper navigation between components

## Last Updated
System tested and verified: All modules working correctly
API servers: Both PTSMD and TPRS APIs operational
Database connectivity: Confirmed working through all modules