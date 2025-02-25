<?php

namespace App\Controllers;

use App\Services\UserService;
use App\Helpers\ApiHelper;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;
use Illuminate\Routing\Controller;
use App\Middleware\AuthMiddleware;

/**
 * User Management Controller
 *
 * Handles profile management, password resets, and dashboard access.
 */
class UserController extends Controller
{
    private UserService $userService;
    private ExceptionHandler $exceptionHandler;
    private LoggerInterface $userLogger;
    private LoggerInterface $auditLogger;
    private AuthMiddleware $authMiddleware;

    public function __construct(
        UserService $userService,
        ExceptionHandler $exceptionHandler,
        LoggerInterface $userLogger,
        LoggerInterface $auditLogger,
        AuthMiddleware $authMiddleware
    ) {
        $this->userService = $userService;
        $this->exceptionHandler = $exceptionHandler;
        $this->userLogger = $userLogger;
        $this->auditLogger = $auditLogger;
        $this->authMiddleware = $authMiddleware;
    }

    /**
     * Retrieve current user profile.
     * Protected by authentication middleware.
     */
    public function getUserProfile($request)
    {
        return $this->authMiddleware->authenticateToken($request, function($req) {
            try {
                // Get user ID from authenticated request
                $userId = $req->userId;
                $user = $this->userService->getUserById($userId);
                
                if (!$user) {
                    return ApiHelper::sendJsonResponse('error', 'User not found', [], 404);
                }
                
                // Remove sensitive data before returning
                unset($user['password_hash']);
                
                return ApiHelper::sendJsonResponse('success', 'User profile retrieved', $user, 200);
            } catch (\Exception $e) {
                $this->exceptionHandler->handleException($e);
                return ApiHelper::sendJsonResponse('error', 'Failed to retrieve profile', [], 500);
            }
        });
    }

    /**
     * Update user profile
     * Protected by authentication middleware.
     */
    public function updateProfile($request)
    {
        return $this->authMiddleware->authenticateToken($request, function($req) {
            try {
                // Get user ID from authenticated request
                $userId = $req->userId;
                
                // Get update data from request body
                $data = json_decode(file_get_contents("php://input"), true);
                if (!$data) {
                    return ApiHelper::sendJsonResponse('error', 'Invalid input data', [], 400);
                }
                
                // Update user profile
                $result = $this->userService->updateUser($userId, $data);
                
                $this->auditLogger->info("User profile updated", ['userId' => $userId]);
                return ApiHelper::sendJsonResponse('success', 'Profile updated successfully', [], 200);
            } catch (\Exception $e) {
                $this->exceptionHandler->handleException($e);
                return ApiHelper::sendJsonResponse('error', 'Failed to update profile', [], 500);
            }
        });
    }

    /**
     * User dashboard access
     * Protected by authentication middleware.
     */
    public function userDashboard($request)
    {
        return $this->authMiddleware->authenticateToken($request, function($req) {
            try {
                // Get user ID from authenticated request
                $userId = $req->userId;
                
                // Get user dashboard data
                $dashboardData = $this->userService->getDashboardData($userId);
                
                return ApiHelper::sendJsonResponse('success', 'Dashboard data retrieved', $dashboardData, 200);
            } catch (\Exception $e) {
                $this->exceptionHandler->handleException($e);
                return ApiHelper::sendJsonResponse('error', 'Failed to retrieve dashboard', [], 500);
            }
        });
    }

    /**
     * Admin-only endpoint example
     * Protected by authentication middleware with role check.
     */
    public function adminAction($request)
    {
        return $this->authMiddleware->checkRole($request, function($req) {
            try {
                // Admin-specific logic here
                return ApiHelper::sendJsonResponse('success', 'Admin action completed', [], 200);
            } catch (\Exception $e) {
                $this->exceptionHandler->handleException($e);
                return ApiHelper::sendJsonResponse('error', 'Admin action failed', [], 500);
            }
        }, ['admin']);
    }
}
