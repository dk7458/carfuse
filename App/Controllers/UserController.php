<?php

namespace App\Controllers;

use App\Models\User;
use App\Services\UserService;
use App\Services\NotificationService;
use App\Services\Validator;
use App\Services\RateLimiter;
use AuditManager\Services\AuditService;
use Psr\Log\LoggerInterface;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use App\Helpers\SecurityHelper;

/**
 * User Management Controller
 *
 * Handles profile management, password resets, and dashboard access.
 */
class UserController
{
    private UserService $userService;
    private LoggerInterface $logger;
    private Validator $validator;
    private RateLimiter $rateLimiter;
    private AuditService $auditService;
    private NotificationService $notificationService;

    public function __construct(
        LoggerInterface $logger,
        Validator $validator,
        RateLimiter $rateLimiter,
        AuditService $auditService,
        NotificationService $notificationService
    ) {
        $this->logger = $logger;
        $this->validator = $validator;
        $this->rateLimiter = $rateLimiter;
        $this->auditService = $auditService;
        $this->notificationService = $notificationService;
        $this->userService = new UserService();
    }

    /**
     * 🔹 Update user profile
     */
    public function updateProfile(Request $request)
    {
        header('Content-Type: application/json');

        try {
            $userId = validateJWT(); // 🔒 Validate token & get user ID
            validateCSRFToken($request); // 🔒 Validate CSRF token

            $data = $request->getParsedBody();

            // ✅ Input Validation
            $rules = [
                'name' => 'required|string|max:255',
                'email' => 'required|email',
                'phone' => 'string|max:20',
                'address' => 'string|max:255',
            ];

            if (!$this->validator->validate($data, $rules)) {
                throw new \Exception("Validation failed: Invalid input.");
            }

            // ✅ Delegate profile update to UserService
            $result = $this->userService->updateProfile($userId, $data);

            if (!$result) {
                throw new \Exception("Profile update failed.");
            }

            // ✅ Log the profile update
            $this->auditService->log(
                'profile_updated',
                'User updated their profile.',
                $userId
            );

            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'data' => []
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Profile Update Failed', ['error' => $e->getMessage()]);
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }
    }

    /**
     * 🔹 Get user profile
     */
    public function getProfile(Request $request)
    {
        header('Content-Type: application/json');

        try {
            $userId = validateJWT(); // 🔒 Validate JWT token

            // ✅ Fetch profile data
            $profile = $this->userService->getProfileById($userId);

            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'data' => $profile
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to retrieve user profile', ['error' => $e->getMessage()]);
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }
    }

    /**
     * 🔹 Request password reset
     */
    public function requestPasswordReset(Request $request)
    {
        header('Content-Type: application/json');

        try {
            $data = $request->getParsedBody();
            $email = $data['email'] ?? '';

            $result = $this->userService->requestPasswordReset($email);

            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'Password reset request processed',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Password Reset Request Failed', ['error' => $e->getMessage()]);
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage(),
                'data' => []
            ]);
        }
    }

    /**
     * 🔹 User dashboard access
     */
    public function userDashboard()
    {
        try {
            validateJWT(); // 🔒 Ensure user is authenticated
            view('dashboard/user_dashboard');
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Dashboard loaded', 'data' => []]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to load user dashboard', ['error' => $e->getMessage()]);
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to load user dashboard', 'data' => []]);
        }
    }
}
