<?php
use Dotenv\Dotenv;

$logFile = __DIR__ . '/../logs/errors.log';

try {
    // ✅ Ensure `.env` is loaded before accessing encryption keys
    $dotenvPath = __DIR__ . '/../';
    if (file_exists($dotenvPath . '.env')) {
        $dotenv = Dotenv::createImmutable($dotenvPath);
        $dotenv->load();
    }

    // ✅ Load Environment Variables (If `.env` Exists)
    if (file_exists(__DIR__ . '/../.env')) {
        $env = parse_ini_file(__DIR__ . '/../.env');
    } else {
        $env = [];
    }

    // Helper function to generate a secure random key if needed
    function generateSecureKey(): string {
        return bin2hex(random_bytes(32)); // 64 character hex string (32 bytes)
    }

    // ✅ Create a function to retrieve env values with validation
    function getEncryptionKey(array $env, string $key) {
        global $logFile;
        $value = $env[$key] ?? getenv($key) ?: null;
        
        // If value is missing, generate a temporary one and log a warning
        if (empty($value)) {
            $value = generateSecureKey();
            $message = "[" . date('Y-m-d H:i:s') . "][WARNING] {$key} not found in environment. " . 
                       "Using a temporary value - THIS IS NOT SECURE FOR PRODUCTION. " .
                       "Please set {$key} in your .env file.";
            error_log($message . "\n", 3, $logFile);
            
            // Also output to stderr during development
            if ($_ENV['APP_ENV'] ?? 'development' !== 'production') {
                error_log($message);
            }
        }
        
        // Validate length for security
        if (strlen($value) < 32) {
            $message = "[" . date('Y-m-d H:i:s') . "][ERROR] {$key} must be at least 32 characters long.";
            error_log($message . "\n", 3, $logFile);
            throw new Exception("{$key} must be at least 32 characters long.");
        }
        
        return $value;
    }

    // ✅ Build full configuration array with all required values
    $config = [
        // Security keys - all guaranteed to be at least 32 chars, with no hardcoded fallbacks
        'jwt_secret' => getEncryptionKey($env, 'JWT_SECRET'),
        'jwt_refresh_secret' => getEncryptionKey($env, 'JWT_REFRESH_SECRET'),
        'encryption_key' => getEncryptionKey($env, 'ENCRYPTION_KEY'),
        
        // JWT configuration - Non-sensitive defaults are still acceptable
        'issuer' => $env['JWT_ISSUER'] ?? getenv('JWT_ISSUER') ?: 'carfuse-api',
        'audience' => $env['JWT_AUDIENCE'] ?? getenv('JWT_AUDIENCE') ?: 'carfuse-clients',
        
        // Encryption settings
        'cipher' => 'AES-256-CBC',
        
        // Key alias for backwards compatibility
        'key' => getEncryptionKey($env, 'ENCRYPTION_KEY'),
        
        // Token expiration settings (in seconds)
        'access_token_ttl' => (int)($env['ACCESS_TOKEN_TTL'] ?? getenv('ACCESS_TOKEN_TTL') ?: 3600),
        'refresh_token_ttl' => (int)($env['REFRESH_TOKEN_TTL'] ?? getenv('REFRESH_TOKEN_TTL') ?: 604800),
    ];

    // ✅ Return Complete Configuration
    return $config;
    
} catch (Exception $e) {
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp][error] Encryption configuration error: " . $e->getMessage() . "\n", 3, $logFile);
    
    // ✅ Prevent Information Leakage
    http_response_code(500);
    exit('Internal Server Error: Configuration issue');
}
?>
