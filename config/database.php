<?php
/**
 * File: config/database.php
 * Purpose: Configure database connections for the app and secure databases.
 */

return [
    'app_database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'u122931475_carfuse',
        'username' => 'u122931475_user',
        'password' => 'Japierdole1876',
        'charset' => 'utf8mb4',
    ],
    'secure_database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'database' => 'u122931475_secure',
        'username' => 'u122931475_admin',
        'password' => 'Japierdole1876',
        'charset' => 'utf8mb4',
    ],
];
