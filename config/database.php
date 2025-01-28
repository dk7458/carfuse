<?php
/**
 * File: config/database.php
 * Purpose: Configure database connections for the app and secure databases.
 */

return [
    'app_database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'carfuse_app',
        'username' => 'app_user',
        'password' => 'secure_password',
        'charset' => 'utf8mb4',
    ],
    'secure_database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'carfuse_secure',
        'username' => 'secure_user',
        'password' => 'secure_password',
        'charset' => 'utf8mb4',
    ],
];
