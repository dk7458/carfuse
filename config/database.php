<?php
use Dotenv\Dotenv;

// ✅ Ensure `.env` is loaded before accessing database credentials
$dotenvPath = __DIR__ . '/../';
if (file_exists($dotenvPath . '.env')) {
    $dotenv = Dotenv::createImmutable($dotenvPath);
    $dotenv->safeLoad();
}

// ✅ Log database configurations for debugging (ONLY FOR DEVELOPMENT)
getLogger('db')->info("🔄 Database Config Loaded: HOST=" . getenv('DB_HOST'));

// ✅ Return structured database configurations
return [
    'app_database' => [
        'driver'    => getenv('DB_DRIVER') ?: 'mysql',
        'host'      => getenv('DB_HOST') ?: 'localhost',
        'database'  => getenv('DB_DATABASE') ?: '',
        'username'  => getenv('DB_USERNAME') ?: '',
        'password'  => getenv('DB_PASSWORD') ?: '',
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix'    => '',
    ],
    'secure_database' => [
        'driver'    => getenv('SECURE_DB_DRIVER') ?: 'mysql',
        'host'      => getenv('SECURE_DB_HOST') ?: 'localhost',
        'database'  => getenv('SECURE_DB_DATABASE') ?: '',
        'username'  => getenv('SECURE_DB_USERNAME') ?: '',
        'password'  => getenv('SECURE_DB_PASSWORD') ?: '',
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix'    => '',
    ]
];
