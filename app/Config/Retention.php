<?php

return [
    'policies' => [
        'logs' => [
            'retention_days' => 90,
            'require_backup' => true,
            'critical_levels' => ['error', 'critical'],
            'batch_size' => 1000,
        ],
        'payments' => [
            'retention_days' => 365,
            'require_backup' => true,
            'protected_statuses' => ['disputed', 'investigating'],
            'batch_size' => 500,
        ],
        'backups' => [
            'retention_days' => 30,
            'minimum_copies' => 3,
            'critical_types' => ['database', 'user_data'],
            'batch_size' => 100,
        ],
    ],
    'backup_path' => storage_path('retention-backups'),
];
