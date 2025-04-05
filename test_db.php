<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// ✅ Force Absolute Path
$dotenvPath = '/home/dorian/carfuse';
$dotenv = Dotenv::createImmutable($dotenvPath);
$dotenv->load();

// ✅ Debug Output
echo "Attempting to load .env from: {$dotenvPath}/.env\n";

echo "DB_HOST: " . ($_ENV['DB_HOST'] ?? 'NOT SET') . "\n";
echo "DB_DATABASE: " . ($_ENV['DB_DATABASE'] ?? 'NOT SET') . "\n";
echo "DB_USERNAME: " . ($_ENV['DB_USERNAME'] ?? 'NOT SET') . "\n";
echo "DB_PASSWORD: " . ($_ENV['DB_PASSWORD'] ?? 'NOT SET') . "\n";
