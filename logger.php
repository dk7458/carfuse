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
 
 // ✅ Define Log Directory
 $logDir = __DIR__ . '/../logs';
 if (!is_dir($logDir)) {
     if (!mkdir($logDir, 0775, true)) {
         error_log("❌ [LOGGER] Failed to create logs directory: {$logDir}");
         die("❌ Logger initialization failed: Could not create log directory.\n");
     }
 }
 
 // ✅ Log Categories (Separated Logs for Different Services)
 $logFiles = [
     'application' => 'application.log',
     'auth'        => 'auth.log',
     'db'          => 'db.log',
     'api'         => 'api.log',
     'security'    => 'security.log',
     'system'      => 'system.log'
 ];
 
 $loggers = [];
 
 foreach ($logFiles as $category => $fileName) {
     $logFile = "{$logDir}/{$fileName}";
 
     if (!file_exists($logFile)) {
         if (!touch($logFile)) {
             error_log("❌ [LOGGER] Failed to create log file: {$logFile}");
             continue;
         }
         chmod($logFile, 0664);
     }
 
     try {
         $logger = new Logger($category);
         $streamHandler = new StreamHandler($logFile, Logger::DEBUG);
 
         // ✅ JSON Formatting for Structured Logs
         $formatter = new LineFormatter(
             json_encode([
                 'timestamp' => '%datetime%',
                 'level'     => '%level_name%',
                 'message'   => '%message%',
                 'context'   => '%context%'
             ]) . PHP_EOL,
             null,
             true,
             true
         );
         $streamHandler->setFormatter($formatter);
         $logger->pushHandler($streamHandler);
 
         $loggers[$category] = $logger;
     } catch (Exception $e) {
         error_log("❌ [LOGGER] Failed to initialize logger for {$category}: " . $e->getMessage());
     }
 }
 
 // ✅ Function to Retrieve Logger by Category
 function getLogger($category = 'application')
 {
     global $loggers;
     if (!isset($loggers[$category])) {
         error_log("❌ [LOGGER] Logger for category '{$category}' not initialized.");
         return new Logger('fallback');
     }
     return $loggers[$category];
 }
 
 // ✅ Return Default Logger
 return getLogger('application');
 
