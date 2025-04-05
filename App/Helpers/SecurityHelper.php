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

    private LoggerInterface $logger;
    private string $logFile;
    
    /**
     * Constructor with dependency injection
     *
     * @param LoggerInterface $logger Logger instance
     * @param string $logFile Path to security log file
     */
    public function __construct(LoggerInterface $logger, string $logFile = null)
    {
        $this->logger = $logger;
        $this->logFile = $logFile ?? __DIR__ . '/../../logs/security.log';
        
        // Initialize secure session
        $this->startSecureSession();
    }

    // Standardized Logging Function
    public function securityLog($message, $level = 'info', $category = 'Security')
    {
        if (method_exists($this->logger, 'log')) {
            $this->logger->log($level, "[$category] $message");
        } else {
            error_log("[$category][$level] $message");
        }
    }

    // Log authentication events
    public function logAuthEvent($message, $level = 'info')
    {
        $this->securityLog($message, $level, 'Auth');
    }

    // Helper to log authentication failures
    public function logAuthFailure($message)
    {
        $this->securityLog($message, 'error', 'Auth');
    }

    // Secure Session Handling using native PHP sessions
    public function startSecureSession()
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
    public function refreshSession()
    {
        $timestamp = date('Y-m-d H:i:s');

        try {
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION['last_activity'] = time();
                session_regenerate_id(true);
                error_log("[$timestamp][info] Session refreshed\n", 3, $this->logFile);
            }
        } catch (\Exception $e) {
            error_log("[$timestamp][error] Session refresh failed: " . $e->getMessage() . "\n", 3, $this->logFile);
        }
    }

    // Replace Laravel session calls with native PHP for session expiry enforcement
    public function enforceSessionExpiry()
    {
        if (!isset($_SESSION['last_activity'])) {
            $_SESSION['last_activity'] = time();
            return;
        }
        if (time() - $_SESSION['last_activity'] > 1800) { // 30 min timeout
            $this->securityLog('Session expired due to inactivity', 'info');
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                setcookie(session_name(), '', time() - 42000, '/');
            }
            session_destroy();
        }
    }

    // Fingerprint-Based Session Integrity Check
    public function validateSessionIntegrity()
    {
        $currentIp = hash('sha256', $_SERVER['REMOTE_ADDR']);
        $currentAgent = hash('sha256', $_SERVER['HTTP_USER_AGENT']);

        if (!isset($_SESSION['client_ip'])) {
            $_SESSION['client_ip'] = $currentIp;
            $_SESSION['user_agent'] = $currentAgent;
            return true;
        }
        if ($_SESSION['client_ip'] !== $currentIp || $_SESSION['user_agent'] !== $currentAgent) {
            $this->securityLog('Session integrity check failed: Mismatch detected', 'warning');
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
    public function sanitizeInput($data)
    {
        if (!isset($data) || $data === null) {
            $data = ''; // Default to empty string to prevent undefined variable errors
        }
        $cleanedData = trim((string)$data);
        return htmlspecialchars($cleanedData, ENT_QUOTES, 'UTF-8');
    }

    // Generate secure random string (for password resets, API keys, etc.)
    public function generateSecureToken($length = 64)
    {
        return bin2hex(random_bytes($length / 2));
    }

    // Secure Session Destruction using native PHP
    public function destroySession()
    {
        $this->securityLog('Destroying session', 'info');
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            setcookie(session_name(), '', time() - 42000, '/');
        }
        session_destroy();
        $this->securityLog('Session destroyed successfully', 'info');
    }

    // Check if a user is logged in
    public function isUserLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    // Get the logged-in user's role
    public function getUserRole()
    {
        return isset($_SESSION['user_id']) ? ($_SESSION['user_role'] ?? 'guest') : 'guest';
    }

    // Get session data safely
    public function getSessionData($key)
    {
        return $_SESSION[$key] ?? null;
    }

    // Set session data safely
    public function setSessionData($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    // Validate JWT token
    public function validateJWT($token)
    {
        // Replace Laravel's authentication with a native JWT approach or session check.
        // For example, decode using firebase/php-jwt, here we simply check session.
        return $_SESSION['user_id'] ?? null;
    }

    // Enforce authentication for protected pages
    public function requireUserAuth()
    {
        return $this->requireAuth();
    }

    // Custom Authentication Enforcement
    public function requireAuth($allowGuest = false)
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
    public function generateCsrfToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // Validate CSRF token in POST requests
    public function validateCsrfToken($token)
    {
        return isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] === $token;
    }

    // Return structured JSON response
    public function jsonResponse($data, $statusCode = 200)
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }
}
