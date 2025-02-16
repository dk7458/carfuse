<?php

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
