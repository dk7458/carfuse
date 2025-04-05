<?php
use Dotenv\Dotenv;

$logFile = __DIR__ . '/../logs/errors.log';

try {
    // Ensure .env is loaded
    $dotenvPath = __DIR__ . '/../';
    if (file_exists($dotenvPath . '.env')) {
        $dotenv = Dotenv::createImmutable($dotenvPath);
        $dotenv->load();
    }

    // Load Environment Variables (If .env Exists)
    if (file_exists(__DIR__ . '/../.env')) {
        $env = parse_ini_file(__DIR__ . '/../.env');
    } else {
        $env = [];
    }

    // Helper function to get environment values with defaults
    function getSessionValue(array $env, string $key, $default) {
        global $logFile;
        $value = $env[$key] ?? getenv($key) ?: $default;
        
        // Log if using default value in production
        if ($value === $default && ($env['APP_ENV'] ?? getenv('APP_ENV')) === 'production') {
            $message = "[" . date('Y-m-d H:i:s') . "][WARNING] Using default value for {$key}. " . 
                      "Consider setting {$key} in your .env file for production.";
            error_log($message . "\n", 3, $logFile);
        }
        
        return $value;
    }

    // Build full session configuration array with all required values
    $config = [
        // Session name
        'name' => getSessionValue($env, 'SESSION_NAME', 'carfuse_session'),
        
        // Cookie settings
        'lifetime' => (int)getSessionValue($env, 'SESSION_LIFETIME', 7200), // 2 hours
        'path' => getSessionValue($env, 'SESSION_PATH', '/'),
        'domain' => getSessionValue($env, 'SESSION_DOMAIN', ''),
        
        // Security settings
        'secure' => (bool)getSessionValue($env, 'SESSION_SECURE', true),
        'httponly' => (bool)getSessionValue($env, 'SESSION_HTTPONLY', true),
        'samesite' => getSessionValue($env, 'SESSION_SAMESITE', 'Lax'),
        
        // Advanced settings
        'regenerate_interval' => (int)getSessionValue($env, 'SESSION_REGENERATE_INTERVAL', 1800), // 30 minutes
        'gc_maxlifetime' => (int)getSessionValue($env, 'SESSION_GC_MAXLIFETIME', 7200), // 2 hours
        
        // Storage settings
        'save_path' => getSessionValue($env, 'SESSION_SAVE_PATH', ''),
        
        // Environment indication
        'environment' => getSessionValue($env, 'APP_ENV', 'development'),
    ];

    // Return Complete Configuration
    return $config;
    
} catch (Exception $e) {
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp][error] Session configuration error: " . $e->getMessage() . "\n", 3, $logFile);
    
    // Prevent Information Leakage
    http_response_code(500);
    exit('Internal Server Error: Session configuration issue');
}
?>
