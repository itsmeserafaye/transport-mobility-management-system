# Transport and Mobility Management System - Complete Process Flow

## Phase 1: User Registration & Portal Access

### Step 1: User Registration *(Module 1: PUV Database)*

**Sub-module 1.1: Vehicle & Operator Records**
- Collect operator information (name, address, contact details)
- Validate government-issued IDs and licenses
- Create unique operator ID
- Store vehicle registration data

**Sub-module 1.2: Compliance Status Management**
- Track franchise validity status
- Monitor inspection compliance
- Update violation records
- Generate compliance reports

### Step 2: Portal Access Management *(All Portals)*

**Sub-module 1.3: RBAC Portal Access**
- TPRS Portal: Encoder, Compliance Specialist, Route Officer, Approver, Administrator
- PTSMD Portal: Violation Officer, Enforcement Specialist, Analytics Officer, Legal Officer
- Citizen Portal: Franchise Applicant, PUV Operator/Driver, Commuter (Public)

---

## Phase 2: Franchise Application & Management

### Step 3: Franchise Application *(Module 2: Franchise Management)*

**Sub-module 2.1: Franchise Application & Workflow**
- Receive franchise applications
- Assign application ID
- Route to appropriate workflow
- Set processing timeline

### Step 4: Document Management *(Module 2: Franchise Management)*

**Sub-module 2.2: Document Repository**
- Store legal documents (permits, licenses, insurance)
- Organize by document type and date
- Create searchable metadata
- Maintain version control

### Step 5: Franchise Lifecycle *(Module 2: Franchise Management)*

**Sub-module 2.3: Franchise Lifecycle Management**
- Process new applications
- Handle renewals and amendments
- Manage revocations
- Track franchise status

### Step 6: Route Publication *(Module 2: Franchise Management)*

**Sub-module 2.4: Route & Schedule Publication**
- Define official routes
- Set schedule parameters
- Publish to citizen portal
- Update route databases

---

## Phase 3: Vehicle Inspection & Compliance

### Step 7: Inspection Scheduling *(Module 4: Vehicle Inspection & Registration)*

**Sub-module 4.1: Inspection Scheduling**
- Schedule vehicle inspections
- Manage inspection appointments
- Send notifications to operators
- Track scheduling compliance

### Step 8: Inspection Processing *(Module 4: Vehicle Inspection & Registration)*

**Sub-module 4.2: Inspection Result Recording**
- Record inspection results
- Generate compliance certificates
- Update vehicle status
- Flag non-compliant vehicles

### Step 9: Inspection History *(Module 4: Vehicle Inspection & Registration)*

**Sub-module 4.3: Inspection History Tracking**
- Maintain inspection records
- Track compliance trends
- Generate historical reports
- Support renewal decisions

---

## Phase 4: Traffic Violation Management

### Step 10: Violation Management *(Module 3: Traffic Violation Ticketing)*

**Sub-module 3.1: Violation Record Management**
- Store violation records
- Link to vehicle/operator data
- Track settlement status
- Generate violation reports

### Step 11: TVT Analytics *(Module 3: Traffic Violation Ticketing)*

**Sub-module 3.2: TVT Analytics**
- Generate violation analytics
- Identify repeat offenders
- Support enforcement decisions
- Track violation trends

### Step 12: Revenue Integration *(Module 3: Traffic Violation Ticketing)*

**Sub-module 3.3: Revenue Integration**
- Generate violation revenue reports
- Monitor settlement status tracking
- Calculate collection statistics
- Integrate with treasury systems



---

## Phase 5: Terminal & Parking Management

### Step 13: Terminal Assignment *(Module 5: Parking & Terminal Management)*

**Sub-module 5.1: Terminal Assignment Management**
- Assign vehicles to terminals
- Manage TODA assignments
- Track terminal capacity
- Validate assignments for franchise

### Step 14: Directory Management *(Module 5: Parking & Terminal Management)*

**Sub-module 5.2: Roster & Directory**
- Maintain operator directories
- Update terminal assignments
- Publish public directories
- Track operator locations

### Step 15: Public Access *(Module 5: Parking & Terminal Management)*

**Sub-module 5.3: Public Transparency**
- Provide public terminal information
- Display TODA rosters
- Show terminal locations
- Enable public queries

---

## Phase 6: Predictive Analytics & Dashboards

### Step 16: TPRS Analytics *(Dashboards - TPRS Portal)*

**Sub-module 6.1: Demand Forecasting Dashboard**
- Display ridership time series
- Show route utilization forecasts
- Generate heatmaps for new franchises
- Track compliance KPIs

### Step 17: PTSMD Analytics *(Dashboards - PTSMD Portal)*

**Sub-module 6.2: Enforcement Analytics Dashboard**
- Show violation heatmaps and hotspots
- Display demand-based enforcement deployment
- Track repeat offender analytics
- Monitor enforcement effectiveness

### Step 18: Public Analytics *(Dashboards - Citizen Portal)*

**Sub-module 6.3: Public Information Dashboard**
- Display public demand trends per route
- Show TODA terminal distribution maps
- Provide schedules with demand forecasts
- Enable route planning tools

---

## Phase 7: External System Integration

### Step 19: Government Integration *(External Connections)*

**Sub-module 7.1: LGU System Integration**
- Connect to Citizen Registry Portal
- Sync with Treasurer/Finance systems
- Link to Legal Case Management
- Update Road Monitoring systems

### Step 20: Transport Integration *(External Connections)*

**Sub-module 7.2: Transport System Integration**
- Connect to LTO/MVIS Database
- Sync with Transport Permit Subsystem
- Link to Insurance Systems
- Update Urban Planning GIS

### Step 21: Citizen Services Integration *(External Connections)*

**Sub-module 7.3: Citizen Service Integration**
- Connect to Citizen Transparency Portal
- Link to Grievance Systems
- Sync with LGU Appointments
- Update Document Records

---

## Process Flow Summary

**Key Data Flow Points:**
- `operator_id` flows through all modules as primary identifier
- `franchise_id` tracks franchises from application to completion
- `vehicle_id` manages vehicle registration and compliance
- `violation_id` tracks violations and enforcement
- `terminal_id` manages terminal assignments and TODA rosters
- `inspection_id` tracks vehicle inspections and compliance

**Critical Integration Dependencies:**
- Phase 1 → Phase 2: Operator registration enables franchise applications
- Phase 2 → Phase 3: Franchise approval triggers inspection requirements
- Phase 3 → Phase 4: Inspection compliance affects violation processing
- Phase 4 → Phase 5: Violation status impacts terminal assignments
- All Phases → Phase 6: Continuous analytics and forecasting
- Phase 6 → Phase 7: Analytics drive external system updates

**Real-Time Data Synchronization:**
- Compliance status updates every inspection cycle

- Terminal assignments update in real-time
- Predictive analytics refresh daily for demand forecasting
- Dashboard KPIs update every 15 minutes during business hours