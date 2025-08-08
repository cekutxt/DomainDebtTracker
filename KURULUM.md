# cekutxt Domain Tracking System - Manual Installation

## Installation Steps

### 1. Database Creation
- Log into phpMyAdmin
- Create a new database:

### 2. Import Database Structure
- Export SQL file from an existing installation using phpMyAdmin
- In the new installation, go to the "Import" tab in phpMyAdmin
- Select the exported SQL file and import it

### 3. Configure Database Connection
Open the `config/database.php` file and edit the following information:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'domain_takip');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 4. Upload Files
Upload all files to your web server.

### 5. Access Admin Panel
- Go to `http://yoursite.com/admin/` in your browser
- Default login credentials:
  - Username: `admin`
  - Password: `admin`

### 6. Initial Setup
1. After logging into the admin panel, click "Change Password" to change your password
2. Configure mail settings (optional)
3. Start adding your domain and debt records

## Features
- Domain expiration tracking
- Whois information display
- Customer-based domain tracking
- Simple debt tracking system
- Email notifications (optional)
- Mobile-responsive design

## System Requirements
- PHP 7.0 or higher
- MySQL 5.6 or higher
- Web server (Apache, Nginx, etc.)