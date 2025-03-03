<?php
use Dotenv\Dotenv;

/**
 * General Application Configuration
 */

// ✅ Ensure `.env` is loaded before accessing app settings
$dotenvPath = __DIR__ . '/../';
if (file_exists($dotenvPath . '.env')) {
    $dotenv = Dotenv::createImmutable($dotenvPath);
    $dotenv->load();
}

// ✅ Return structured app configurations
return [
    'environment' => getenv('APP_ENV') ?: 'production',
    'debug'       => getenv('APP_DEBUG') === 'true',

    'providers' => [
        // LoggingHelper has been removed as it's now deprecated
    ],
];
