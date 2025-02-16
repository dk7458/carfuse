<?php
use Dotenv\Dotenv;

// Ensure `.env` is loaded before accessing database credentials
$dotenvPath = __DIR__ . '/../';
if (file_exists($dotenvPath . '.env')) {
    $dotenv = Dotenv::createImmutable($dotenvPath);
    $dotenv->safeLoad();
}
// This file must return an array of database configurations
return [
    'app_database' => [
        'driver'    => 'mysql',
        'host'      => getenv('DB_HOST'),
        'database'  => getenv('DB_DATABASE'),
        'username'  => getenv('DB_USERNAME'),
        'password'  => getenv('DB_PASSWORD'),
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
