<?php

namespace App\Middleware;

use App\Services\Auth\TokenService;
use App\Helpers\ApiHelper;
use Psr\Log\LoggerInterface;
use Exception;

class AuthMiddleware
{
    private TokenService $tokenService;
    private LoggerInterface $authLogger;

    public function __construct(TokenService $tokenService, LoggerInterface $authLogger)
    {
        $this->tokenService = $tokenService;
        $this->authLogger = $authLogger;
    }

    /**
     * Authenticate API requests using JWT token from Authorization header
     *
     * @param mixed $request The request object
     * @param callable $next The next middleware handler
     * @return mixed
     */
    public function authenticateToken($request, callable $next)
    {
        // Get authorization header
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        // Check for Bearer token
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return ApiHelper::sendJsonResponse('error', 'Authorization token required', null, 401);
        }
        
        $token = $matches[1];
        
        try {
            // Validate the token
            $tokenData = $this->tokenService->validateToken($token);
            
            if (!$tokenData) {
                $this->authLogger->warning('Invalid token used for authentication');
                return ApiHelper::sendJsonResponse('error', 'Invalid or expired token', null, 401);
            }
            
            // Attach user ID to request for later use in controllers
            $request->userId = $tokenData['sub'] ?? null;
            
            if (!$request->userId) {
                $this->authLogger->warning('Token missing user ID');
                return ApiHelper::sendJsonResponse('error', 'Invalid token', null, 401);
            }
            
            // Log successful authentication
            $this->authLogger->info('User authenticated via token', [
                'userId' => $request->userId,
                'endpoint' => $_SERVER['REQUEST_URI']
            ]);
            
            // Continue to the next middleware or controller
            return $next($request);
            
        } catch (Exception $e) {
            $this->authLogger->error('Token authentication error', [
                'error' => $e->getMessage()
            ]);
            return ApiHelper::sendJsonResponse('error', 'Authentication failed', null, 401);
        }
    }

    /**
     * Check if the authenticated user has required role(s)
     *
     * @param mixed $request The request object
     * @param callable $next The next middleware handler
     * @param array|string $roles Required role(s)
     * @return mixed
     */
    public function checkRole($request, callable $next, $roles)
    {
        // First ensure user is authenticated
        $authResult = $this->authenticateToken($request, function($req) use ($roles, $next) {
            // Get user roles from the token
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
            if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                $token = $matches[1];
                $tokenData = $this->tokenService->decodeToken($token);
                
                if (!$tokenData || !isset($tokenData['role'])) {
                    return ApiHelper::sendJsonResponse('error', 'Invalid token or missing role', null, 401);
                }
                
                $userRole = $tokenData['role'];
                
                // Convert roles to array if string
                if (is_string($roles)) {
                    $roles = [$roles];
                }
                
                // Check if user has required role
                if (in_array($userRole, $roles)) {
                    // User has required role, continue
                    return $next($req);
                } else {
                    $this->authLogger->warning('Unauthorized role access attempt', [
                        'userId' => $req->userId,
                        'userRole' => $userRole,
                        'requiredRoles' => implode(',', $roles)
                    ]);
                    return ApiHelper::sendJsonResponse('error', 'Insufficient permissions', null, 403);
                }
            }
            
            return ApiHelper::sendJsonResponse('error', 'Authorization token required', null, 401);
        });
        
        return $authResult;
    }
}
