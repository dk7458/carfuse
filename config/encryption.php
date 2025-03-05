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

    // ✅ Create a function to retrieve env values with validation
    function getRequiredEnv(array $env, string $key, string $fallback) {
        $value = $env[$key] ?? getenv($key) ?: $fallback;
        
        // Ensure key is at least 32 characters for security keys
        if ((strpos($key, 'JWT_') === 0 || $key === 'ENCRYPTION_KEY') && strlen($value) < 32) {
            throw new Exception("$key must be at least 32 characters long.");
        }
        
        return $value;
    }

    // ✅ Build full configuration array with all required values
    $config = [
        // Security keys - all guaranteed to be at least 32 chars
        'jwt_secret' => getRequiredEnv($env, 'JWT_SECRET', 'e4uererje46ye575e7k5jkEAEAGRHSTEHJaet55utaeeHWHU%HJETEUUTEEuzjhrywstrrsaga'),
        'jwt_refresh_secret' => getRequiredEnv($env, 'JWT_REFRESH_SECRET', '347378%^%R#V#B#RT&I#BR^&BR^#B^#R$RBGBB##GT#GT&#GN#G'),
        'encryption_key' => getRequiredEnv($env, 'ENCRYPTION_KEY', 'bt3rb32t9b7t8B^&b78Rv566cv7ec5D7dc6Vd&^vdrb67v76^58bt*&6bt89n8N8N*7n'),
        
        // JWT configuration
        'issuer' => getRequiredEnv($env, 'JWT_ISSUER', 'carfuse-api'),
        'audience' => getRequiredEnv($env, 'JWT_AUDIENCE', 'carfuse-clients'),
        
        // Encryption settings
        'cipher' => 'AES-256-CBC',
        
        // Key alias for backwards compatibility
        'key' => getRequiredEnv($env, 'ENCRYPTION_KEY', 'bt3rb32t9b7t8B^&b78Rv566cv7ec5D7dc6Vd&^vdrb67v76^58bt*&6bt89n8N8N*7n'),
        
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
