# Transport and Mobility Management System

A comprehensive web-based system for managing transport and mobility operations including PUV Database, Franchise Management, Traffic Violation Ticketing, Vehicle Inspection & Registration, and Terminal Management.

## 🚀 Features

- **PUV Database Management**: Operators, vehicles, compliance tracking, and violation history
- **Franchise Management**: Applications, documents, franchises, routes, and schedules
- **Traffic Violation Ticketing**: Violations, analytics, settlement, and revenue reports
- **Vehicle Inspection & Registration**: Inspections, registrations, compliance tracking, and scheduling
- **Terminal Management**: Terminal assignments, roster & directory, and public transparency
- **TPRS Portal**: Simplified portal for Transport Public Record System access

## 📋 Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- XAMPP (recommended for local development)

## 🛠️ Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/YOUR_USERNAME/transport-mobility-management-system.git
   cd transport-mobility-management-system
   ```

2. **Database Setup**
   - Start XAMPP and ensure MySQL is running
   - Create database: `transport_mobility_db`
   - Import schema: `mysql -u root -p transport_mobility_db < database/transport_and_mobility_management.sql`
   - **Note**: Database file may need to be downloaded separately due to size

3. **Create Required Directories**
   ```bash
   mkdir uploads uploads/documents uploads/tickets upload
   chmod 755 uploads upload (Linux/Mac)
   ```

3. **Configuration**
   - Database configuration is in `config/database.php`
   - Default settings work with XAMPP (localhost, root, no password)

4. **Access the System**
   - Administrator Portal: `http://localhost/transport_and_mobility_management_system/administrator/`
   - TPRS Portal: `http://localhost/transport_and_mobility_management_system/tprs/`

## 📚 System Modules

### Administrator Portal
- **PUV Database**: Vehicle & operator records, compliance status, violation history
- **Franchise Management**: Application workflow, document repository, lifecycle management
- **Traffic Violation Ticketing**: Violation records, analytics, revenue integration
- **Vehicle Inspection**: Scheduling, result recording, history tracking
- **Terminal Management**: Assignment management, roster & directory, public transparency

### TPRS Portal
- **PUV Database**: Compliance status management, violation history (view-only)
- Simplified interface for Transport Public Record System users

## 🔧 Technology Stack

- **Backend**: PHP, MySQL, PDO
- **Frontend**: HTML5, Tailwind CSS, JavaScript
- **Icons**: Lucide Icons
- **Database**: MySQL with comprehensive relational schema

## 📊 Database Schema

The system uses a comprehensive MySQL database with tables for:
- Operators and vehicles
- Compliance status and violation history
- Franchise applications and lifecycle
- Document repository and route schedules
- Inspection records and terminal assignments
- Revenue collections and analytics

## 🚦 Usage

1. **Administrator Access**: Full system management capabilities
2. **TPRS Portal**: Limited access for compliance monitoring and violation viewing
3. **Data Management**: CRUD operations with modal interfaces
4. **Reporting**: Export capabilities (CSV, Excel, PDF, Word)
5. **Analytics**: Violation analytics and compliance scoring

## 🔒 Security Features

- Input validation and sanitization
- Secure database queries with prepared statements
- Session management
- File upload restrictions
- XSS and SQL injection protection

## 📈 Key Features

- **Real-time Statistics**: Dashboard with live data from database
- **Modal-based UI**: Seamless user experience with modal dialogs
- **Responsive Design**: Works on desktop and mobile devices
- **Export Functionality**: Multiple export formats for reports
- **Search & Filter**: Advanced filtering and search capabilities
- **Compliance Scoring**: Automated compliance calculation based on business rules

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 📞 Support

For support and questions, please create an issue in the GitHub repository.

---

**Transport and Mobility Management System v1.0.0**  
Built with PHP, MySQL, and modern web technologies