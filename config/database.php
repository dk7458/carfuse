<?php
/**
 * Securely configure database connections without .env
 * This file is committed to Git and used directly.
 */

return [
    'app_database' => [
        'driver'   => 'mysql',
        'host'     => 'srv1803.hstgr.io',
        'port'     => 3306,
        'database' => 'u122931475_carfuse',
        'username' => 'u122931475_user',
        'password' => '59&:NJ9a@',
        'charset'  => 'utf8mb4',
        'collation'=> 'utf8mb4_unicode_ci',
        'prefix'   => '',
    ],
    'secure_database' => [
        'driver'   => 'mysql',
        'host'     => 'srv1803.hstgr.io',
        'port'     => 3306,
        'database' => 'u122931475_secure',
        'username' => 'u122931475_admin',
        'password' => '&hNAA*4a8Jx$',
        'charset'  => 'utf8mb4',
        'collation'=> 'utf8mb4_unicode_ci',
        'prefix'   => '',
    ],
];
