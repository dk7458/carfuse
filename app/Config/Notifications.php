<?php

return [
    'templates_path' => resource_path('views/notifications'),
    'retry' => [
        'max_attempts' => 3,
        'interval' => 60, // seconds between retries
    ],
    'batch' => [
        'size' => 50,
        'delay' => 2, // seconds
    ],
];
