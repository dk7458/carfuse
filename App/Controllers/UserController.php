<?php

namespace App\Controllers;

use App\Services\UserService;
use App\Helpers\ApiHelper;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;
use App\Services\Auth\TokenService;
use Illuminate\Routing\Controller;

/**
 * User Management Controller
 *
 * Handles profile management, password resets, and dashboard access.
 */
class UserController extends Controller
{
    private UserService $userService;
    private TokenService $tokenService;
    private ExceptionHandler $exceptionHandler;
    private LoggerInterface $userLogger;
    private LoggerInterface $auditLogger;

    public function __construct(
        UserService $userService,
        TokenService $tokenService, // Need TokenService to extract user ID from token
        ExceptionHandler $exceptionHandler,
        LoggerInterface $userLogger,
        LoggerInterface $auditLogger
    ) {
        $this->userService = $userService;
        $this->tokenService = $tokenService;
        $this->exceptionHandler = $exceptionHandler;
        $this->userLogger = $userLogger;
        $this->auditLogger = $auditLogger;
    }

    /**
     * Retrieve current user profile.
     */
    public function getUserProfile()
    {
        try {
            // Get user ID from JWT token
            $token = $_COOKIE['jwt'] ?? null;
            if (!$token) {
                return ApiHelper::sendJsonResponse('error', 'Authentication required', [], 401);
            }
            
            $tokenData = $this->tokenService->decodeToken($token);
            if (!$tokenData || !isset($tokenData['sub'])) {
                return ApiHelper::sendJsonResponse('error', 'Invalid token', [], 401);
            }
            
            $userId = $tokenData['sub'];
            $user = $this->userService->getUserById($userId);
            
            if (!$user) {
                return ApiHelper::sendJsonResponse('error', 'User not found', [], 404);
            }
            
            // Remove sensitive data before responding
            unset($user['password_hash']);
            
            return ApiHelper::sendJsonResponse('success', 'User profile retrieved', $user, 200);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'Failed to retrieve user profile', [], 500);
        }
    }

    /**
     * Update user profile
     */
    public function updateProfile()
    {
        try {
            // Get user ID from JWT token
            $token = $_COOKIE['jwt'] ?? null;
            if (!$token) {
                return ApiHelper::sendJsonResponse('error', 'Authentication required', [], 401);
            }
            
            $tokenData = $this->tokenService->decodeToken($token);
            if (!$tokenData || !isset($tokenData['sub'])) {
                return ApiHelper::sendJsonResponse('error', 'Invalid token', [], 401);
            }
            
            $userId = $tokenData['sub'];
            
            // Get update data
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
    }

    /**
     * User dashboard access
     */
    public function userDashboard()
    {
        try {
            // Get user ID from JWT token
            $token = $_COOKIE['jwt'] ?? null;
            if (!$token) {
                return ApiHelper::sendJsonResponse('error', 'Authentication required', [], 401);
            }
            
            $tokenData = $this->tokenService->decodeToken($token);
            if (!$tokenData || !isset($tokenData['sub'])) {
                return ApiHelper::sendJsonResponse('error', 'Invalid token', [], 401);
            }
            
            $userId = $tokenData['sub'];
            
            // Get user dashboard data
            $dashboardData = $this->userService->getDashboardData($userId);
            
            return ApiHelper::sendJsonResponse('success', 'Dashboard data retrieved', $dashboardData, 200);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'Failed to retrieve dashboard', [], 500);
        }
    }
}
