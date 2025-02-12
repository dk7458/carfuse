<?php

namespace App\Middleware;

use App\Services\Auth\TokenService;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

require_once __DIR__ . '/../Helpers/SecurityHelper.php';

class AuthMiddleware
{
    protected TokenService $tokenService;
    private int $maxAttempts = 5;
    private int $blockDuration = 300; // 5 minutes
    private static string $jwtSecret;

    public function __construct()
    {
        $configPath = __DIR__ . '/../../config/encryption.php';
        if (!file_exists($configPath)) {
            throw new Exception("Encryption configuration missing.");
        }

        $encryptionConfig = require $configPath;

        if (!isset($encryptionConfig['jwt_secret'], $encryptionConfig['jwt_refresh_secret'])) {
            throw new Exception("JWT configuration missing in encryption.php.");
        }

        $this->tokenService = new TokenService(
            $encryptionConfig['jwt_secret'],
            $encryptionConfig['jwt_refresh_secret']
        );

        if (!isset($encryptionConfig['jwt_secret'])) {
            throw new Exception("JWT secret missing in encryption configuration.");
        }
        self::$jwtSecret = $encryptionConfig['jwt_secret'];
    }

    // New private method for rate limiting
    private function checkRateLimit(string $ip): bool
    {
        if (!isset($_SESSION['failed_attempts'])) {
            $_SESSION['failed_attempts'] = [];
        }
        if (!isset($_SESSION['failed_attempts'][$ip])) {
            $_SESSION['failed_attempts'][$ip] = ['count' => 0, 'start' => time()];
        }
        $attempt = $_SESSION['failed_attempts'][$ip];
        // Reset count if block duration has passed
        if (time() - $attempt['start'] > $this->blockDuration) {
            $_SESSION['failed_attempts'][$ip] = ['count' => 0, 'start' => time()];
            return true;
        }
        return $attempt['count'] < $this->maxAttempts;
    }

    // New private method to record a failed attempt
    private function recordFailedAttempt(string $ip): void
    {
        if (!isset($_SESSION['failed_attempts'])) {
            $_SESSION['failed_attempts'] = [];
        }
        if (!isset($_SESSION['failed_attempts'][$ip])) {
            $_SESSION['failed_attempts'][$ip] = ['count' => 0, 'start' => time()];
        }
        $_SESSION['failed_attempts'][$ip]['count']++;
        $_SESSION['failed_attempts'][$ip]['start'] = time();
    }

    /**
     * Handle incoming requests.
     *
     * @param mixed $request
     * @param callable $next
     * @param bool $protected Set to true to enforce JWT validation.
     * @return mixed
     */
    public function handle($request, $next, bool $protected = true)
    {
        if (!$protected) {
            return $next($request);
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        // Enforce rate limiting for failed attempts
        if (!$this->checkRateLimit($ip)) {
            http_response_code(429);
            echo json_encode(['error' => 'Too many failed login attempts. Please try again later.']);
            exit;
        }

        $publicRoutes = ['/public', '/api/public']; // Define public routes

        if (in_array($request->getPathInfo(), $publicRoutes)) {
            return $next($request); // Allow guest access for public routes
        }

        // Define protected routes
        $protectedRoutes = [
            '/views/dashboard.php',
            '/views/user/profile.php'
        ];
        
        $currentPath = $request->getPathInfo();
        // Apply middleware to protected views and admin pages
        if (!in_array($currentPath, $protectedRoutes) && strpos($currentPath, '/admin') !== 0) {
            return $next($request);
        }
        
        $token = '';
        // Extract token from Authorization header if available
        $authHeader = $request->getHeader('Authorization');
        if ($authHeader && strpos($authHeader, 'Bearer ') === 0) {
            $token = str_replace('Bearer ', '', $authHeader);
        } elseif (isset($_COOKIE['jwt'])) {
            $token = $_COOKIE['jwt'];
        }
        
        if (empty($token)) {
            $this->logUnauthorized("Missing token");
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized: Missing token.']);
            exit();
        }
        
        try {
            $decoded = JWT::decode($token, new Key(self::$jwtSecret, 'HS256'));
            if ($decoded->exp < time()) {
                $this->logUnauthorized("Token expired");
                http_response_code(403);
                echo json_encode(['error' => 'Unauthorized: Token expired.']);
                exit();
            }
        } catch (Exception $e) {
            $this->logUnauthorized("Token invalid: " . $e->getMessage());
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized: ' . $e->getMessage()]);
            exit();
        }

        // Enforce admin-only routes for paths beginning with '/admin'
        if (strpos($request->getPathInfo(), '/admin') === 0) {
            // Assume the token or session holds a 'role' claim
            $role = $decoded->role ?? ($_SESSION['user_role'] ?? 'user');
            if ($role !== 'admin') {
                http_response_code(403);
                echo json_encode(['error' => 'Forbidden: Admins only']);
                $this->recordFailedAttempt($ip);
                return;
            }
        }

        // Optional: Validate session integrity for web requests
        if (!validateSessionIntegrity()) {
            http_response_code(401);
            echo json_encode(['error' => 'Session expired']);
            $this->recordFailedAttempt($ip);
            return;
        }

        return $next($request);
    }

    private function unauthorizedResponse($message)
    {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $message, 'data' => []]);
        exit();
    }

    private function logAuthAttempt($status, $message)
    {
        $logMessage = sprintf("[%s] %s: %s from IP: %s\n", date('Y-m-d H:i:s'), ucfirst($status), $message, $_SERVER['REMOTE_ADDR']);
        file_put_contents(__DIR__ . '/../../logs/auth.log', $logMessage, FILE_APPEND);
    }

    private function logUnauthorized(string $message): void
    {
        $logMessage = sprintf("[%s] Unauthorized access: %s from IP: %s\n", 
            date('Y-m-d H:i:s'), 
            $message, 
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        );
        file_put_contents(BASE_PATH . '/logs/auth.log', $logMessage, FILE_APPEND);
    }
}
