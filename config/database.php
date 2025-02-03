<?php
/**
 * File: config/database.php
 * Purpose: Securely configure database connections using environment variables.
 */

return [
    'app_database' => [
        'driver'   => 'mysql',
        'host'     => getenv('DB_HOST') ?: '127.0.0.1',
        'port'     => getenv('DB_PORT') ?: 3306,
        'database' => getenv('DB_DATABASE') ?: 'default_db',
        'username' => getenv('DB_USERNAME') ?: 'default_user',
        'password' => getenv('DB_PASSWORD') ?: '',
        'charset'  => getenv('DB_CHARSET') ?: 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix'   => '',
    ],
    'secure_database' => [
        'driver'   => 'mysql',
        'host'     => getenv('SECURE_DB_HOST') ?: '127.0.0.1',
        'port'     => getenv('SECURE_DB_PORT') ?: 3306,
        'database' => getenv('SECURE_DB_DATABASE') ?: 'default_secure_db',
        'username' => getenv('SECURE_DB_USERNAME') ?: 'default_admin',
        'password' => getenv('SECURE_DB_PASSWORD') ?: '',
        'charset'  => getenv('SECURE_DB_CHARSET') ?: 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix'   => '',
    ],
];
