# 🏢 Xeasy Laravel - Enterprise Expense Management System

[![Laravel](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Status](https://img.shields.io/badge/Status-Production%20Ready-brightgreen.svg)]()

A comprehensive, enterprise-grade expense management system built with Laravel 12, designed to streamline expense claim processing, reporting, and financial management for organizations of all sizes.

## 📋 Table of Contents

- [Description](#-description)
- [Features](#-features)
- [Technologies Used](#-technologies-used)
- [Installation](#-installation)
- [Usage](#-usage)
- [API Documentation](#-api-documentation)
- [Contributing](#-contributing)
- [License](#-license)
- [Contact](#-contact)
- [Acknowledgements](#-acknowledgements)

## 🎯 Description

Xeasy Laravel is a sophisticated expense management platform that automates and simplifies the entire expense claim lifecycle. From initial submission to final approval and reimbursement, the system provides a seamless experience for employees, managers, and finance teams.

### Key Benefits:
- **Streamlined Workflow**: Automated approval processes with configurable workflows
- **Real-time Analytics**: Comprehensive dashboards with actionable insights
- **Multi-company Support**: Handle multiple companies and financial years
- **Advanced Reporting**: Detailed reports with export capabilities
- **Role-based Access**: Granular permissions and security controls
- **API Integration**: RESTful APIs for third-party integrations

## ✨ Features

### 🏠 Dashboard & Analytics
- **Real-time Overview**: Live expense statistics and trends
- **Interactive Charts**: Visual representation of expense data
- **Department-wise Analysis**: Compare expenses across departments
- **Year-over-Year Comparison**: Track financial performance
- **Custom Filters**: Filter data by month, claim type, department, and status

### 📝 Expense Management
- **Multi-type Claims**: Support for various expense categories
- **Document Upload**: Image and PDF attachment support
- **Odometer Tracking**: Vehicle expense management with odometer readings
- **Status Tracking**: Real-time claim status updates
- **Approval Workflow**: Multi-level approval process

### 👥 User & Permission Management
- **Role-based Access Control**: Granular permission system
- **User Management**: Complete user lifecycle management
- **Permission Groups**: Organized permission structure
- **Menu Management**: Dynamic menu system with logging

### 📊 Reporting & Export
- **Claim Reports**: Detailed expense claim analysis
- **Daily Activity Reports**: User activity tracking
- **Department-wise Reports**: Department-specific expense analysis
- **Excel Export**: Export data in multiple formats
- **PDF Generation**: Professional report generation

### 🔧 System Administration
- **API Management**: RESTful API configuration and management
- **Database Configuration**: Multi-database support
- **Theme Customization**: Customizable UI themes
- **General Settings**: System-wide configuration
- **Financial Year Management**: Multi-year financial data handling

### 🔌 API Integration
- **Field Mapping**: Custom API field mapping
- **Table Management**: Dynamic table and column management
- **Data Synchronization**: Real-time data sync capabilities

## 🛠 Technologies Used

### Backend
- **PHP 8.2+** - Modern PHP with enhanced performance
- **Laravel 12** - Latest Laravel framework with cutting-edge features
- **MySQL/MariaDB** - Robust relational database
- **Redis** - Caching and session management

### Frontend
- **Bootstrap 5** - Responsive UI framework
- **jQuery** - JavaScript library for DOM manipulation
- **ApexCharts** - Interactive charting library
- **DataTables** - Advanced table functionality
- **SweetAlert2** - Beautiful alert dialogs

### Development Tools
- **Laravel Debugbar** - Development debugging
- **Laravel Pint** - PHP code style fixer
- **PHPUnit** - Unit testing framework
- **Laravel Sail** - Docker development environment

### Key Libraries
- **Spatie Laravel Permission** - Role and permission management
- **Spatie Laravel Activity Log** - Comprehensive activity logging
- **Maatwebsite Excel** - Excel import/export functionality
- **Laravel DomPDF** - PDF generation

## 🚀 Installation

### Prerequisites
- PHP 8.2 or higher
- Composer 2.0 or higher
- MySQL 8.0 or MariaDB 10.5 or higher

### Step 1: Clone the Repository
```bash
git clone https://github.com/devvspl/Xeasy_laravel.git
cd Xeasy_laravel
```

### Step 2: Install Dependencies
```bash
composer install
npm install
```

### Step 3: Environment Configuration
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` file with your database and application settings:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=Xeasy_laravel
DB_USERNAME=your_username
DB_PASSWORD=your_password

```

### Step 4: Database Setup
```bash
php artisan migrate
php artisan db:seed
```

### Step 5: Asset Compilation
```bash
npm run dev
# or for production
npm run build
```

### Step 6: Storage Setup
```bash
php artisan storage:link
```

### Step 7: Start the Application
```bash
php artisan serve
```

Visit `http://localhost:8000` to access the application.

## 📖 Usage

### Initial Setup
1. **Login**: Access the system with default admin credentials
2. **Company Configuration**: Set up your company details and database connections
3. **Financial Year**: Configure the current financial year
4. **User Management**: Create users and assign appropriate roles
5. **Permission Setup**: Configure permissions and permission groups

### Daily Operations
1. **Expense Submission**: Employees submit expense claims with supporting documents
2. **Approval Process**: Managers review and approve claims based on company policies
3. **Finance Processing**: Finance team processes approved claims for reimbursement
4. **Reporting**: Generate reports for analysis and compliance

### Advanced Features
- **API Integration**: Configure external system integrations
- **Custom Reports**: Create custom report templates
- **Theme Customization**: Customize the application appearance
- **Data Export**: Export data in various formats for external analysis

## 🤝 Contributing

We welcome contributions from the community! Please follow these guidelines:

### Development Setup
1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Make your changes and add tests
4. Run tests: `php artisan test`
5. Commit your changes: `git commit -m 'Add amazing feature'`
6. Push to the branch: `git push origin feature/amazing-feature`
7. Open a Pull Request

### Code Standards
- Follow PSR-12 coding standards
- Write meaningful commit messages
- Add tests for new features
- Update documentation as needed
- Ensure all tests pass before submitting

### Reporting Issues
- Use the GitHub issue tracker
- Provide detailed reproduction steps
- Include error logs and screenshots
- Specify your environment details

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

The MIT License is a permissive license that allows for:
- Commercial use
- Modification
- Distribution
- Private use

## 📞 Contact

- **Project Maintainer**: [Your Name](mailto:your.email@example.com)
- **GitHub Issues**: [Report a Bug](https://github.com/your-username/Xeasy_laravel/issues)
- **Documentation**: [Wiki](https://github.com/your-username/Xeasy_laravel/wiki)
- **Support**: [Get Help](mailto:support@Xeasy-laravel.com)

## 🙏 Acknowledgements

- **Laravel Team** - For the amazing framework
- **Spatie** - For excellent Laravel packages
- **Bootstrap Team** - For the responsive UI framework
- **Open Source Community** - For the incredible tools and libraries

---

## 💡 Getting Started

Ready to streamline your expense management? Xeasy Laravel provides everything you need to modernize your expense processes. Start with the installation guide above, and you'll be up and running in minutes!

**Happy coding! 🚀**

*Built with ❤️ using Laravel and modern web technologies.*
