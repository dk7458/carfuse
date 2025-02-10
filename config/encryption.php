<?php

$logFile = __DIR__ . '/../logs/errors.log';

try {
    // ✅ Load Environment Variables (If `.env` Exists)
    if (file_exists(__DIR__ . '/../.env')) {
        $env = parse_ini_file(__DIR__ . '/../.env');
    } else {
        $env = [];
    }

    // ✅ Retrieve Secure Keys from `.env` or Use Safe Fallbacks
    $jwtSecret = $env['JWT_SECRET'] ?? getenv('JWT_SECRET') ?: 'default_secure_fallback_key_32_characters_long';
    $jwtRefreshSecret = $env['JWT_REFRESH_SECRET'] ?? getenv('JWT_REFRESH_SECRET') ?: 'your-secure-refresh-jwt-secret';
    $encryptionKey = $env['ENCRYPTION_KEY'] ?? getenv('ENCRYPTION_KEY') ?: 'default_fallback_encryption_key_32+characters_long';

    // ✅ Validate JWT & Encryption Key Lengths
    if (strlen($jwtSecret) < 32) {
        throw new Exception('JWT secret key must be at least 32 characters long.');
    }
    if (strlen($jwtRefreshSecret) < 32) {
        throw new Exception('JWT refresh secret key must be at least 32 characters long.');
    }
    if (strlen($encryptionKey) < 32) {
        throw new Exception('Encryption key must be at least 32 characters long.');
    }

    // ✅ Return Secure Configuration
    return [
        'encryption_key' => $encryptionKey,
        'cipher' => 'AES-256-CBC',
        'jwt_secret' => $jwtSecret,
        'jwt_refresh_secret' => $jwtRefreshSecret,
    ];
} catch (Exception $e) {
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp][error] Encryption configuration error: " . $e->getMessage() . "\n", 3, $logFile);
    
    // ✅ Prevent Information Leakage
    http_response_code(500);
    exit('Internal Server Error');
}
?>
