<?php
return [
    'host' => $_ENV['SECURE_DB_HOST'] ?? '127.0.0.1',
    'port' => $_ENV['SECURE_DB_PORT'] ?? '3306',
    'database' => $_ENV['SECURE_DB_NAME'] ?? 'secure_database',
    'username' => $_ENV['SECURE_DB_USER'] ?? 'secure_user',
    'password' => $_ENV['SECURE_DB_PASSWORD'] ?? 'secure_password',
    'charset' => 'utf8mb4',
];
