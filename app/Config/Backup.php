<?php

return [
    'storage' => [
        'local_path' => storage_path('app/backups'),
        'cloud' => [
            'path' => 'backups',
            'provider' => 's3',  // or any other cloud disk configured in filesystems.php
        ],
    ],
    'database' => [
        'validate_checksum' => true,
        'retention_days' => 14,
    ],
    'files' => [
        'paths' => [
            public_path(),
            storage_path('app'),
        ],
        'exclude' => [
            storage_path('logs'),
        ],
        'retention_days' => 14,
    ],
];
