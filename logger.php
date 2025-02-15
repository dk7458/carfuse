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
 $logDir = __DIR__ . '/../logs'; // FIXED PATH TO BE ABSOLUTE
 if (!is_dir($logDir)) {
     mkdir($logDir, 0775, true);
 }
 
 // ✅ Ensure Correct File Permissions
 $logFile = $logDir . '/application.log';
 if (!file_exists($logFile)) {
     touch($logFile);
     chmod($logFile, 0664);
 }
 
 // ✅ Initialize Logger (Monolog)
 try {
     $logger = new Logger('application');
     $streamHandler = new StreamHandler($logFile, Logger::DEBUG);
     $streamHandler->setFormatter(new LineFormatter(null, null, true, true));
     $logger->pushHandler($streamHandler);
 } catch (Exception $e) {
     die("❌ Logger initialization failed: " . $e->getMessage());
 }
 
 // ✅ Return Logger Instance for Application-Wide Use
 return $logger;