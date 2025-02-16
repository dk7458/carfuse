<?php

namespace App\Middleware;

use App\Helpers\SecurityHelper;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Log\LoggerInterface;

/**
 * AuthMiddleware - Handles authentication for API requests.
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
    public function handle($next, ...$roles)
    {
        // Ensure Authorization header exists
        $headers = getallheaders();
        if (!isset($headers['Authorization']) || !str_starts_with($headers['Authorization'], 'Bearer ')) {
            $this->logger->warning("[AuthMiddleware] Unauthorized access attempt: Missing Authorization header.");
            http_response_code(401);
            exit(json_encode(['error' => 'Unauthorized']));
        }

        // Extract and validate JWT token
        $token = substr($headers['Authorization'], 7);
        $decoded = $this->validateToken($token);
        if (!$decoded) {
            $this->logger->warning("[AuthMiddleware] Invalid token detected.");
            http_response_code(401);
            exit(json_encode(['error' => 'Invalid token']));
        }

        // Extract user details
        $_SESSION['user_id'] = $decoded['sub'] ?? null;
        $_SESSION['user_role'] = $decoded['role'] ?? 'guest';

        // Check role-based access control
        if (!empty($roles) && !in_array($_SESSION['user_role'], $roles)) {
            $this->logger->warning("[AuthMiddleware] User {$_SESSION['user_id']} attempted unauthorized access.");
            http_response_code(403);
            exit(json_encode(['error' => 'Forbidden']));
        }

        // Proceed with the request
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
            $this->logger->error("[AuthMiddleware] Token validation failed: " . $e->getMessage());
            return false;
        }
    }
}
