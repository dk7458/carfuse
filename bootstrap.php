<?php

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Load dependencies
$dependencies = require __DIR__ . '/config/dependencies.php';

// Return the dependencies array for use across the application
return $dependencies;
