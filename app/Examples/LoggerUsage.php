<?php

use App\Services\LoggerService;

$logger = new LoggerService();

// Log information
$logger->logInfo('User profile updated', [
    'user_id' => 123,
    'changes' => ['name' => 'New Name', 'email' => 'new@example.com']
]);

// Log warning
$logger->logWarning('High memory usage detected', [
    'memory_usage' => memory_get_usage(true),
    'peak_memory' => memory_get_peak_usage(true)
]);

// Log error with exception
try {
    throw new Exception('Database connection failed');
} catch (Exception $e) {
    $logger->logError('Failed to connect to database', [
        'database' => 'main',
        'host' => 'localhost'
    ], $e);
}

// Log with additional context
$logger->logInfo('Order processed', [
    'order_id' => 'ORD-123',
    'amount' => 99.99,
    'currency' => 'USD',
    'customer_id' => 'CUST-456'
]);
