<?php

return [
    'retry' => [
        'max_attempts' => 3,
        'delay' => 300, // 5 minutes
    ],
    'priorities' => [
        'high' => 1,
        'medium' => 2,
        'low' => 3,
    ],
    'handlers' => [
        'notification' => \App\Services\Events\NotificationEventHandler::class,
        'cleanup' => \App\Services\Events\CleanupEventHandler::class,
        'data_processing' => \App\Services\Events\DataProcessingEventHandler::class,
    ],
];
