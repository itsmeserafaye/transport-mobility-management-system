# Transport & Mobility Management System - API Endpoints and Permissions

## Overview
This document defines the API endpoints and inter-module permissions for the Transport & Mobility Management System based on the external system connections and data flow requirements.

---

## Module 1: PUV Database API

### Vehicle & Operator Records
```
# Operators
GET /api/v1/puv/operators
→ Get all operators with filtering
→ Permissions: All modules

GET /api/v1/puv/operators/{id}
→ Get specific operator details
→ Permissions: All modules

POST /api/v1/puv/operators
→ Register new operator
→ Permissions: Franchise Management

PUT /api/v1/puv/operators/{id}
→ Update operator information
→ Permissions: Franchise Management

DELETE /api/v1/puv/operators/{id}
→ Delete operator record
→ Permissions: Administrator only

# Vehicles
GET /api/v1/puv/vehicles
→ Get all vehicles with filtering
→ Permissions: All modules, Citizen Registry Portal

GET /api/v1/puv/vehicles/{id}
→ Get specific vehicle details
→ Permissions: All modules, Citizen Registry Portal

POST /api/v1/puv/vehicles
→ Register new public utility vehicle
→ Permissions: Franchise Management, Vehicle Inspection

PUT /api/v1/puv/vehicles/{id}
→ Update vehicle information
→ Permissions: Franchise Management, Vehicle Inspection

DELETE /api/v1/puv/vehicles/{id}
→ Delete vehicle record
→ Permissions: Administrator only
```

### Compliance Status Management
```
GET /api/v1/puv/compliance
→ Get compliance records with filtering
→ Permissions: Franchise Management, Vehicle Inspection, Traffic Violation

POST /api/v1/puv/compliance
→ Create new compliance record
→ Permissions: Vehicle Inspection, Traffic Violation

GET /api/v1/puv/reports/compliance
→ Generate compliance reports
→ Permissions: Treasurer/Finance, Dashboards
```

### Violation History Integration
```
GET /api/v1/puv/violations
→ Get violation history with filtering
→ Permissions: Franchise Management, Legal Case Management

POST /api/v1/puv/violations
→ Record violation history
→ Permissions: Traffic Violation Ticketing

GET /api/v1/puv/reports/violations
→ Generate violation reports
→ Permissions: All modules
```

---

## Module 2: Franchise Management API

### Franchise Application Processing
```
# Applications
GET /api/v1/franchise/applications
→ Get all franchise applications with filtering
→ Permissions: Franchise Management, Legal Case Management

GET /api/v1/franchise/applications/{id}
→ Get specific application details
→ Permissions: Franchise Management, Legal Case Management

POST /api/v1/franchise/applications
→ Submit new franchise application
→ Permissions: Citizen Registry Portal

PUT /api/v1/franchise/applications/{id}
→ Update application information
→ Permissions: Franchise Management

DELETE /api/v1/franchise/applications/{id}
→ Delete application record
→ Permissions: Administrator only

# Franchises
GET /api/v1/franchise/franchises
→ Get all franchises with filtering
→ Permissions: Franchise Management, All modules

GET /api/v1/franchise/franchises/{id}
→ Get specific franchise details
→ Permissions: Franchise Management, All modules

POST /api/v1/franchise/franchises
→ Create new franchise record
→ Permissions: Franchise Management

PUT /api/v1/franchise/franchises/{id}
→ Update franchise information
→ Permissions: Franchise Management

DELETE /api/v1/franchise/franchises/{id}
→ Delete franchise record
→ Permissions: Administrator only
```

### Franchise Renewal Management
```
GET /api/v1/franchise/renewals
→ Get all renewal applications with filtering
→ Permissions: Franchise Management, Citizen Registry Portal

POST /api/v1/franchise/renewals
→ Submit renewal application
→ Permissions: Citizen Registry Portal

GET /api/v1/franchise/reports/renewals
→ Generate renewal reports
→ Permissions: Franchise Management
```

### Legal Case Integration
```
GET /api/v1/franchise/legal-cases
→ Get franchise legal cases with filtering
→ Permissions: Legal Case Management, Franchise Management

POST /api/v1/franchise/legal-cases
→ Create legal case
→ Permissions: Legal Case Management

GET /api/v1/franchise/reports/legal-cases
→ Generate legal case reports
→ Permissions: Legal Case Management
```

---

## Module 3: Traffic Violation Ticketing API

### Violation Recording & Processing
```
# Violations
GET /api/v1/traffic/violations
→ Get all traffic violations with filtering
→ Permissions: Legal Case Management, Treasurer Collection

GET /api/v1/traffic/violations/{id}
→ Get specific violation details
→ Permissions: Legal Case Management, Treasurer Collection

POST /api/v1/traffic/violations
→ Record traffic violation
→ Permissions: Traffic Enforcement Officers, Mobile Ticketing

PUT /api/v1/traffic/violations/{id}
→ Update violation information
→ Permissions: Legal Case Management, Payment Processing

DELETE /api/v1/traffic/violations/{id}
→ Delete violation record
→ Permissions: Administrator only

# Citations
GET /api/v1/traffic/citations
→ Get all citations with filtering
→ Permissions: Legal Case Management, Citizen Portal

GET /api/v1/traffic/citations/{id}
→ Get specific citation details
→ Permissions: Legal Case Management, Citizen Portal

POST /api/v1/traffic/citations
→ Issue citation
→ Permissions: Traffic Enforcement Officers

PUT /api/v1/traffic/citations/{id}
→ Update citation information
→ Permissions: Treasurer Collection

DELETE /api/v1/traffic/citations/{id}
→ Delete citation record
→ Permissions: Administrator only
```

### Payment Management
```
GET /api/v1/traffic/payments
→ Get payment records with filtering
→ Permissions: Treasurer Collection, Legal Case Management

POST /api/v1/traffic/payments
→ Record citation payment
→ Permissions: Treasurer Collection

GET /api/v1/traffic/reports/payments
→ Generate payment reports
→ Permissions: Treasurer Collection
```

### Legal Case Integration
```
GET /api/v1/traffic/legal-cases
→ Get traffic legal cases with filtering
→ Permissions: Legal Case Management

POST /api/v1/traffic/legal-cases
→ Escalate to legal case
→ Permissions: Legal Case Management

GET /api/v1/traffic/reports/legal-cases
→ Generate legal case reports
→ Permissions: Legal Case Management
```

---

## Module 4: Vehicle Inspection & Registration API

### Inspection Scheduling & Management
```
# Inspections
GET /api/v1/vehicle/inspections
→ Get all vehicle inspections with filtering
→ Permissions: Vehicle Inspection, PUV Database

GET /api/v1/vehicle/inspections/{id}
→ Get specific inspection details
→ Permissions: Vehicle Inspection, PUV Database

POST /api/v1/vehicle/inspections
→ Schedule vehicle inspection
→ Permissions: Vehicle Owners, Franchise Management

PUT /api/v1/vehicle/inspections/{id}
→ Update inspection information and results
→ Permissions: Vehicle Inspection Officers

DELETE /api/v1/vehicle/inspections/{id}
→ Delete inspection record
→ Permissions: Administrator only

# Registrations
GET /api/v1/vehicle/registrations
→ Get all vehicle registrations with filtering
→ Permissions: Vehicle Inspection, Franchise Management

GET /api/v1/vehicle/registrations/{id}
→ Get specific registration details
→ Permissions: Vehicle Inspection, Franchise Management

POST /api/v1/vehicle/registrations
→ Process vehicle registration
→ Permissions: Vehicle Inspection, PUV Database

PUT /api/v1/vehicle/registrations/{id}
→ Update registration status
→ Permissions: Vehicle Inspection

DELETE /api/v1/vehicle/registrations/{id}
→ Delete registration record
→ Permissions: Administrator only
```

### Compliance Certification
```
GET /api/v1/vehicle/certifications
→ Get vehicle certifications with filtering
→ Permissions: All modules, Citizen Portal

POST /api/v1/vehicle/certifications
→ Issue compliance certificate
→ Permissions: Vehicle Inspection Officers

GET /api/v1/vehicle/reports/certifications
→ Generate certification reports
→ Permissions: Franchise Management, Traffic Violation
```

### Inspection Reports
```
GET /api/v1/vehicle/reports/inspections
→ Generate inspection reports
→ Permissions: Vehicle Inspection, Management

GET /api/v1/vehicle/reports/compliance
→ Generate compliance reports
→ Permissions: Vehicle Inspection, Management
```

---

## Module 5: Parking & Terminal Management API

### Terminal Assignment & Scheduling
```
# Terminal Assignments
GET /api/v1/parking/assignments
→ Get all terminal assignments with filtering
→ Permissions: Parking Terminal Management, Traffic Violation

GET /api/v1/parking/assignments/{id}
→ Get specific assignment details
→ Permissions: Parking Terminal Management, Traffic Violation

POST /api/v1/parking/assignments
→ Assign vehicle to terminal
→ Permissions: Parking Terminal Management

PUT /api/v1/parking/assignments/{id}
→ Update assignment schedule
→ Permissions: Parking Terminal Management

DELETE /api/v1/parking/assignments/{id}
→ Delete assignment record
→ Permissions: Administrator only

# Terminals
GET /api/v1/parking/terminals
→ Get all terminals with filtering
→ Permissions: All modules, Citizen Portal

GET /api/v1/parking/terminals/{id}
→ Get specific terminal details
→ Permissions: All modules, Citizen Portal

POST /api/v1/parking/terminals
→ Create new terminal
→ Permissions: Parking Terminal Management

PUT /api/v1/parking/terminals/{id}
→ Update terminal information
→ Permissions: Parking Terminal Management

DELETE /api/v1/parking/terminals/{id}
→ Delete terminal record
→ Permissions: Administrator only
```

### Route Management
```
GET /api/v1/parking/routes
→ Get all terminal routes with filtering
→ Permissions: All modules, Citizen Portal

POST /api/v1/parking/routes
→ Define terminal routes
→ Permissions: Parking Terminal Management

GET /api/v1/parking/reports/routes
→ Generate route analytics reports
→ Permissions: Parking Terminal Management, Dashboards
```

### Compliance Monitoring
```
GET /api/v1/parking/compliance
→ Get compliance records with filtering
→ Permissions: Parking Terminal Management, Traffic Violation

POST /api/v1/parking/compliance
→ Record compliance check
→ Permissions: Parking Terminal Officers

GET /api/v1/parking/reports/compliance
→ Generate compliance reports
→ Permissions: Parking Terminal Management, Dashboards
```

## Module 6: PTSMD Analytics API

### Dashboard Analytics
```
# Statistics
GET /api/v1/analytics/statistics
→ Get system-wide statistics and metrics
→ Permissions: Dashboards, Management

GET /api/v1/analytics/statistics/{module}
→ Get module-specific statistics
→ Permissions: Dashboards, Management

# Reports
GET /api/v1/analytics/reports
→ Get available analytics reports
→ Permissions: Dashboards, Management

GET /api/v1/analytics/reports/{type}
→ Generate specific analytics report
→ Permissions: Dashboards, Management

POST /api/v1/analytics/reports/custom
→ Generate custom analytics report
→ Permissions: Dashboards, Management
```

### Performance Metrics
```
GET /api/v1/analytics/performance/modules
→ Get module performance metrics
→ Permissions: System Administrators

GET /api/v1/analytics/performance/api
→ Get API performance metrics
→ Permissions: System Administrators

GET /api/v1/analytics/performance/system
→ Get system performance metrics
→ Permissions: System Administrators
```

### Data Insights
```
GET /api/v1/analytics/insights/trends
→ Get data trend insights
→ Permissions: Management, Dashboards

GET /api/v1/analytics/insights/predictions
→ Get predictive analytics
→ Permissions: Management, Dashboards

GET /api/v1/analytics/insights/recommendations
→ Get system recommendations
→ Permissions: Management, Dashboards
```

---

## External System Integration APIs

**Status Legend:**
- ✅ **EXISTING**: Endpoint exists in the current codebase
- 🔧 **CONCEPTUAL**: Endpoint designed for external integration (not yet implemented)

## External System Access Capabilities

### External System Endpoint Summary

#### Public Utility Vehicle Management
```
✅ POST /api/v1/puv/operators → register public utility vehicle operator
✅ GET /api/v1/puv/operators/{id} → get PUV operator details
🔧 POST /api/v1/external/puv/vehicles → register public utility vehicle
🔧 GET /api/v1/external/puv/vehicles/{id} → get PUV vehicle details
```

#### Franchise Management
```
✅ POST /api/v1/franchise/applications → apply for transport franchise
✅ GET /api/v1/franchise/franchises/{id} → track franchise status
🔧 POST /api/v1/external/franchise/applications → external franchise application
🔧 GET /api/v1/external/franchise/status/{id} → external franchise status check
```

#### Traffic Violation Management
```
✅ POST /api/v1/traffic/violations → issue traffic violation ticket
✅ GET /api/v1/traffic/violations/{id} → get violation details
🔧 POST /api/v1/external/traffic/tickets → external traffic ticket submission
🔧 GET /api/v1/external/traffic/tickets/{id} → external ticket status check
```

#### Vehicle Inspection Management
```
✅ POST /api/v1/vehicle/inspections → request vehicle inspection
✅ GET /api/v1/vehicle/inspections/{id} → track inspection status
🔧 POST /api/v1/external/vehicle/inspection-requests → external inspection request
🔧 GET /api/v1/external/vehicle/inspection-status/{id} → external inspection status
```

#### Parking & Terminal Management
```
🔧 POST /api/v1/external/parking/bookings → book parking/terminal space
🔧 GET /api/v1/external/parking/bookings/{id} → check parking request status
🔧 POST /api/v1/external/terminal/assignments → request terminal assignment
🔧 GET /api/v1/external/terminal/assignments/{id} → check terminal assignment status
```

#### Revenue & Payment Management
```
✅ GET /api/v1/traffic/reports/payments → get payment reports
✅ GET /api/v1/franchise/reports/payments → get franchise payment reports
🔧 POST /api/v1/external/payments/violations → process violation payments
🔧 POST /api/v1/external/payments/franchises → process franchise payments
🔧 GET /api/v1/external/payments/status/{transaction_id} → check payment status
```

### What External Systems Can Access in Your System

#### Citizen Information & Engagement Systems

**Feedback And Grievance Portal**
- **GET**: Can retrieve traffic violation appeal portal access
  - Endpoint: `/api/v1/external/appeals/portal-access`
  - Purpose: Opens portal interface for citizens to appeal traffic violations
  - Data Retrieved: Portal access tokens, violation details for appeal

- **POST**: Can submit violation appeals to your system
  - Endpoint: `/api/v1/external/appeals/submit`
  - Purpose: Citizens submit appeals on traffic violations through external portal
  - Data Sent: Appeal details, violation ID, citizen information, supporting documents

**Citizen Registry System**
- **POST**: Can receive violator identity data from your system
  - Endpoint: `/api/v1/external/citizen-registry/violator-identity`
  - Purpose: Links violator identity with registered citizens
  - Data Received: Violator details, citizen ID, violation context

**Notification and Alert System**
- **POST**: Can receive violation notification data from your system
  - Endpoint: `/api/v1/external/notifications/violation-alerts`
  - Purpose: Receives traffic violation notification data for citizen alerts
  - Data Received: Violation details, citizen contact info, notification preferences

#### Permits & Licensing Management Systems

**Public Utility Vehicle Registry**
- **GET**: Can validate vehicle registration status
  - Endpoint: `/api/v1/external/puv/vehicle-validation`
  - Purpose: Validate if the vehicle is registered in your system
  - Data Retrieved: Vehicle registration status, operator details, compliance info

**E-Permit Tracker System**
- **POST**: Can receive transport unit registration updates
  - Endpoint: `/api/v1/external/e-permit/transport-registration`
  - Purpose: Updates transport unit registration in e-permit system
  - Data Received: Vehicle details, registration status, permit information

- **POST**: Can receive vehicle franchise status updates
  - Endpoint: `/api/v1/external/e-permit/franchise-status`
  - Purpose: Updates vehicle status for franchise in e-permit tracker
  - Data Received: Franchise status, vehicle ID, validity dates, route assignments

**Franchise Management System**
- **GET**: Can validate franchise status
  - Endpoint: `/api/v1/external/franchise/validation`
  - Purpose: Checks if the applicant has a valid franchise
  - Data Retrieved: Franchise validity, status, expiration dates, route permissions

#### Revenue Collection & Treasury Services

**Treasury Dashboard & Report System**
- **POST**: Can receive violation revenue reports
  - Endpoint: `/api/v1/external/treasury/violation-revenue`
  - Purpose: Receives traffic violation revenue reports
  - Data Received: Estimated violation revenue, collected amounts, payment status, financial summaries

### Data Exchange Formats

#### Traffic Violation Data
```json
{
  "violation_id": "string",
  "violator_info": {
    "citizen_id": "string",
    "name": "string",
    "contact_details": {}
  },
  "violation_details": {
    "type": "string",
    "location": "string",
    "timestamp": "ISO 8601",
    "fine_amount": "decimal"
  },
  "status": "pending|paid|appealed|dismissed"
}
```

#### Vehicle Registration Data
```json
{
  "vehicle_id": "string",
  "plate_number": "string",
  "registration_status": "active|expired|suspended",
  "operator_details": {
    "operator_id": "string",
    "license_info": {}
  },
  "compliance_status": "compliant|non_compliant"
}
```

#### Franchise Validation Data
```json
{
  "franchise_id": "string",
  "applicant_id": "string",
  "franchise_status": "active|pending|expired|revoked",
  "validity_period": {
    "start_date": "ISO 8601",
    "end_date": "ISO 8601"
  },
  "route_assignments": []
}
```

#### Revenue Report Data
```json
{
  "report_period": {
    "start_date": "ISO 8601",
    "end_date": "ISO 8601"
  },
  "violation_revenue": {
    "estimated_amount": "decimal",
    "collected_amount": "decimal",
    "pending_amount": "decimal",
    "collection_rate": "percentage"
  },
  "transaction_summary": {
    "total_violations": "integer",
    "paid_violations": "integer",
    "pending_violations": "integer"
  }
}
```

### Citizen Information & Engagement Subsystem

#### Feedback And Grievance Portal ⇄ Traffic Violation Ticketing System
```
✅ EXISTING: GET /api/v1/traffic/violations
→ Opens portal for appealing traffic violations (filtered by citizen)
→ Direction: Feedback Portal → Traffic Violation System
→ Permissions: Citizens, Feedback Portal

✅ EXISTING: POST /api/v1/traffic/legal-cases
→ Citizens submit appeals on traffic violations
→ Direction: Citizens → Traffic Violation System
→ Permissions: Citizens, Feedback Portal

🔧 CONCEPTUAL: GET /api/v1/external/citizen/appeal-portal
→ Access citizen appeal portal interface
→ Direction: Citizens → Feedback Portal
→ Permissions: Public, Citizens

🔧 CONCEPTUAL: POST /api/v1/external/citizen/grievances
→ Submit general grievances and feedback
→ Direction: Citizens → Feedback Portal
→ Permissions: Public, Citizens
```

#### Traffic Violation Ticketing System → Citizen Registry System
```
✅ EXISTING: GET /api/v1/puv/operators
→ Links violator identity with registered operators/citizens
→ Direction: Traffic Violation System → Citizen Registry
→ Permissions: Traffic Violation System

🔧 CONCEPTUAL: POST /api/v1/external/citizen/identity-verification
→ Verify citizen identity for violation processing
→ Direction: Traffic Violation System → Citizen Registry
→ Permissions: Traffic Violation System

🔧 CONCEPTUAL: GET /api/v1/external/citizen/profile/{citizen_id}
→ Get citizen profile information
→ Direction: Traffic Violation System → Citizen Registry
→ Permissions: Traffic Violation System
```

#### Notification and Alert System ← Traffic Violation Ticketing System
```
✅ EXISTING: GET /api/v1/traffic/reports/legal-cases
→ Sends traffic violation notification data
→ Direction: Traffic Violation System → Notification System
→ Permissions: Traffic Violation System

🔧 CONCEPTUAL: POST /api/v1/external/notifications/send-violation-alert
→ Send violation notifications to citizens
→ Direction: Traffic Violation System → Notification System
→ Permissions: Traffic Violation System

🔧 CONCEPTUAL: POST /api/v1/external/notifications/send-payment-reminder
→ Send payment reminder notifications
→ Direction: Traffic Violation System → Notification System
→ Permissions: Traffic Violation System

🔧 CONCEPTUAL: POST /api/v1/external/notifications/send-court-summons
→ Send court summons notifications
→ Direction: Legal Case Management → Notification System
→ Permissions: Legal Case Management
```

### Permits & Licensing Management Subsystem

#### Franchise and Transport Permit → Public Utility Vehicle
```
✅ EXISTING: GET /api/v1/puv/vehicles/{id}
→ Validate if the Vehicle is Registered
→ Direction: Franchise & Transport Permit → PUV Database
→ Permissions: Franchise & Transport Permit System
```

#### Public Utility Vehicle → E-Permit Tracker
```
✅ EXISTING: PUT /api/v1/vehicle/registrations/{id}
→ Update the Transport Unit Registration
→ Direction: PUV Database → E-Permit Tracker
→ Permissions: PUV Database System

🔧 CONCEPTUAL: GET /api/v1/external/e-permit/vehicle-status/{vehicle_id}
→ Check vehicle permit status in e-permit system
→ Direction: PUV Database → E-Permit Tracker
→ Permissions: PUV Database System

🔧 CONCEPTUAL: POST /api/v1/external/e-permit/vehicle-registration
→ Register new vehicle in e-permit system
→ Direction: PUV Database → E-Permit Tracker
→ Permissions: PUV Database System
```

#### Franchise and Transport Permit → Franchise Management System
```
✅ EXISTING: GET /api/v1/franchise/franchises/{id}
→ Checks if the applicant have a valid franchise
→ Direction: Franchise & Transport Permit → Franchise Management
→ Permissions: Franchise & Transport Permit System

🔧 CONCEPTUAL: GET /api/v1/external/permits/franchise-requirements
→ Get franchise permit requirements and forms
→ Direction: Citizens → Franchise and Transport Permit
→ Permissions: Public, Citizens

🔧 CONCEPTUAL: POST /api/v1/external/permits/franchise-application
→ Submit online franchise application
→ Direction: Citizens → Franchise and Transport Permit
→ Permissions: Public, Citizens
```

#### Franchise Management System → E-Permit Tracker
```
✅ EXISTING: PUT /api/v1/franchise/franchises/{id}
→ Updates vehicle status for franchise
→ Direction: Franchise Management → E-Permit Tracker
→ Permissions: Franchise Management System

🔧 CONCEPTUAL: POST /api/v1/external/e-permit/franchise-approval
→ Send franchise approval to e-permit system
→ Direction: Franchise Management → E-Permit Tracker
→ Permissions: Franchise Management System

🔧 CONCEPTUAL: GET /api/v1/external/e-permit/franchise-status/{franchise_id}
→ Check franchise status in e-permit system
→ Direction: Franchise Management → E-Permit Tracker
→ Permissions: Franchise Management System
```

### Revenue Collection & Treasury Services

#### Treasury Dashboard & Report ← Traffic Violation Ticketing
```
✅ EXISTING: GET /api/v1/traffic/reports/payments
→ Send Traffic violation report (estimated violation revenue and collected amounts)
→ Direction: Traffic Violation System → Treasury Dashboard & Report
→ Permissions: Traffic Violation System

🔧 CONCEPTUAL: POST /api/v1/external/treasury/revenue-collection
→ Submit collected revenue data to treasury
→ Direction: Traffic Violation System → Treasury
→ Permissions: Traffic Violation System

🔧 CONCEPTUAL: GET /api/v1/external/treasury/payment-status/{payment_id}
→ Check payment status in treasury system
→ Direction: Traffic Violation System → Treasury
→ Permissions: Traffic Violation System
```

#### Treasury Dashboard ← Franchise Management
```
✅ EXISTING: GET /api/v1/franchise/reports/payments
→ Sends franchise fee revenue data to treasury
→ Direction: Franchise Management → Treasury Dashboard
→ Permissions: Franchise Management System

🔧 CONCEPTUAL: POST /api/v1/external/treasury/franchise-revenue
→ Submit franchise revenue data to treasury
→ Direction: Franchise Management → Treasury
→ Permissions: Franchise Management System
```

#### Treasury Dashboard ← Vehicle Inspection & Registration
```
✅ EXISTING: GET /api/v1/vehicle/reports/payments
→ Sends inspection fee revenue data to treasury
→ Direction: Vehicle Inspection → Treasury Dashboard
→ Permissions: Vehicle Inspection System

🔧 CONCEPTUAL: POST /api/v1/external/treasury/inspection-revenue
→ Submit inspection revenue data to treasury
→ Direction: Vehicle Inspection → Treasury
→ Permissions: Vehicle Inspection System
```

#### Online Payment Gateway ⇄ All Systems
```
🔧 CONCEPTUAL: POST /api/v1/external/payment/process-violation-payment
→ Process traffic violation payments
→ Direction: Citizens → Payment Gateway
→ Permissions: Public, Citizens

🔧 CONCEPTUAL: POST /api/v1/external/payment/process-franchise-payment
→ Process franchise application payments
→ Direction: Citizens → Payment Gateway
→ Permissions: Public, Citizens

🔧 CONCEPTUAL: POST /api/v1/external/payment/process-inspection-payment
→ Process vehicle inspection payments
→ Direction: Citizens → Payment Gateway
→ Permissions: Public, Citizens

🔧 CONCEPTUAL: GET /api/v1/external/payment/transaction-status/{transaction_id}
→ Check payment transaction status
→ Direction: All Systems → Payment Gateway
→ Permissions: All Systems, Citizens
```

### Additional External System Integrations

#### SMS Gateway Service ← All Systems
```
🔧 CONCEPTUAL: POST /api/v1/external/sms/send-violation-notice
→ Send SMS notifications for traffic violations
→ Direction: Traffic Violation System → SMS Gateway
→ Permissions: Traffic Violation System

🔧 CONCEPTUAL: POST /api/v1/external/sms/send-franchise-update
→ Send SMS updates for franchise applications
→ Direction: Franchise Management → SMS Gateway
→ Permissions: Franchise Management System

🔧 CONCEPTUAL: POST /api/v1/external/sms/send-inspection-reminder
→ Send SMS reminders for vehicle inspections
→ Direction: Vehicle Inspection → SMS Gateway
→ Permissions: Vehicle Inspection System

🔧 CONCEPTUAL: POST /api/v1/external/sms/send-payment-confirmation
→ Send SMS payment confirmations
→ Direction: Payment Gateway → SMS Gateway
→ Permissions: Payment Gateway
```

#### Email Service ← All Systems
```
🔧 CONCEPTUAL: POST /api/v1/external/email/send-violation-notice
→ Send email notifications for traffic violations
→ Direction: Traffic Violation System → Email Service
→ Permissions: Traffic Violation System

🔧 CONCEPTUAL: POST /api/v1/external/email/send-franchise-documents
→ Send franchise application documents via email
→ Direction: Franchise Management → Email Service
→ Permissions: Franchise Management System

🔧 CONCEPTUAL: POST /api/v1/external/email/send-inspection-certificate
→ Send vehicle inspection certificates via email
→ Direction: Vehicle Inspection → Email Service
→ Permissions: Vehicle Inspection System

🔧 CONCEPTUAL: POST /api/v1/external/email/send-payment-receipt
→ Send payment receipts via email
→ Direction: Payment Gateway → Email Service
→ Permissions: Payment Gateway
```

#### Mapping and GPS Service ⇄ Parking & Terminal Management
```
🔧 CONCEPTUAL: GET /api/v1/external/maps/terminal-locations
→ Get terminal location coordinates
→ Direction: Parking & Terminal → Mapping Service
→ Permissions: Parking & Terminal System

🔧 CONCEPTUAL: POST /api/v1/external/maps/route-optimization
→ Request route optimization for terminals
→ Direction: Parking & Terminal → Mapping Service
→ Permissions: Parking & Terminal System

🔧 CONCEPTUAL: GET /api/v1/external/maps/traffic-data
→ Get real-time traffic data for route planning
→ Direction: Parking & Terminal → Mapping Service
→ Permissions: Parking & Terminal System
```

#### Document Management System ⇄ All Systems
```
🔧 CONCEPTUAL: POST /api/v1/external/documents/upload-violation-evidence
→ Upload violation evidence documents
→ Direction: Traffic Violation → Document Management
→ Permissions: Traffic Violation System

🔧 CONCEPTUAL: POST /api/v1/external/documents/upload-franchise-documents
→ Upload franchise application documents
→ Direction: Franchise Management → Document Management
→ Permissions: Franchise Management System

🔧 CONCEPTUAL: POST /api/v1/external/documents/upload-inspection-reports
→ Upload vehicle inspection reports
→ Direction: Vehicle Inspection → Document Management
→ Permissions: Vehicle Inspection System

🔧 CONCEPTUAL: GET /api/v1/external/documents/retrieve/{document_id}
→ Retrieve stored documents
→ Direction: All Systems → Document Management
→ Permissions: All Systems
```

---

## Authentication & Authorization

### API Key Requirements
- **Internal Modules**: Require system-level API keys
- **External Systems**: Require registered API keys with specific permissions
- **Public Endpoints**: Rate-limited, no authentication required
- **Citizen Access**: User authentication required

### Permission Levels
1. **READ_ONLY**: Can only retrieve data
2. **READ_WRITE**: Can retrieve and update data
3. **ADMIN**: Full access including delete operations
4. **PUBLIC**: Limited read access to public information

### Rate Limiting
- **Internal APIs**: 1000 requests/minute
- **External APIs**: 100 requests/minute
- **Public APIs**: 50 requests/minute
- **Citizen APIs**: 200 requests/minute

---

## Error Handling

### Standard HTTP Status Codes
- **200**: Success
- **201**: Created
- **400**: Bad Request
- **401**: Unauthorized
- **403**: Forbidden
- **404**: Not Found
- **429**: Too Many Requests
- **500**: Internal Server Error

### Error Response Format
```json
{
  "error": {
    "code": "ERROR_CODE",
    "message": "Human readable error message",
    "details": "Additional error details",
    "timestamp": "2024-01-15T10:30:00Z"
  }
}
```

---

## Data Formats

### Request/Response Format
- **Content-Type**: `application/json`
- **Character Encoding**: UTF-8
- **Date Format**: ISO 8601 (YYYY-MM-DDTHH:mm:ssZ)

### Common Data Structures
```json
{
  "vehicle": {
    "id": "string",
    "plate_number": "string",
    "operator_id": "string",
    "franchise_id": "string",
    "status": "active|inactive|suspended"
  },
  "operator": {
    "id": "string",
    "name": "string",
    "license_number": "string",
    "contact_info": {}
  },
  "franchise": {
    "id": "string",
    "status": "active|pending|expired|revoked",
    "validity_date": "string",
    "route_assignments": []
  }
}
```

This API documentation provides a comprehensive framework for inter-module communication and external system integration within the Transport & Mobility Management System.