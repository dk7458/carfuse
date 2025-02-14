<?php
/*
|--------------------------------------------------------------------------
| Security Helper - Centralized Session & Security Functions
|--------------------------------------------------------------------------
| This file handles secure session management, CSRF protection, input sanitization,
| and user session handling to ensure global consistency and security.
|
| Path: App/Helpers/SecurityHelper.php
*/

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

// Security Configuration
const SESSION_CONFIG = [
    'use_only_cookies' => 1,
    'use_strict_mode' => 1,
    'cookie_httponly' => 1,
    'cookie_samesite' => 'Lax',
    'gc_maxlifetime' => 3600,
    'cookie_lifetime' => 0,
    'use_trans_sid' => 0,
    'sid_bits_per_character' => 6
];

// Enhanced logging with severity levels
if (!function_exists('securityLog')) {
    function securityLog($message, $level = 'info') {
        $logFile = __DIR__ . '/../../logs/security.log';
        $timestamp = date('Y-m-d H:i:s');
        $userId = $_SESSION['user_id'] ?? 'guest';
        
        // Sanitize sensitive data
        $patterns = [
            '/user_id[\s]?[=:][\s]?["\']?\w+["\']?/i',
            '/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}/',
            '/Mozilla\/[^\s]+/'
        ];
        $message = preg_replace($patterns, '[REDACTED]', $message);
        $sanitizedMessage = str_replace(["\n", "\r"], '', $message);
        
        error_log("[$timestamp][$level][user_id: $userId] $sanitizedMessage\n", 3, $logFile);
    }
}

// ...existing code...

if (!function_exists('logAuthEvent')) {
    /**
     * Log authentication events.
     */
    function logAuthEvent($message, $level = 'info') {
        Log::channel('auth')->info($message);
    }
}

if (!function_exists('logAuthFailure')) {
    // Helper to log authentication failures to auth.log
    function logAuthFailure($message) {
        Log::channel('auth')->error($message);
    }
}

if (!function_exists('startSecureSession')) {
    // Replace startSecureSession() to use native PHP sessions instead of Laravel's Session facade.
    function startSecureSession() {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['initiated'] = time();
        $_SESSION['client_ip'] = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '');
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['last_activity'] = time();
        $_SESSION['guest'] = true;
        error_log('New guest session initiated.');
        return true;
    }
}

// ...existing code...

if (!function_exists('refreshSession')) {
    /**
     * Refresh session to extend its duration.
     */
    function refreshSession() {
        // Changed to security.log
        $logFile = __DIR__ . '/../../logs/security.log';
        $timestamp = date('Y-m-d H:i:s');
    
        try {
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['last_activity'] = time();
                session_regenerate_id(true);
                error_log("[$timestamp][info] Session refreshed\n", 3, $logFile);
            }
        } catch (Exception $e) {
            error_log("[$timestamp][error] Session refresh failed: " . $e->getMessage() . "\n", 3, $logFile);
        }
    }
}

// ...existing code...

if (!function_exists('validateSessionIntegrity')) {
    function validateSessionIntegrity() {
        if (!isset($_SESSION['initiated'])) {
            securityLog('Session integrity check failed: not initiated', 'warning');
            return false;
        }
    
        $currentIp = hash('sha256', $_SERVER['REMOTE_ADDR']);
        $currentAgent = hash('sha256', $_SERVER['HTTP_USER_AGENT']);
        
        // Flexible validation for guest sessions
        if (isset($_SESSION['user_id'])) {
            // Strict validation for authenticated users
            if ($_SESSION['client_ip'] !== $currentIp || 
                $_SESSION['user_agent'] !== $currentAgent) {
                securityLog('Session integrity check failed: authenticated user mismatch', 'warning');
                destroySession();
                return false;
            }
        } else {
            // Update fingerprint for guest sessions
            $_SESSION['client_ip'] = $currentIp;
            $_SESSION['user_agent'] = $currentAgent;
            $_SESSION['guest'] = true;
        }
    
        // Check for session timeout (30 minutes)
        if (time() - $_SESSION['last_activity'] > 1800) {
            securityLog('Session expired due to inactivity', 'info');
            destroySession();
            return false;
        }
    
        // Refresh session if about to expire (within 5 minutes)
        if (time() - $_SESSION['last_activity'] > 1500) {
            refreshSession();
        }
    
        $_SESSION['last_activity'] = time();
        return true;
    }
}

// ...existing code...

// Remove manual CSRF token functions; use Laravel's CSRF middleware and Blade @csrf directives.
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate secure random string (for password resets, API keys, etc.).
 */
function generateSecureToken($length = 64)
{
    return bin2hex(random_bytes($length / 2));
}

function destroySession() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return false;
    }

    try {
        $wasGuest = $_SESSION['guest'] ?? false;
        securityLog('Initiating session destruction: ' . ($wasGuest ? 'guest' : 'user') . ' session');
        
        // Clear session data
        $_SESSION = [];
        
        // Delete session cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'],
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => 'Lax'
            ]);
        }
        
        if (!session_destroy()) {
            throw new Exception('Session destruction failed');
        }
        
        securityLog('Session destroyed successfully');
        return true;
    } catch (Exception $e) {
        securityLog('Session destruction error: ' . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Check if a user is logged in.
 */
function isUserLoggedIn()
{
    return Auth::check();
}

/**
 * Get the logged-in user's role.
 */
function getUserRole()
{
    return Auth::check() ? Auth::user()->role : 'guest';
}

/**
 * Get session data safely.
 */
function getSessionData($key)
{
    return $_SESSION[$key] ?? null;
}

/**
 * Set session data safely.
 */
function setSessionData($key, $value)
{
    $_SESSION[$key] = $value;
}

/**
 * Validate JWT token.
 */
function validateJWT($token) {
    // Instead of manual decoding, we delegate to Laravel's 'api' guard.
    return Auth::guard('api')->user();
}

/**
 * Enforce authentication for protected pages.
 */
function requireUserAuth() {
    requireAuth();
}

// New function to enforce authentication dynamically
function requireAuth($allowGuest = false) {
    // Get HTTP headers if available
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $authHeader = $headers['Authorization'] ?? '';

    if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
        // API authentication using JWT Bearer
        $config = require __DIR__ . '/../../config/encryption.php';
        $jwtSecret = $config['jwt_secret'] ?? '';
        $token = substr($authHeader, 7);
        try {
            return (array) Firebase\JWT\JWT::decode($token, new Firebase\JWT\Key($jwtSecret, 'HS256'));
        } catch (Exception $e) {
            error_log("[AUTH] API authentication failure: " . $e->getMessage() . "\n", 3, __DIR__ . '/../../logs/auth.log');
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
    } else {
        // Web authentication using session
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (isset($_SESSION['user_id'])) {
            return $_SESSION['user_id'];
        } elseif ($allowGuest) {
            return null;
        } else {
            error_log("[AUTH] Web authentication failure: No session user_id\n", 3, __DIR__ . '/../../logs/auth.log');
            // If the request expects JSON, return JSON response
            if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
            } else {
                http_response_code(401);
                echo 'Unauthorized';
            }
            exit;
        }
    }
}

// Initialize secure session when the file is included
if (!startSecureSession()) {
    securityLog('Critical: Failed to initialize secure session', 'critical');
}
?>
