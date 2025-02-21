<?php

use Dotenv\Dotenv;

// ✅ Ensure `.env` is loaded before accessing database credentials
$dotenvPath = __DIR__ . '/../';
if (file_exists($dotenvPath . '.env')) {
    $dotenv = Dotenv::createImmutable($dotenvPath);
    $dotenv->safeLoad();
}

// ✅ DEBUG LOGGING (Optional - Remove in Production)
if (!isset($_ENV['DB_HOST']) || empty($_ENV['DB_HOST'])) {
    file_put_contents(__DIR__ . '/../logs/debug.log', "[DB CONFIG] ❌ ERROR: .env not loaded or missing DB_HOST\n", FILE_APPEND);
    die("❌ ERROR: Database environment variables not set.");
}

// ✅ Return database configurations
return [
    'app_database' => [
        'driver'    => $_ENV['DB_DRIVER'] ?? 'mysql',
        'host'      => $_ENV['DB_HOST'] ?? 'localhost',
        'database'  => $_ENV['DB_DATABASE'] ?? '',
        'username'  => $_ENV['DB_USERNAME'] ?? '',
        'password'  => $_ENV['DB_PASSWORD'] ?? '',
        'charset'   => $_ENV['DB_CHARSET'] ?? 'utf8mb4',
        'collation' => $_ENV['DB_COLLATION'] ?? 'utf8mb4_unicode_ci',
        'prefix'    => '',
    ],
    'secure_database' => [
        'driver'    => $_ENV['SECURE_DB_DRIVER'] ?? 'mysql',
        'host'      => $_ENV['SECURE_DB_HOST'] ?? 'localhost',
        'database'  => $_ENV['SECURE_DB_DATABASE'] ?? '',
        'username'  => $_ENV['SECURE_DB_USERNAME'] ?? '',
        'password'  => $_ENV['SECURE_DB_PASSWORD'] ?? '',
        'charset'   => $_ENV['SECURE_DB_CHARSET'] ?? 'utf8mb4',
        'collation' => $_ENV['SECURE_DB_COLLATION'] ?? 'utf8mb4_unicode_ci',
        'prefix'    => '',
    ]
];
