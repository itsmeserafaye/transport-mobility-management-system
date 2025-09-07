**Government Service Transport & Mobility Management System with Predictive Analytics for PUV Demand Forecasting**

**Users (Portals)**
**1. TPRS Portal (Transport Permits & Regulatory Service)**

**Purpose:** Manage franchises, inspections, permits validation, and compliance.
**Internal Roles (via RBAC):** Encoder, Compliance Specialist, Route Officer, Approver, Administrator

**2. PTSMD Portal (Public Transport Services & Mobility Department)**

**Purpose:** Manage traffic violations, enforcement monitoring, and analytics.
**Internal Roles (via RBAC):** Violation Officer, Enforcement Specialist, Analytics Officer, Legal Officer, Approver, Administrator

**3. Citizen / Operator Portal**

**Purpose:** Provide self-service access for transport operators and commuters.
**Users:**

Franchise Applicant → Apply/renew franchise, upload documents, track status

PUV Operator / Driver → View compliance, violations, terminal assignments

Commuter (Public) → Access routes, schedules, demand trends, terminal info

**Modules**
**1. PUV Database**

Vehicle & Operator Records

Compliance Status Management

Violation History Integration

**2. Franchise Management (TMM)**

Franchise Application & Workflow

Document Repository

Franchise Lifecycle Management

Route & Schedule Publication

**3. Traffic Violation Ticketing**

OCR Ticket Digitization

Violation Record Management

Linking & Analytics

Citizen / Operator Violation Portal

**4. Vehicle Inspection & Registration**

Inspection Scheduling

Inspection Result Recording

Inspection History Tracking

**5. Parking & Terminal Management**

Terminal Assignment Management

Roster & Directory

Public Transparency

**Dashboards (Predictive Analytics Integrated)**
**TPRS Portal Dashboard**

Demand forecast charts (ridership time series, route utilization).

Heatmaps of high-demand areas (for new franchises).

Compliance KPIs (inspection pass rate, franchise validity, violation trends).

PTSMD Portal Dashboard

Violation heatmaps and hotspots.

Demand-based enforcement deployment (high ridership = more monitoring).

Top violators and repeat offender analytics.

Citizen / Operator Portal Dashboard

Public demand trends per route.

TODA terminal distribution maps.

Schedules and official routes with demand forecast overlays.

Access (RBAC Mapping by Portal)
TPRS Portal

PUV Database → Read/Write (update compliance)

Franchise Management → Full (create, approve, renew, revoke)

Traffic Violation Ticketing → Read-only (view for decisions)

Vehicle Inspection & Registration → Full (schedule, record results, certificates)

Parking & Terminal Mgmt → Read-only (validate TODA assignments)

Dashboard (with Forecasts) → Full planning access

**PTSMD Portal**

PUV Database → Read-only (vehicles, operators, compliance snapshot)

Franchise Management → Read-only (view only, no approval rights)

Traffic Violation Ticketing → Full (upload OCR tickets, manage statuses, analytics)

Vehicle Inspection & Registration → Read-only (check roadworthiness)

Parking & Terminal Mgmt → Read-only (view terminal assignments)

Dashboard (with Forecasts) → View only (enforcement ops)

Citizen / Operator Portal

Franchise Management → Apply, upload documents, track status

Traffic Violation Ticketing → View violation records, settlement status

Parking & Terminal Mgmt → Public view of terminals & TODA roster

Dashboard (with Forecasts) → Public dashboards (routes, demand trends)

Internal Connections Between Submodules

PUV Database

Links with Franchise Management (validation)

Links with Traffic Violation Ticketing (violations linked to vehicles/operators)

Links with Vehicle Inspection (compliance updates)

Links with Parking & Terminal Mgmt (assignments)

Publishes registered units/routes to Citizen Portal

Franchise Management (TMM)

Requires inspection results before approval

Validates terminal assignments before granting franchise

Publishes routes/schedules to Citizen Portal

Traffic Violation Ticketing

Stores violations linked to PUV Database

Shares violation history with Franchise Management (renewal validation)

Allows citizens/operators to check violation status online

Vehicle Inspection & Registration

Updates PUV Database with inspection results

Ensures only inspected units get franchises

Parking & Terminal Management

Associates vehicles with terminals in PUV Database

Validates assignments for franchise approval

Publishes terminal listings to Citizen Portal

Dashboards (with Forecasts)

Pull data from PUV DB, Franchise Mgmt, Violations, Terminals

Provide KPIs, heatmaps, and demand forecasts

Adjust visualization per portal (TPRS = planning, PTSMD = enforcement, Citizen = info)

Internal Connection Module Flow

1. PUV Database

Vehicle Records → Flows to Franchise, Violation, Inspection

Compliance Status → Flows to Renewal, Enforcement

Violation History → Flows to Franchise, Enforcement Analytics

2. Franchise Management

Applications → Flows to Document Repository, Compliance Checker

Document Repository → Flows to Validation

Lifecycle Updates → Flows to PUV Database, Citizen Portal

Route Publication → Flows to Citizen Portal, Enforcement

3. Traffic Violation Ticketing

OCR Tickets → Flows to Record Mgmt

Violation Records → Flows to PUV Database, Enforcement Dashboard

Linking & Analytics → Flows to Franchise Renewal, Enforcement Analytics

Citizen Portal → Flows to Operator Self-service

4. Vehicle Inspection & Registration

Scheduling → Flows to Citizen Portal

Results Recording → Flows to Franchise, PUV Database

History → Flows to Compliance Snapshot

5. Parking & Terminal Management

Terminal Assignments → Flows to Franchise Mgmt

Directory → Flows to Citizen Portal

6. Dashboards (with Forecasts)

Forecasts → Flows to TPRS (planning), PTSMD (enforcement), Citizen Portal (public info)

Connections to Other Subsystems

PUV Database

Vehicle Records → Citizen Registry Portal (GET)

Compliance Status → Treasurer/Finance (POST)

Violation History → Legal Case Mgmt (GET)

Compliance Snapshot → Road Monitoring (POST)

Franchise Management (TMM)

Applications → Transport Permit Subsystem (POST)

Documents → LGU Document Records (POST)

Lifecycle Mgmt → Treasurer Collection (POST)

Routes → Citizen Transport Portal (GET)

Traffic Violation Ticketing

OCR → Enforcement OCR System (POST)

Records → Legal Adjudication (POST)

Analytics → Safety & Incident Monitoring (GET)

Portal → Citizen Grievance System (GET)

Vehicle Inspection & Registration

Scheduling → LGU Appointments (POST)

Results → LTO/MVIS Database (POST)

History → Insurance Systems (GET)

Parking & Terminal Management

Assignments → TODA Encoder Subsystem (POST)

Directory → Citizen Transparency Portal (GET)

Terminals → Urban Planning GIS (POST)

Dashboards (Forecasts)

Forecast Data → LGU Planning & Budgeting (GET)

Optimization → Urban Mobility Tools (POST)

External Data → Weather, Traffic, Events APIs (GET)