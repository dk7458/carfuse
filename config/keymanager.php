<?php

return [
    'key_path' => env('KEY_PATH', '/path/to/keys'),
    'key_secret' => env('KEY_SECRET', 'your-key-secret'),
    'keys' => [
        'default' => 'your-default-key-here',
        'encryption' => getenv('ENCRYPTION_KEY') ?: 'fallback-encryption-key-32chars',
        'jwt_signing' => getenv('JWT_SECRET') ?: 'fallback-jwt-secret-32chars',
    ],
    'key_manager' => env('KEY_MANAGER', 'your-key-manager'),
];
