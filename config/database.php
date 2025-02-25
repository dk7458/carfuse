<?php
use Dotenv\Dotenv;

// ✅ Ensure `.env` is loaded before accessing database credentials
$dotenvPath = __DIR__ . '/../';
if (file_exists($dotenvPath . '.env')) {
    $dotenv = Dotenv::createImmutable($dotenvPath);
    $dotenv->load();
}

// ✅ Return structured database configurations
return [
    'app_database' => [
        'driver'    => 'mysql',
        'host'      => 'localhost',
        'database'  => 'sdifbhdsi',
        'username'  => 'app_user',
        'password'  => 'app_password',
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix'    => '',
    ],
    'secure_database' => [
        'driver'    => 'mysql',
        'host'      => 'localhost',
        'database'  => 'secure_database',
        'username'  => 'secure_user',
        'password'  => 'secure_password',
        'charset'   => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix'    => '',
    ]
];
