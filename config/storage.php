<?php

return [
    'base_directory' => __DIR__ . '/../storage/',
    'storage_path' => env('STORAGE_PATH', 'your-storage-path'),

    'security' => [
        'permissions' => [
            'default' => 0640,  // Default file permissions (owner read/write)
            'directory' => 0755, // Default directory permissions
        ],
        'max_file_size' => 5 * 1024 * 1024, // Max file size (5MB)
        'allowed_extensions' => ['png', 'jpg', 'svg', 'pdf', 'docx'],
    ],

    'encryption' => [
        'enabled' => true,
    ],
];
