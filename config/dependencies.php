<?php

/**
 * Main Dependencies Configuration File
 * 
 * This file initializes the dependency injection container and loads
 * all modular dependency components.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Define the path to the modular dependency files
$dependenciesDir = __DIR__ . '/dependencies/';

// Check if directory exists, if not, create it
if (!is_dir($dependenciesDir)) {
    // Make migration easier by copying current files if needed
    mkdir($dependenciesDir, 0775, true);
    
    // Define files to migrate to the dependencies directory
    $filesToMigrate = [
        'logging_dependencies.php',
        'database_dependencies.php',
        'service_dependencies.php',
        'controller_dependencies.php',
    ];
    
    foreach ($filesToMigrate as $file) {
        $source = __DIR__ . '/' . $file;
        $destination = $dependenciesDir . $file;
        
        if (file_exists($source) && !file_exists($destination)) {
            copy($source, $destination);
        }
    }
}

// Load the main dependencies index file which handles the loading sequence
$result = require_once $dependenciesDir . 'index.php';

// Return the container and other important registered services
return $result;
