<?php
/**
 * File: audit.php
 * Purpose: Configuration file for the Audit Manager module.
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

    // Logging levels configuration
    'log_levels' => [
        'debug' => false,  // Debug messages are disabled in production
        'info' => true,    // Log informational messages
        'warning' => true, // Log warnings
        'error' => true,   // Log errors
        'critical' => true, // Log critical system events
    ],

    // Database settings
    'database' => [
        'table' => 'audit_logs',
        'batch_size' => 1000,      // Size for batch operations
        'max_export_rows' => 10000 // Max rows for export operation
    ],

    // Encryption settings
    'encryption' => [
        'enabled' => true, // Enable AES encryption for sensitive log entries
        'key' => $_ENV['ENCRYPTION_KEY'] ?? 'your-encryption-key-here', // AES encryption key
        'cipher' => 'AES-256-CBC', // Cipher method
    ],

    // Filters for accessing logs
    'filters' => [
        'by_user' => true,    // Enable filtering logs by user ID
        'by_booking' => true, // Enable filtering logs by booking ID
        'by_date' => true,    // Enable filtering logs by date range
        'by_level' => true,   // Enable filtering by log level
        'by_category' => true // Enable filtering by category
    ],

    // Access control
    'access' => [
        'allowed_roles' => ['admin', 'audit_manager', 'security_admin'], // Roles allowed to access logs
    ],

    // Service configuration
    'services' => [
        'fraud_detection' => [
            'enabled' => true,
            'threshold_score' => 80, // Fraud score threshold
            'notify_on_suspicion' => true
        ]
    ],
    
    // Notification settings
    'notifications' => [
        'enabled' => true, // Enable email notifications for critical events
        'email_recipients' => explode(',', $_ENV['AUDIT_NOTIFICATION_EMAILS'] ?? 'admin@example.com'),
    ],
];
