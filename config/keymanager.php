<?php

return [
    'keys' => [
        'default' => 'your-default-key-here',
        'encryption' => getenv('ENCRYPTION_KEY') ?: 'fallback-encryption-key-32chars',
        'jwt_signing' => getenv('JWT_SECRET') ?: 'fallback-jwt-secret-32chars',
    ],
    'key_manager' => env('KEY_MANAGER', 'your-key-manager'),
];
