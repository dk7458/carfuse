<?php

namespace App\Middleware;

use App\Helpers\ApiHelper;
use App\Services\TokenService;
use App\Handlers\ExceptionHandler;
use Psr\Log\LoggerInterface;

/**
 * AuthMiddleware - Handles authentication and authorization for API requests.
 * Ensures valid JWT tokens and role-based access control.
 */
class AuthMiddleware
{
    private TokenService $tokenService;
    private ExceptionHandler $exceptionHandler;
    private LoggerInterface $authLogger;
    private LoggerInterface $securityLogger;

    public function __construct(
        TokenService $tokenService,
        ExceptionHandler $exceptionHandler,
        LoggerInterface $authLogger,
        LoggerInterface $securityLogger
    ) {
        $this->tokenService = $tokenService;
        $this->exceptionHandler = $exceptionHandler;
        $this->authLogger = $authLogger;
        $this->securityLogger = $securityLogger;
    }

    /**
     * Handle authentication and authorization.
     * 
     * @param callable $next The next middleware function.
     * @param array $roles Required roles (e.g., 'admin').
     */
    public function handle(callable $next, ...$roles)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        try {
            // Retrieve and validate Authorization header
            $headers = getallheaders();
            if (!isset($headers['Authorization']) || !str_starts_with($headers['Authorization'], 'Bearer ')) {
                $this->authLogger->warning("Unauthorized access: Missing Authorization header.");
                ApiHelper::sendJsonResponse('error', 'Unauthorized', [], 401);
            }

            $token = substr($headers['Authorization'], 7);
            $decoded = $this->tokenService->validateToken($token);
            if (!$decoded) {
                $this->authLogger->warning("Invalid token detected.");
                ApiHelper::sendJsonResponse('error', 'Invalid token', [], 401);
            }

            // Store user details in session
            $_SESSION['user_id'] = $decoded['sub'] ?? null;
            $_SESSION['user_role'] = $decoded['role'] ?? 'guest';
            
            // Role-based access control
            if (!empty($roles) && !in_array($_SESSION['user_role'], $roles)) {
                $this->securityLogger->warning("Unauthorized role access attempt.", [
                    'userId' => $_SESSION['user_id'],
                    'requiredRoles' => $roles
                ]);
                ApiHelper::sendJsonResponse('error', 'Forbidden', [], 403);
            }

            $this->authLogger->info("✅ User authenticated.", [
                'userId' => $_SESSION['user_id'],
                'role' => $_SESSION['user_role']
            ]);

            return $next();
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
        }
    }
}
