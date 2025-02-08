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

// Add debug logging function
function securityLog($message, $level = 'info') {
    $logFile = __DIR__ . '/../../logs/security.log';
    $timestamp = date('Y-m-d H:i:s');
    $sanitizedMessage = str_replace(["\n", "\r"], '', $message);
    error_log("[$timestamp][$level] $sanitizedMessage\n", 3, $logFile);
}

function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Configure session parameters before starting
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $params = session_get_cookie_params();
        
        // Only set session parameters if we haven't started yet
        if (headers_sent()) {
            securityLog('Headers already sent before session configuration', 'warning');
            return false;
        }

        // Configure session cookie
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'],
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        // Attempt to start session
        if (@session_start()) {
            securityLog('Session started successfully');
            
            // Initialize session security measures
            if (empty($_SESSION['initiated'])) {
                session_regenerate_id(true);
                $_SESSION['initiated'] = time();
                $_SESSION['client_ip'] = $_SERVER['REMOTE_ADDR'];
                $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                securityLog('New session initiated and secured');
            }
            
            // Validate session
            if (!validateSessionIntegrity()) {
                destroySession();
                securityLog('Session integrity check failed - session destroyed', 'warning');
                return false;
            }
            
            return true;
        } else {
            securityLog('Failed to start session', 'error');
            return false;
        }
    }
    return true;
}

function validateSessionIntegrity() {
    if (!isset($_SESSION['initiated']) || 
        !isset($_SESSION['client_ip']) || 
        !isset($_SESSION['user_agent'])) {
        return false;
    }
    
    return $_SESSION['client_ip'] === $_SERVER['REMOTE_ADDR'] && 
           $_SESSION['user_agent'] === $_SERVER['HTTP_USER_AGENT'];
}

function generateCsrfToken() {
    try {
        if (empty($_SESSION['csrf_token']) || 
            !isset($_SESSION['csrf_time']) || 
            time() - $_SESSION['csrf_time'] > 3600) {
            
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_time'] = time();
            securityLog('New CSRF token generated');
        }
        return $_SESSION['csrf_token'];
    } catch (Exception $e) {
        securityLog('Failed to generate CSRF token: ' . $e->getMessage(), 'error');
        return false;
    }
}

/**
 * Validate CSRF token from user input.
 */
function validateCsrfToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
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
    if (session_status() === PHP_SESSION_ACTIVE) {
        // Log before destroying
        securityLog('Session destroyed for user: ' . ($_SESSION['user_id'] ?? 'unknown'));
        
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
        
        // Destroy session
        session_destroy();
        return true;
    }
    return false;
}

/**
 * Check if a user is logged in.
 */
function isUserLoggedIn()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return isset($_SESSION['user_id']) && 
               isset($_SESSION['initiated']) && 
               validateSessionIntegrity();
    }
    return false;
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

// Initialize secure session when the file is included
if (!startSecureSession()) {
    securityLog('Failed to initialize secure session', 'critical');
}
?>
