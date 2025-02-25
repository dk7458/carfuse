<?php

/**
 * Dependencies Index File
 * 
 * This file initializes the dependency container and loads all modular 
 * dependency component files in the correct order.
 */

use DI\ContainerBuilder;

// Initialize the container builder
$containerBuilder = new ContainerBuilder();

// First, manually load the logging dependencies to ensure logger is available
$loggingFile = __DIR__ . '/logging_dependencies.php';
if (file_exists($loggingFile)) {
    $loggingDefinitions = require_once $loggingFile;
    $containerBuilder->addDefinitions($loggingDefinitions);
    
    // Build a temporary container just for the logger
    $tempContainer = $containerBuilder->build();
    $dependenciesLogger = null;
    
    try {
        $dependenciesLogger = $tempContainer->get('dependencies_logger');
    } catch (Exception $e) {
        // If logger initialization fails, create a simple error logger to stderr
        error_log("Warning: Could not initialize dependencies_logger - " . $e->getMessage());
    }
} else {
    error_log("Warning: Logging dependencies file not found at {$loggingFile}");
}

// Get all PHP files in the dependencies directory except index.php and logging_dependencies.php (already loaded)
$dependencyFiles = glob(__DIR__ . '/*.php');
$dependencyFiles = array_filter($dependencyFiles, function($file) {
    $basename = basename($file);
    return $basename !== 'index.php' && $basename !== 'logging_dependencies.php';
});

// Sort files to ensure consistent loading order
sort($dependencyFiles);

// Load each dependency file and collect definitions
$definitions = $loggingDefinitions ?? [];
foreach ($dependencyFiles as $file) {
    try {
        $fileDefinitions = require_once $file;
        if (is_array($fileDefinitions)) {
            $definitions = array_merge($definitions, $fileDefinitions);
        }
        
        if (isset($dependenciesLogger)) {
            $dependenciesLogger->info("Loaded dependency file: " . basename($file));
        }
    } catch (Exception $e) {
        $errorMsg = "Error loading dependency file {$file}: " . $e->getMessage();
        
        if (isset($dependenciesLogger)) {
            $dependenciesLogger->error($errorMsg);
        } else {
            error_log($errorMsg);
        }
    }
}

// Add all collected definitions to the container
$containerBuilder->addDefinitions($definitions);

// Build the container
try {
    $container = $containerBuilder->build();
    
    if (isset($dependenciesLogger)) {
        $dependenciesLogger->info("DI container successfully built with " . count($definitions) . " definitions");
    }
    
    return [
        'container' => $container,
        'definitions' => $definitions,
    ];
} catch (Exception $e) {
    $errorMsg = "Failed to build DI container: " . $e->getMessage();
    
    if (isset($dependenciesLogger)) {
        $dependenciesLogger->error($errorMsg);
    } else {
        error_log($errorMsg);
    }
    
    // Re-throw the exception
    throw $e;
}
