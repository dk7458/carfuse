# Audit Manager Module

## Overview
The Audit Manager module is responsible for providing a robust, centralized logging and audit trail system for the CarFuse platform. It ensures compliance with regulations by maintaining an immutable record of critical operations across the system.

---

## Features
- **Centralized Logging**: Logs all critical events with metadata such as user ID, booking ID, and timestamps.
- **AES Encryption**: Sensitive data in logs is encrypted using AES-256-CBC.
- **Log Filtering**: Admins can filter logs by user, booking, and date.
- **Access Control**: Logs are accessible only to authorized roles (e.g., admin, audit manager).
- **Log Rotation**: Automatic rotation and retention of logs for efficient storage management.
- **Notifications**: Email alerts for critical events.

---


---

## Configuration
The configuration file (`config/audit.php`) allows you to customize the following settings:
- **Log Storage**: Define the directory, file naming convention, and log rotation settings.
- **Encryption**: Enable AES encryption and set the encryption key.
- **Filters**: Configure log filtering options (by user, booking, and date).
- **Access Control**: Restrict access to logs based on user roles.
- **Notifications**: Configure email notifications for critical events.

---

## Installation
1. Place the `audit_manager/` directory in the root of the project.
2. Configure the settings in `config/audit.php` as per your requirements.
3. Ensure the logs directory (`audit_manager/logs`) is writable by the application.

---

## Usage
### **1. Logging Events**
Use the `AuditService` class to log events. Example:
```php
use AuditManager\AuditService;

$auditService = new AuditService($db, $config);
$auditService->log('User login', ['user_id' => 123, 'ip_address' => '192.168.0.1']);
