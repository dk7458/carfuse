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

use Psr\Log\LoggerInterface;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;

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

// ✅ Standardized Logging Function
if (!function_exists('securityLog')) {
    function securityLog(LoggerInterface $logger, $message, $level = 'info') {
        $logger->log($level, "[Security] $message");
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

// ✅ Secure Session Handling via Laravel's Session Facade
if (!function_exists('startSecureSession')) {
    function startSecureSession() {
        if (!Session::isStarted()) {
            Session::start();
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

// ✅ Replace raw $_SESSION handling with Session facade in session expiry enforcement
if (!function_exists('enforceSessionExpiry')) {
    function enforceSessionExpiry(LoggerInterface $logger) {
        if (!Session::has('last_activity')) {
            Session::put('last_activity', time());
            return;
        }
        if (time() - Session::get('last_activity') > 1800) { // 30 min timeout
            securityLog($logger, 'Session expired due to inactivity', 'info');
            Session::flush();
        }
    }
}

// ✅ Fingerprint-Based Session Integrity Check using Session::put()/get()
if (!function_exists('validateSessionIntegrity')) {
    function validateSessionIntegrity(LoggerInterface $logger) {
        $currentIp = hash('sha256', $_SERVER['REMOTE_ADDR']);
        $currentAgent = hash('sha256', $_SERVER['HTTP_USER_AGENT']);
        
        if (!Session::has('client_ip')) {
            Session::put('client_ip', $currentIp);
            Session::put('user_agent', $currentAgent);
            return true;
        }
        if (Session::get('client_ip') !== $currentIp || Session::get('user_agent') !== $currentAgent) {
            securityLog($logger, 'Session integrity check failed: Mismatch detected', 'warning');
            Session::flush();
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
        $cleanedData = trim((string) $data); // ✅ Cast to string before trim()
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

// ✅ Secure Session Destruction using Session::flush()
if (!function_exists('destroySession')) {
    function destroySession(LoggerInterface $logger) {
        securityLog($logger, 'Destroying session', 'info');
        Session::flush();
        securityLog($logger, 'Session destroyed successfully', 'info');
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
    return Session::get($key);
}

/**
 * Set session data safely.
 */
function setSessionData($key, $value)
{
    Session::put($key, $value);
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

// ✅ Enforce API Authentication using requireAuth()
if (!function_exists('requireAuth')) {
    function requireAuth($allowGuest = false) {
        if (Auth::check()) {
            return Auth::user();
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

// Initialize secure session when the file is included
if (!startSecureSession()) {
    securityLog('Critical: Failed to initialize secure session', 'critical');
}
?>
