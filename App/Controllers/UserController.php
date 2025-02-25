<?php

namespace App\Controllers;

use App\Services\UserService;
use App\Helpers\ApiHelper;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;
use Illuminate\Routing\Controller;

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

    public function __construct(
        UserService $userService,
        ExceptionHandler $exceptionHandler,
        LoggerInterface $userLogger,
        LoggerInterface $auditLogger
    ) {
        $this->userService = $userService;
        $this->exceptionHandler = $exceptionHandler;
        $this->userLogger = $userLogger;
        $this->auditLogger = $auditLogger;
    }

    /**
     * Retrieve current user profile.
     */
    public function getUserProfile()
    {
        // Retrieve user profile via UserService (assumes a method exists)
        // For example, get the userId from a secure context/token (not session)
        $userId = $_GET['id'] ?? null;
        if (!$userId) {
            return ApiHelper::sendJsonResponse('error', 'User ID missing', null, 400);
        }
        $user = $this->userService->getUserProfile($userId);
        return ApiHelper::sendJsonResponse('success', 'User profile retrieved', $user, 200);
    }

    /**
     * Update user profile
     */
    public function updateProfile()
    {
        $data = $_POST;
        $rules = [
            'name'    => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'email'   => 'required|email',
            'phone'   => 'nullable|string|max:15',
            'address' => 'nullable|string|max:255',
        ];
        if (!$this->userService->getValidator()->validate($data, $rules)) {
            return ApiHelper::sendJsonResponse('error', 'Validation failed', $this->userService->getValidator()->errors(), 400);
        }
        try {
            $this->userService->updateUserProfile($data);
            $this->auditLogger->info("User profile updated", ['user_id' => $data['id'] ?? null]);
            return ApiHelper::sendJsonResponse('success', 'Profile updated successfully', null, 200);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
        }
    }

    /**
     * User dashboard access
     */
    public function userDashboard()
    {
        // Dashboard access logic via UserService if needed.
        // ...existing dashboard logic...
        $html = "<html><body><h1>User Dashboard</h1><!-- ...existing dashboard HTML... --></body></html>";
        return ApiHelper::sendJsonResponse('success', 'User Dashboard', $html, 200);
    }
}
