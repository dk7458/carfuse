<?php
/**
 * File: filestorage.php
 * Purpose: Configuration file for the FileStorage service in DocumentManager.
 * Path: DocumentManager/config/filestorage.php
 *
 * Changelog:
 * - [2025-01-28] Initial creation of the file.
 */

return [
    // General file storage settings
    'base_directory' => '/path/to/filestorage', // Base directory for storing files

    // Storage structure
    'directories' => [
        'templates' => 'Templates', // Directory for storing document templates
        'users' => 'Users', // Directory for user-specific documents
        'logs' => '../logs', // Directory for storing logs (relative to base)
    ],

    // File security settings
    'security' => [
        'allowed_extensions' => ['jpg', 'png', 'pdf', 'docx'], // Allowed file extensions
        'max_file_size' => 10485760, // Max file size (10 MB in bytes)
        'encryption' => [
            'enabled' => true, // Enable encryption for stored files
        ],
        'permissions' => [
            'default' => 0640, // Default file permissions (read/write for owner, read for group)
        ],
    ],

    // Temporary storage settings
    'temporary' => [
        'enabled' => true, // Enable temporary storage
        'directory' => __DIR__ . '/../Storage/Temp', // Temp directory path
        'cleanup_interval' => 86400, // Time in seconds to clean up old temp files (24 hours)
    ],

    // Error handling and logging
    'error_handling' => [
        'log_errors' => true, // Log errors related to file storage
        'log_file' => __DIR__ . '/../logs/filestorage.log', // Log file for file storage errors
    ],
];
