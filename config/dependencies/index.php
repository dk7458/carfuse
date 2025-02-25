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

// Get all PHP files in the dependencies directory except index.php
$dependencyFiles = glob(__DIR__ . '/*.php');
$dependencyFiles = array_filter($dependencyFiles, function($file) {
    return basename($file) !== 'index.php';
});

// Sort files to ensure consistent loading order
sort($dependencyFiles);

// Load each dependency file and collect definitions
$definitions = [];
foreach ($dependencyFiles as $file) {
    $fileDefinitions = require_once $file;
    if (is_array($fileDefinitions)) {
        $definitions = array_merge($definitions, $fileDefinitions);
    }
}

// Add all collected definitions to the container
$containerBuilder->addDefinitions($definitions);

// Build the container
$container = $containerBuilder->build();

return [
    'container' => $container,
    'definitions' => $definitions,
];
