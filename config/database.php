<?php
/**
 * Securely configure database connections using environment variables.
 */

return [
    'app_database' => [
        'driver'   => 'mysql',
        'host'     => $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1',
        'port'     => $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: 3306,
        'database' => $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: 'default_db',
        'username' => $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: 'default_user',
        'password' => $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '',
        'charset'  => $_ENV['DB_CHARSET'] ?? getenv('DB_CHARSET') ?: 'utf8mb4',
        'collation'=> 'utf8mb4_unicode_ci',
        'prefix'   => '',
    ],
    'secure_database' => [
        'driver'   => 'mysql',
        'host'     => $_ENV['SECURE_DB_HOST'] ?? getenv('SECURE_DB_HOST') ?: '127.0.0.1',
        'port'     => $_ENV['SECURE_DB_PORT'] ?? getenv('SECURE_DB_PORT') ?: 3306,
        'database' => $_ENV['SECURE_DB_DATABASE'] ?? getenv('SECURE_DB_DATABASE') ?: 'default_secure_db',
        'username' => $_ENV['SECURE_DB_USERNAME'] ?? getenv('SECURE_DB_USERNAME') ?: 'default_admin',
        'password' => $_ENV['SECURE_DB_PASSWORD'] ?? getenv('SECURE_DB_PASSWORD') ?: '',
        'charset'  => $_ENV['SECURE_DB_CHARSET'] ?? getenv('SECURE_DB_CHARSET') ?: 'utf8mb4',
        'collation'=> 'utf8mb4_unicode_ci',
        'prefix'   => '',
    ],
];
