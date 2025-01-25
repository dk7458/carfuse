<?php

use App\Services\TimedEventService;

$eventService = new TimedEventService();

// Execute a single notification event
$notificationEvent = [
    'id' => 1,
    'name' => 'Daily User Report',
    'type' => 'notification',
    'priority' => 1,
    'data' => [
        'recipients' => ['admin@example.com'],
        'template' => 'daily_report'
    ]
];

try {
    if ($eventService->executeEvent($notificationEvent)) {
        echo "Notification event executed successfully\n";
    }
} catch (Exception $e) {
    echo "Event execution failed: " . $e->getMessage() . "\n";
}

// Execute all pending events
try {
    $stats = $eventService->executeAllEvents();
    echo "Batch execution completed:\n";
    echo "Success: {$stats['success']}\n";
    echo "Failed: {$stats['failed']}\n";
    echo "Skipped: {$stats['skipped']}\n";
} catch (Exception $e) {
    echo "Batch execution failed: " . $e->getMessage() . "\n";
}

// Register a custom event handler
$eventService->registerHandler('custom_type', new CustomEventHandler());
