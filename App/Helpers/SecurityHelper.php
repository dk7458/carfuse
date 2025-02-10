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

/**
 * Log security events consistently.
 */
function logSecurityEvent($message, $level = 'info') {
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

/**
 * Log authentication events.
 */
function logAuthEvent($message, $level = 'info') {
    // Changed to security.log
    $logFile = __DIR__ . '/../../logs/security.log';
    $timestamp = date('Y-m-d H:i:s');
    $userId = $_SESSION['user_id'] ?? 'guest';

    error_log("[$timestamp][$level][user_id: $userId] $message\n", 3, $logFile);
}

// Helper to log authentication failures to auth.log
function logAuthFailure($message) {
    $logFile = __DIR__ . '/../../logs/auth.log';
    $timestamp = date('Y-m-d H:i:s');
    error_log("[$timestamp][auth_failure] $message\n", 3, $logFile);
}

// Improved startSecureSession to support API requests via JWT
function startSecureSession() {
    // If API request and JWT provided, attempt JWT validation and setup session
    if (defined('API_ENTRY')) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            $decoded = validateJWT($token);
            if ($decoded !== false) {
                if (session_status() !== PHP_SESSION_ACTIVE) {
                    session_start();
                }
                $_SESSION['api_authenticated'] = true;
                $_SESSION['user_id'] = $decoded->sub ?? 'api_user';
                // Optionally store more token data in session
            }
        }
    }

    // Proceed with standard session initialization if not active
    if (session_status() === PHP_SESSION_ACTIVE) {
        return true;
    }

    if (headers_sent()) {
        logSecurityEvent('Headers already sent, cannot modify session settings', 'warning');
    } else {
        foreach (SESSION_CONFIG as $key => $value) {
            @ini_set("session.$key", $value);
        }

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'] ?? '',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    try {
        if (!session_start()) { // will only be called if not already active
            throw new Exception('Session start failed');
        }

        // Initialize session only once
        if (empty($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = time();
            $_SESSION['client_ip'] = hash('sha256', $_SERVER['REMOTE_ADDR']);
            $_SESSION['user_agent'] = hash('sha256', $_SERVER['HTTP_USER_AGENT']);
            $_SESSION['last_activity'] = time();
            $_SESSION['guest'] = true; // Default guest initialization
            logSecurityEvent('New guest session initiated');
        }

        // CSRF protection
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return validateSessionIntegrity();
    } catch (Exception $e) {
        logSecurityEvent('Session initialization failed: ' . $e->getMessage(), 'critical');
        return false;
    }
}

// ...existing code...

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

// ...existing code...

// Add session timeout definition
if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 1800); // 30 minutes
}

// Modify validateSessionIntegrity() to use SESSION_TIMEOUT
function validateSessionIntegrity() {
    if (!isset($_SESSION['initiated'])) {
        logSecurityEvent('Session integrity check failed: not initiated', 'warning');
        return false;
    }

    $currentIp = hash('sha256', $_SERVER['REMOTE_ADDR']);
    $currentAgent = hash('sha256', $_SERVER['HTTP_USER_AGENT']);
    
    if (isset($_SESSION['user_id'])) {
        if ($_SESSION['client_ip'] !== $currentIp || $_SESSION['user_agent'] !== $currentAgent) {
            logSecurityEvent('Session integrity check failed: authenticated user mismatch', 'warning');
            destroySession();
            return false;
        }
    } else {
        $_SESSION['client_ip'] = $currentIp;
        $_SESSION['user_agent'] = $currentAgent;
        $_SESSION['guest'] = true;
    }

    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        logSecurityEvent('Session expired due to inactivity', 'info');
        destroySession();
        return false;
    }

    if (time() - $_SESSION['last_activity'] > (SESSION_TIMEOUT - 300)) { // refresh if within 5 minutes of expiry
        refreshSession();
    }

    $_SESSION['last_activity'] = time();
    return true;
}

// ...existing code...

function generateCsrfToken() {
    try {
        // Ensure we have a token and it's not expired
        if (empty($_SESSION['csrf_token']) || 
            !isset($_SESSION['csrf_time']) || 
            (time() - $_SESSION['csrf_time'] > 1800)) {
            
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_time'] = time();
        }
        return $_SESSION['csrf_token'];
    } catch (Exception $e) {
        logSecurityEvent('CSRF token generation failed: ' . $e->getMessage(), 'error');
        // Fallback to a less secure but functional token
        return hash('sha256', uniqid(mt_rand(), true));
    }
}

/**
 * Validate CSRF token from user input.
 */
function validateCsrfToken($token)
{
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        logSecurityEvent('CSRF validation failed for token: ' . ($token ?? 'null'), 'warning');
        return false;
    }
    return true;
}

/**
 * Return CSRF hidden input field for forms.
 */
function csrf_field()
{
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Sanitize user input to prevent XSS.
 */
function sanitizeInput($data)
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
        logSecurityEvent('Initiating session destruction: ' . ($wasGuest ? 'guest' : 'user') . ' session');
        
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
        
        logSecurityEvent('Session destroyed successfully');
        return true;
    } catch (Exception $e) {
        logSecurityEvent('Session destruction error: ' . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Check if a user is logged in.
 */
function isUserLoggedIn()
{
    return session_status() === PHP_SESSION_ACTIVE && 
           isset($_SESSION['user_id']) && 
           !($_SESSION['guest'] ?? true) && 
           validateSessionIntegrity();
}

/**
 * Get the logged-in user's role.
 */
function getUserRole()
{
    return $_SESSION['user_role'] ?? 'guest';
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
    // Changed to use security.log for logging all security events.
    $logFile = __DIR__ . '/../../logs/security.log';
    $timestamp = date('Y-m-d H:i:s');
    $userId = $_SESSION['user_id'] ?? 'guest';

    try {
        // Decode the token (assuming using Firebase JWT library)
        $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key('your-secret-key', 'HS256'));

        // Check if the token is expired
        if ($decoded->exp < time()) {
            logSecurityEvent("JWT expired for token: $token", 'warning');
            return false;
        }

        return $decoded;
    } catch (Exception $e) {
        logSecurityEvent("JWT validation failed: " . $e->getMessage(), 'error');
        return false;
    }
} // <-- Added missing closing brace

/**
 * Enforce authentication for protected pages.
 */
function requireUserAuth() {
    requireAuth();
}

// New function to enforce global JWT validation
function validateToken($token) {
    return validateJWT($token);
}

// Modify requireAuth() to enforce CSRF token on API POST requests
function requireAuth($allowGuest = false) {
    $apiRequest = defined('API_ENTRY');
    $authValid = false;

    if ($apiRequest) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? ($_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
        if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $token = $matches[1];
            if (validateToken($token) !== false) {
                $authValid = true;
            }
        }
        // Enforce CSRF validation on API POST requests
        if ($apiRequest && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
            if (!validateCsrfToken($csrf)) {
                logAuthFailure('API CSRF validation failed for request to ' . ($_SERVER['REQUEST_URI'] ?? 'unknown'));
                header('Content-Type: application/json');
                header('HTTP/1.1 403 Forbidden');
                echo json_encode(['error' => 'CSRF token invalid']);
                exit();
            }
        }
    }
    
    if (!$authValid && isUserLoggedIn()) {
        $authValid = true;
    }
    
    if (!$authValid && $allowGuest) {
        $authValid = true;
    }
    
    if (!$authValid) {
        $message = 'Unauthorized access attempt to ' . ($_SERVER['REQUEST_URI'] ?? 'unknown');
        logAuthFailure($message);
        if ($apiRequest) {
            header('Content-Type: application/json');
            header('HTTP/1.1 401 Unauthorized');
            echo json_encode(['error' => 'Unauthorized']);
        } else {
            header('Location: /auth/login.php');
        }
        exit();
    }
}

// Initialize secure session when the file is included
if (!startSecureSession()) {
    logSecurityEvent('Critical: Failed to initialize secure session', 'critical');
}
?>
