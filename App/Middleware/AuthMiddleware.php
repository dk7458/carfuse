<?php

namespace App\Middleware;

use App\Helpers\SecurityHelper;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Log\LoggerInterface;

/**
 * AuthMiddleware - Handles authentication and authorization for API requests.
 * Ensures valid JWT tokens and role-based access control.
 */
class AuthMiddleware
{
    private LoggerInterface $logger;
    private string $jwtSecret;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;

        $configPath = __DIR__ . '/../../config/encryption.php';
        if (!file_exists($configPath)) {
            throw new \Exception("Encryption configuration missing.");
        }

        $encryptionConfig = require $configPath;
        if (!isset($encryptionConfig['jwt_secret'])) {
            throw new \Exception("JWT secret missing in encryption.php.");
        }

        $this->jwtSecret = $encryptionConfig['jwt_secret'];
    }

    /**
     * Handle authentication and authorization.
     * 
     * @param callable $next The next middleware function.
     * @param array $roles Required roles (e.g., 'admin').
     */
    public function handle(callable $next, ...$roles)
    {
        // ✅ Ensure session is active before setting user data
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // ✅ Ensure Authorization header exists
        $headers = getallheaders();
        if (!isset($headers['Authorization']) || !str_starts_with($headers['Authorization'], 'Bearer ')) {
            $this->logAuthEvent('Unauthorized access attempt: Missing Authorization header', 'warning');
            http_response_code(401);
            exit(json_encode(['error' => 'Unauthorized']));
        }

        // ✅ Extract and validate JWT token
        $token = substr($headers['Authorization'], 7);
        $decoded = $this->validateToken($token);
        if (!$decoded) {
            $this->logAuthEvent('Invalid token detected', 'warning');
            http_response_code(401);
            exit(json_encode(['error' => 'Invalid token']));
        }

        // ✅ Store user details in session
        $_SESSION['user_id'] = $decoded['sub'] ?? null;
        $_SESSION['user_role'] = $decoded['role'] ?? 'guest';

        // ✅ Enforce role-based access control
        if (!empty($roles) && !in_array($_SESSION['user_role'], $roles)) {
            $this->logAuthEvent("User {$_SESSION['user_id']} attempted unauthorized access", 'warning');
            http_response_code(403);
            exit(json_encode(['error' => 'Forbidden']));
        }

        // ✅ Proceed with the request
        return $next();
    }

    /**
     * Validate JWT token and return decoded data.
     */
    private function validateToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            return (array) $decoded;
        } catch (\Exception $e) {
            $this->logAuthEvent("Token validation failed: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Log authentication-related events.
     * @param string $message
     * @param string $level (default: info)
     */
    private function logAuthEvent(string $message, string $level = 'info')
    {
        $userId = $_SESSION['user_id'] ?? 'guest';
        $logMessage = "[Auth][{$level}] User: {$userId} - {$message}";
        $this->logger->log($level, $logMessage);
    }

    /**
     * Static method to enforce authentication without middleware.
     */
    public static function requireAuth()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            exit(json_encode(['error' => 'Unauthorized']));
        }
    }

    /**
     * Static method to enforce admin authentication.
     */
    public static function requireAdmin()
    {
        self::requireAuth();
        if ($_SESSION['user_role'] !== 'admin') {
            http_response_code(403);
            exit(json_encode(['error' => 'Forbidden']));
        }
    }
}
