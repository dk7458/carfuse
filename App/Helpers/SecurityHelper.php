<?php
if (defined('SECURITY_HELPER_LOADED')) {
    return;
}
define('SECURITY_HELPER_LOADED', true);

/*
|--------------------------------------------------------------------------
| Security Helper - Centralized Session & Security Functions
|--------------------------------------------------------------------------
| This file handles secure session management, CSRF protection, input sanitization,
| and user session handling to ensure global consistency and security.
|
| Path: App/Helpers/SecurityHelper.php
*/

// Removed: use Illuminate\Support\Facades\Session;
// Removed: use Illuminate\Support\Facades\Auth;
// Removed: use Illuminate\Support\Facades\Log;

// Security Configuration
const SESSION_CONFIG = [
    'use_only_cookies'        => 1,
    'use_strict_mode'         => 1,
    'cookie_httponly'         => 1,
    'cookie_samesite'         => 'Lax',
    'gc_maxlifetime'          => 3600,
    'cookie_lifetime'         => 0,
    'use_trans_sid'           => 0,
    'sid_bits_per_character'  => 6
];

// ✅ Standardized Logging Function
if (!function_exists('securityLog')) {
    function securityLog(LoggerInterface $logger, $message, $level = 'info') {
        // Use logger if available; fall back to error_log
        if ($logger && method_exists($logger, 'log')) {
            $logger->log($level, "[Security] $message");
        } else {
            error_log("[Security][$level] $message");
        }
    }
}

// ...existing code...

if (!function_exists('logAuthEvent')) {
    /**
     * Log authentication events.
     */
    function logAuthEvent($message, $level = 'info') {
        error_log("[Auth][$level] $message");
    }
}

if (!function_exists('logAuthFailure')) {
    // Helper to log authentication failures
    function logAuthFailure($message) {
        error_log("[Auth][error] $message");
    }
}

// ✅ Secure Session Handling using native PHP sessions
if (!function_exists('startSecureSession')) {
    function startSecureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_only_cookies', 1);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_samesite', 'Lax');
            session_start();
        }
        return true;
    }
}

// ...existing code...

if (!function_exists('refreshSession')) {
    /**
     * Refresh session to extend its duration.
     */
    function refreshSession() {
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

// ✅ Replace Laravel session calls with native PHP for session expiry enforcement
if (!function_exists('enforceSessionExpiry')) {
    function enforceSessionExpiry($logger) {
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
            return;
        }
        if (time() - $_SESSION['last_activity'] > 1800) { // 30 min timeout
            securityLog($logger, 'Session expired due to inactivity', 'info');
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                setcookie(session_name(), '', time() - 42000, '/');
            }
            session_destroy();
        }
    }
}

// ✅ Fingerprint-Based Session Integrity Check
if (!function_exists('validateSessionIntegrity')) {
    function validateSessionIntegrity($logger) {
        $currentIp = hash('sha256', $_SERVER['REMOTE_ADDR']);
        $currentAgent = hash('sha256', $_SERVER['HTTP_USER_AGENT']);
        
        if (!isset($_SESSION['client_ip'])) {
            $_SESSION['client_ip'] = $currentIp;
            $_SESSION['user_agent'] = $currentAgent;
            return true;
        }
        if ($_SESSION['client_ip'] !== $currentIp || $_SESSION['user_agent'] !== $currentAgent) {
            securityLog($logger, 'Session integrity check failed: Mismatch detected', 'warning');
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                setcookie(session_name(), '', time() - 42000, '/');
            }
            session_destroy();
            return false;
        }
        return true;
    }
}

// ...existing code...

if (!function_exists('sanitizeInput')) {
    /**
     * Sanitize user input to prevent XSS.
     */
    function sanitizeInput($data)
    {
        if (!isset($data) || $data === null) {
            $data = ''; // ✅ Default to empty string to prevent undefined variable errors
        }
        $cleanedData = trim((string)$data);
        return htmlspecialchars($cleanedData, ENT_QUOTES, 'UTF-8');
    }
}

// ...existing code...

/**
 * Generate secure random string (for password resets, API keys, etc.).
 */
function generateSecureToken($length = 64)
{
    return bin2hex(random_bytes($length / 2));
}

// ✅ Secure Session Destruction using native PHP
if (!function_exists('destroySession')) {
    function destroySession(LoggerInterface $logger) {
        securityLog($logger, 'Destroying session', 'info');
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            setcookie(session_name(), '', time() - 42000, '/');
        }
        session_destroy();
        securityLog($logger, 'Session destroyed successfully', 'info');
    }
}

/**
 * Check if a user is logged in.
 */
function isUserLoggedIn()
{
    return isset($_SESSION['user_id']);
}

/**
 * Get the logged-in user's role.
 */
function getUserRole()
{
    return isset($_SESSION['user_id']) ? ($_SESSION['user_role'] ?? 'guest') : 'guest';
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
    // Replace Laravel's authentication with a native JWT approach or session check.
    // For example, decode using firebase/php-jwt, here we simply check session.
    return $_SESSION['user_id'] ?? null;
}

/**
 * Enforce authentication for protected pages.
 */
function requireUserAuth() {
    return requireAuth();
}

// ✅ Custom Authentication Enforcement
if (!function_exists('requireAuth')) {
    function requireAuth($allowGuest = false) {
        if (isset($_SESSION['user_id'])) {
            return $_SESSION['user_id'];
        }
        if ($allowGuest) {
            return null;
        }
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
}

// ✅ CSRF Token Generation
if (!function_exists('generateCsrfToken')) {
    function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

// Validate CSRF token in POST requests (example usage)
// if ($_SERVER['REQUEST_METHOD'] === 'POST' && 
//     (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? ''))) {
//     die("CSRF validation failed.");
// }

// Initialize secure session when the file is included
if (!startSecureSession()) {
    securityLog(null, 'Critical: Failed to initialize secure session', 'critical');
}
?>
