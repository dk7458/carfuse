<?php
/**
 * File: audit.php
 * Purpose: Configuration file for the Audit Manager module.
 * Path: audit_manager/config/audit.php
 * 
 * Changelog:
 * - [2025-01-25] Initial creation of the file.
 * - [2025-01-27] Added logging level configuration and encryption details.
 */

return [
    // General settings
    'enabled' => true, // Enable or disable the audit manager

    // Log file storage settings
    'storage' => [
        'directory' => __DIR__ . '/../logs', // Directory where logs will be stored
        'file_prefix' => 'audit_', // Prefix for log files
        'rotation' => [
            'enabled' => true, // Enable log rotation
            'frequency' => 'daily', // Rotate logs daily
            'max_files' => 30, // Keep logs for the last 30 days
        ],
    ],

    // Logging levels
    'log_levels' => [
        'info' => true, // Log informational messages
        'warning' => true, // Log warnings
        'error' => true, // Log errors
    ],

    // Encryption settings
    'encryption' => [
        'enabled' => true, // Enable AES encryption for sensitive log entries
        'key' => 'your-encryption-key-here', // AES encryption key (store securely)
        'cipher' => 'AES-256-CBC', // Cipher method
    ],

    // Filters for accessing logs
    'filters' => [
        'by_user' => true, // Enable filtering logs by user ID
        'by_booking' => true, // Enable filtering logs by booking ID
        'by_date' => true, // Enable filtering logs by date range
    ],

    // Access control
    'access' => [
        'allowed_roles' => ['admin', 'audit_manager'], // Roles allowed to access the logs
    ],

    // Notification settings
    'notifications' => [
        'enabled' => true, // Enable email notifications for critical events
        'email_recipients' => ['admin@example.com'], // Recipients for critical event notifications
    ],
];
