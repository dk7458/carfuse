<?php

/**
 * Logger Configuration
 *
 * This file initializes Monolog as the application-wide logger,
 * ensuring all services use a single logging instance.
 */

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Psr\Log\LoggerInterface;

// ✅ Ensure Log Directory Exists
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0775, true);
}

// ✅ Initialize Logger (Monolog)
try {
    $logger = new Logger('application');
    $streamHandler = new StreamHandler($logDir . '/application.log', Logger::DEBUG);
    $streamHandler->setFormatter(new LineFormatter(null, null, true, true));
    $logger->pushHandler($streamHandler);
} catch (Exception $e) {
    die("❌ Logger initialization failed: " . $e->getMessage());
}

// ✅ Ensure Logger Implements LoggerInterface
if (!$logger instanceof LoggerInterface) {
    die("❌ Logger must be an instance of LoggerInterface.\n");
}

// ✅ Return Logger Instance for Application-Wide Use
return $logger;
