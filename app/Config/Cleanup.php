<?php

return [
    'payments' => [
        'retention_days' => 90,
        'statuses' => ['failed', 'cancelled'],
        'chunk_size' => 100,
        'backup_before_delete' => true,
    ],
    'logs' => [
        'retention_days' => 30,
        'paths' => [
            storage_path('logs'),
        ],
        'backup_before_delete' => false,
    ],
    'sessions' => [
        'retention_days' => 30,
        'excluded_users' => [1], // e.g., system user
    ],
];
