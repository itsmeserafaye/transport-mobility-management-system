# Sub-Module Processes and Inter-Module Data Flow

## Module 1: PUV Database

### 1.1 Vehicle & Operator Records
**Process:**
- Collect operator information (name, address, contact details)
- Validate government-issued IDs and licenses
- Create unique operator ID
- Store vehicle registration data

**Data Output to Other Modules:**
- `operator_id` → Franchise Management
- `vehicle_data` → Vehicle Inspection & Registration
- `contact_information` → Traffic Violation Ticketing

### 1.2 Compliance Status Management
**Process:**
- Track franchise validity status
- Monitor inspection compliance
- Update violation records
- Generate compliance reports

**Data Output to Other Modules:**
- `compliance_status` → Franchise Management
- `inspection_due_dates` → Vehicle Inspection & Registration
- `violation_history` → Traffic Violation Ticketing

### 1.3 Violation History Integration
**Process:**
- Integrate violation records from ticketing system
- Link violations to operators and vehicles
- Track violation trends
- Generate violation summaries

**Data Input from Other Modules:**
- `violation_records` ← Traffic Violation Ticketing
- `settlement_status` ← Traffic Violation Ticketing

**Data Output to Other Modules:**
- `violation_summary` → Franchise Management
- `repeat_offender_flags` → Parking & Terminal Management
- `compliance_score` → Vehicle Inspection & Registration

---

## Module 2: Franchise Management (TMM)

### 2.1 Franchise Application & Workflow
**Process:**
- Receive franchise applications
- Assign application ID
- Route to appropriate workflow
- Set processing timeline

**Data Input from Other Modules:**
- `operator_id` ← PUV Database
- `inspection_results` ← Vehicle Inspection & Registration
- `terminal_assignment` ← Parking & Terminal Management

**Data Output to Other Modules:**
- `application_id` → Vehicle Inspection & Registration
- `workflow_status` → Dashboards
- `processing_timeline` → Traffic Violation Ticketing

### 2.2 Document Repository
**Process:**
- Store legal documents (permits, licenses, insurance)
- Organize by document type and date
- Create searchable metadata
- Maintain version control

**Data Input from Other Modules:**
- `inspection_certificates` ← Vehicle Inspection & Registration
- `terminal_assignments` ← Parking & Terminal Management

**Data Output to Other Modules:**
- `legal_documents` → Traffic Violation Ticketing
- `document_metadata` → Vehicle Inspection & Registration
- `version_history` → Dashboards

### 2.3 Franchise Lifecycle Management
**Process:**
- Process new applications
- Handle renewals and amendments
- Manage revocations
- Track franchise status

**Data Input from Other Modules:**
- `compliance_status` ← PUV Database
- `violation_summary` ← PUV Database
- `inspection_compliance` ← Vehicle Inspection & Registration

**Data Output to Other Modules:**
- `franchise_status` → PUV Database
- `renewal_notifications` → Traffic Violation Ticketing
- `lifecycle_updates` → Parking & Terminal Management

### 2.4 Route & Schedule Publication
**Process:**
- Define official routes
- Set schedule parameters
- Publish to citizen portal
- Update route databases

**Data Input from Other Modules:**
- `terminal_locations` ← Parking & Terminal Management
- `demand_forecasts` ← Dashboards

**Data Output to Other Modules:**
- `route_data` → Dashboards
- `schedule_info` → Parking & Terminal Management
- `public_routes` → Traffic Violation Ticketing

---

## Module 3: Traffic Violation Ticketing

### 3.1 Violation Record Management
**Process:**
- Store violation records
- Link to vehicle/operator data
- Track settlement status
- Generate violation reports

**Data Input from Other Modules:**
- `operator_id` ← PUV Database
- `vehicle_data` ← PUV Database

**Data Output to Other Modules:**
- `violation_records` → PUV Database
- `settlement_tracking` → Franchise Management
- `violation_reports` → Dashboards

### 3.2 TVT Analytics
**Process:**
- Generate violation analytics
- Identify repeat offenders
- Support enforcement decisions
- Track violation trends

**Data Input from Other Modules:**
- `compliance_score` ← PUV Database
- `franchise_status` ← Franchise Management

**Data Output to Other Modules:**
- `analytics_results` → Dashboards
- `enforcement_recommendations` → Parking & Terminal Management
- `repeat_offender_list` → Vehicle Inspection & Registration

### 3.3 Revenue Integration
**Process:**
- Generate violation revenue reports
- Monitor settlement status tracking
- Calculate collection statistics
- Integrate with treasury systems

**Data Input from Other Modules:**
- `violation_records` ← Violation Record Management
- `settlement_tracking` ← Violation Record Management

**Data Output to External Systems:**
- `violation_revenue_report` → Treasury Dashboard & Report



---

## Module 4: Vehicle Inspection & Registration

### 4.1 Inspection Scheduling
**Process:**
- Schedule vehicle inspections
- Manage inspection appointments
- Send notifications to operators
- Track scheduling compliance

**Data Input from Other Modules:**
- `vehicle_data` ← PUV Database
- `application_id` ← Franchise Management
- `inspection_due_dates` ← PUV Database

**Data Output to Other Modules:**
- `inspection_schedule` → Franchise Management
- `appointment_notifications` → Traffic Violation Ticketing
- `scheduling_metrics` → Dashboards

### 4.2 Inspection Result Recording
**Process:**
- Record inspection results
- Generate compliance certificates
- Update vehicle status
- Flag non-compliant vehicles

**Data Input from Other Modules:**
- `document_metadata` ← Franchise Management
- `compliance_score` ← Traffic Violation Ticketing

**Data Output to Other Modules:**
- `inspection_results` → Franchise Management
- `inspection_certificates` → Franchise Management
- `compliance_updates` → PUV Database

### 4.3 Inspection History Tracking
**Process:**
- Maintain inspection records
- Track compliance trends
- Generate historical reports
- Support renewal decisions

**Data Input from Other Modules:**
- `repeat_offender_list` ← Traffic Violation Ticketing

**Data Output to Other Modules:**
- `inspection_compliance` → Franchise Management
- `historical_trends` → Dashboards
- `renewal_recommendations` → Parking & Terminal Management

---

## Module 5: Parking & Terminal Management

### 5.1 Terminal Assignment Management
**Process:**
- Assign vehicles to terminals
- Manage TODA assignments
- Track terminal capacity
- Validate assignments for franchise

**Data Input from Other Modules:**
- `franchise_status` ← Franchise Management
- `enforcement_recommendations` ← Traffic Violation Ticketing
- `renewal_recommendations` ← Vehicle Inspection & Registration

**Data Output to Other Modules:**
- `terminal_assignment` → Franchise Management
- `terminal_assignments` → Franchise Management
- `capacity_reports` → Dashboards

### 5.2 Roster & Directory
**Process:**
- Maintain operator directories
- Update terminal assignments
- Publish public directories
- Track operator locations

**Data Input from Other Modules:**
- `lifecycle_updates` ← Franchise Management
- `schedule_info` ← Franchise Management

**Data Output to Other Modules:**
- `operator_directory` → Dashboards
- `terminal_locations` → Franchise Management
- `public_directory` → Traffic Violation Ticketing

### 5.3 Public Transparency
**Process:**
- Provide public terminal information
- Display TODA rosters
- Show terminal locations
- Enable public queries

**Data Input from Other Modules:**
- `repeat_offender_flags` ← PUV Database
- `public_routes` ← Franchise Management

**Data Output to Other Modules:**
- `public_info` → Dashboards
- `transparency_metrics` → Traffic Violation Ticketing
- `query_logs` → Vehicle Inspection & Registration

---

## Module 6: Dashboards (Predictive Analytics Integrated)

### 6.1 TPRS Portal Dashboard
**Process:**
- Display demand forecast charts (ridership time series, route utilization)
- Generate heatmaps of high-demand areas for new franchises
- Show compliance KPIs (inspection pass rate, franchise validity, violation trends)

**Data Input from Other Modules:**
- `workflow_status` ← Franchise Management
- `version_history` ← Franchise Management

- `scheduling_metrics` ← Vehicle Inspection & Registration
- `capacity_reports` ← Parking & Terminal Management

**Data Output to Other Modules:**
- `demand_forecasts` → Franchise Management
- `planning_insights` → Vehicle Inspection & Registration
- `kpi_alerts` → Traffic Violation Ticketing

### 6.2 PTSMD Portal Dashboard
**Process:**
- Show violation heatmaps and hotspots
- Display demand-based enforcement deployment
- Track repeat offender analytics

**Data Input from Other Modules:**
- `violation_reports` ← Traffic Violation Ticketing
- `analytics_results` ← Traffic Violation Ticketing
- `historical_trends` ← Vehicle Inspection & Registration

**Data Output to Other Modules:**
- `enforcement_priorities` → Traffic Violation Ticketing
- `hotspot_alerts` → Parking & Terminal Management
- `deployment_recommendations` → Franchise Management

### 6.3 Citizen/Operator Portal Dashboard
**Process:**
- Display public demand trends per route
- Show TODA terminal distribution maps
- Provide schedules and official routes with demand forecast overlays

**Data Input from Other Modules:**
- `route_data` ← Franchise Management
- `operator_directory` ← Parking & Terminal Management
- `public_info` ← Parking & Terminal Management


**Data Output to Other Modules:**
- `user_feedback` → Franchise Management
- `demand_patterns` → Traffic Violation Ticketing
- `service_requests` → Vehicle Inspection & Registration

---

## External System Connections

### Citizen Information & Engagement Subsystem:
**Feedback And Grievance Portal ⇄ Traffic Violation Ticketing System**
- `appeal_portal_access` → Opens portal for appealing traffic violations (GET)
- `violation_appeals` ← Citizens submit appeals on traffic violations (POST)

**Traffic Violation Ticketing System → Citizen Registry System**
- `violator_identity_data` → Links violator identity with registered citizens (POST)

**Notification and Alert System ← Traffic Violation Ticketing System**
- `violation_notification_data` ← Sends traffic violation notification data (POST)

### Permits & Licensing Management Subsystem:
**Franchise and Transport Permit → Public Utility Vehicle**
- `vehicle_validation_request` → Validate if the Vehicle is Registered (GET)

**Public Utility Vehicle → E-Permit Tracker**
- `transport_unit_registration` → Update the Transport Unit Registration (POST)

**Franchise and Transport Permit → Franchise Management System**
- `franchise_validation_request` → Checks if the applicant have a valid franchise (GET)

**Franchise Management System → E-Permit Tracker**
- `vehicle_franchise_status` → Updates vehicle status for franchise (POST)

### Revenue Collection & Treasury Services:
**Treasury Dashboard & Report ← Traffic Violation Ticketing**
- `violation_revenue_report` ← Send Traffic violation report (estimated violation revenue and collected amounts) (POST)

