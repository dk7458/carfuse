<?php

/**
 * Dependencies Index File
 * 
 * This file initializes the dependency container and loads all modular 
 * dependency component files in the correct order.
 */

use DI\ContainerBuilder;
use Psr\Log\LoggerInterface;

// Define a simple logger implementation for bootstrapping
class BootstrapLogger implements LoggerInterface {
    public function emergency($message, array $context = []) { error_log("EMERGENCY: {$message}"); }
    public function alert($message, array $context = []) { error_log("ALERT: {$message}"); }
    public function critical($message, array $context = []) { error_log("CRITICAL: {$message}"); }
    public function error($message, array $context = []) { error_log("ERROR: {$message}"); }
    public function warning($message, array $context = []) { error_log("WARNING: {$message}"); }
    public function notice($message, array $context = []) { error_log("NOTICE: {$message}"); }
    public function info($message, array $context = []) { error_log("INFO: {$message}"); }
    public function debug($message, array $context = []) { error_log("DEBUG: {$message}"); }
    public function log($level, $message, array $context = []) { error_log("{$level}: {$message}"); }
}

// Create bootstrap logger for initialization
$bootstrapLogger = new BootstrapLogger();

// Initialize the container builder
$containerBuilder = new ContainerBuilder();

// Define a basic array to hold our definitions
$definitions = [];

// First, collect all dependency files including logging_dependencies.php
$dependencyFiles = glob(__DIR__ . '/*.php');
if ($dependencyFiles === false) {
    $bootstrapLogger->error("Failed to get dependency files from directory: " . __DIR__);
    $dependencyFiles = [];
}

// Filter out this index.php file
$dependencyFiles = array_filter($dependencyFiles, function($file) {
    return basename($file) !== 'index.php';
});

// Sort files to ensure consistent loading order
sort($dependencyFiles);

// Prioritize logging_dependencies if it exists
$loggingFile = __DIR__ . '/logging_dependencies.php';
if (file_exists($loggingFile)) {
    // Move logging_dependencies to the beginning
    $dependencyFiles = array_diff($dependencyFiles, [$loggingFile]);
    array_unshift($dependencyFiles, $loggingFile);
}

// Load each dependency file and collect definitions
foreach ($dependencyFiles as $file) {
    try {
        $bootstrapLogger->info("Loading dependency file: " . basename($file));
        $fileDefinitions = require $file;
        if (is_array($fileDefinitions)) {
            $definitions = array_merge($definitions, $fileDefinitions);
        } else {
            $bootstrapLogger->warning("Dependency file {$file} did not return an array");
        }
    } catch (Exception $e) {
        $errorMsg = "Error loading dependency file {$file}: " . $e->getMessage();
        $bootstrapLogger->error($errorMsg);
    }
}

// Add all collected definitions to the container
$containerBuilder->addDefinitions($definitions);

// Build the container
try {
    $container = $containerBuilder->build();
    
    // Now that we have the real container, try to get the real logger
    try {
        $logger = $container->get('dependencies_logger');
        $logger->info("DI container successfully built with " . count($definitions) . " definitions");
    } catch (Exception $e) {
        $bootstrapLogger->warning("Could not retrieve dependencies_logger from container: " . $e->getMessage());
    }
    
    return [
        'container' => $container,
        'definitions' => $definitions,
    ];
} catch (Exception $e) {
    $errorMsg = "Failed to build DI container: " . $e->getMessage();
    $bootstrapLogger->error($errorMsg);
    
    // Instead of re-throwing, return a minimal result that includes the error
    return [
        'container' => null,
        'definitions' => $definitions,
        'error' => $e->getMessage()
    ];
}
