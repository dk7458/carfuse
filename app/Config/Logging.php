<?php

return [
    'default' => env('LOG_CHANNEL', 'stack'),
    
    'channels' => [
        'file' => [
            'driver' => 'daily',
            'path' => storage_path('logs/app.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
            'max_size' => 50000, // KB
        ],
        'database' => [
            'driver' => 'database',
            'table' => 'system_logs',
            'connection' => null,
        ],
        'sentry' => [
            'driver' => 'sentry',
            'dsn' => env('SENTRY_DSN'),
            'level' => env('LOG_LEVEL', 'error'),
        ],
        'datadog' => [
            'driver' => 'datadog',
            'api_key' => env('DATADOG_API_KEY'),
            'app_key' => env('DATADOG_APP_KEY'),
        ],
    ],
    
    'levels' => [
        'debug' => 100,
        'info' => 200,
        'notice' => 250,
        'warning' => 300,
        'error' => 400,
        'critical' => 500,
        'alert' => 550,
        'emergency' => 600,
    ],
];
