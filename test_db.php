<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// **DEBUG LOGGING**
echo "Attempting to load .env...\n";

// **FORCE LOAD .ENV FILE**
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load(); // Use load() instead of safeLoad()

// **PRINT DEBUG INFO**
echo "DB_HOST: " . getenv('DB_HOST') . "\n";
echo "DB_DATABASE: " . getenv('DB_DATABASE') . "\n";
echo "DB_USERNAME: " . getenv('DB_USERNAME') . "\n";
echo "DB_PASSWORD: " . getenv('DB_PASSWORD') . "\n";
?>
