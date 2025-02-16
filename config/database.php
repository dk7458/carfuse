<?php
use Dotenv\Dotenv;

// Ensure `.env` is loaded before accessing database credentials
$dotenvPath = __DIR__ . '/../';
if (file_exists($dotenvPath . '.env')) {
    $dotenv = Dotenv::createImmutable($dotenvPath);
    $dotenv->safeLoad();
}
var_dump(getenv('DB_HOST'));

// This file must return an array of database configurations
return [
    'app_database' => [
        'driver'    => 'mysql',
        'host'      => $_ENV['DB_HOST'] ?? '',
        'database'  => $_ENV['DB_DATABASE'] ?? '',
        'username'  => $_ENV['DB_USERNAME'] ?? '',
        'password'  => $_ENV['DB_PASSWORD'] ?? '',
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix'    => '',
    ],
    'secure_database' => [
        'driver'    => 'mysql',
        'host'      => getenv('SECURE_DB_HOST'),
        'database'  => getenv('SECURE_DB_DATABASE'),
        'username'  => getenv('SECURE_DB_USERNAME'),
        'password'  => getenv('SECURE_DB_PASSWORD'),
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix'    => '',
    ]
];
