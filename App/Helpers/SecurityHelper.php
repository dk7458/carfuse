<?php

namespace App\Helpers;

use Psr\Log\LoggerInterface;
use Firebase\JWT\JWT;

class SecurityHelper
{
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

    // Standardized Logging Function
    public static function securityLog(LoggerInterface $logger, $message, $level = 'info', $category = 'Security')
    {
        if ($logger && method_exists($logger, 'log')) {
            $logger->log($level, "[$category] $message");
        } else {
            error_log("[$category][$level] $message");
        }
    }

    // Log authentication events
    public static function logAuthEvent($message, $level = 'info')
    {
        self::securityLog(null, $message, $level, 'Auth');
    }

    // Helper to log authentication failures
    public static function logAuthFailure($message)
    {
        self::securityLog(null, $message, 'error', 'Auth');
    }

    // Secure Session Handling using native PHP sessions
    public static function startSecureSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_only_cookies', 1);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_samesite', 'Lax');
            session_start();
        }
        return true;
    }

    // Refresh session to extend its duration
    public static function refreshSession()
    {
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

    // Replace Laravel session calls with native PHP for session expiry enforcement
    public static function enforceSessionExpiry(LoggerInterface $logger)
    {
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
            return;
        }
        if (time() - $_SESSION['last_activity'] > 1800) { // 30 min timeout
            self::securityLog($logger, 'Session expired due to inactivity', 'info');
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                setcookie(session_name(), '', time() - 42000, '/');
            }
            session_destroy();
        }
    }

    // Fingerprint-Based Session Integrity Check
    public static function validateSessionIntegrity(LoggerInterface $logger)
    {
        $currentIp = hash('sha256', $_SERVER['REMOTE_ADDR']);
        $currentAgent = hash('sha256', $_SERVER['HTTP_USER_AGENT']);

        if (!isset($_SESSION['client_ip'])) {
            $_SESSION['client_ip'] = $currentIp;
            $_SESSION['user_agent'] = $currentAgent;
            return true;
        }
        if ($_SESSION['client_ip'] !== $currentIp || $_SESSION['user_agent'] !== $currentAgent) {
            self::securityLog($logger, 'Session integrity check failed: Mismatch detected', 'warning');
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                setcookie(session_name(), '', time() - 42000, '/');
            }
            session_destroy();
            return false;
        }
        return true;
    }

    // Sanitize user input to prevent XSS
    public static function sanitizeInput($data)
    {
        if (!isset($data) || $data === null) {
            $data = ''; // Default to empty string to prevent undefined variable errors
        }
        $cleanedData = trim((string)$data);
        return htmlspecialchars($cleanedData, ENT_QUOTES, 'UTF-8');
    }

    // Generate secure random string (for password resets, API keys, etc.)
    public static function generateSecureToken($length = 64)
    {
        return bin2hex(random_bytes($length / 2));
    }

    // Secure Session Destruction using native PHP
    public static function destroySession(LoggerInterface $logger)
    {
        self::securityLog($logger, 'Destroying session', 'info');
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            setcookie(session_name(), '', time() - 42000, '/');
        }
        session_destroy();
        self::securityLog($logger, 'Session destroyed successfully', 'info');
    }

    // Check if a user is logged in
    public static function isUserLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    // Get the logged-in user's role
    public static function getUserRole()
    {
        return isset($_SESSION['user_id']) ? ($_SESSION['user_role'] ?? 'guest') : 'guest';
    }

    // Get session data safely
    public static function getSessionData($key)
    {
        return $_SESSION[$key] ?? null;
    }

    // Set session data safely
    public static function setSessionData($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    // Validate JWT token
    public static function validateJWT($token)
    {
        // Replace Laravel's authentication with a native JWT approach or session check.
        // For example, decode using firebase/php-jwt, here we simply check session.
        return $_SESSION['user_id'] ?? null;
    }

    // Enforce authentication for protected pages
    public static function requireUserAuth()
    {
        return self::requireAuth();
    }

    // Custom Authentication Enforcement
    public static function requireAuth($allowGuest = false)
    {
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

    // CSRF Token Generation
    public static function generateCsrfToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // Validate CSRF token in POST requests
    public static function validateCsrfToken($token)
    {
        return isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] === $token;
    }

    // Return structured JSON response
    public static function jsonResponse($data, $statusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}

// Initialize secure session when the file is included
if (!SecurityHelper::startSecureSession()) {
    SecurityHelper::securityLog(null, 'Critical: Failed to initialize secure session', 'critical');
}
?>
