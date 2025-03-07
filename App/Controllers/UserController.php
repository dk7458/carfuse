<?php

namespace App\Controllers;

use App\Helpers\ApiHelper;
use App\Helpers\ExceptionHandler;
use App\Services\Validator;
use App\Services\Auth\TokenService;
use App\Services\AuditService;
use App\Services\Auth\AuthService;
use App\Models\User; // Inject User model
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * User Management Controller
 *
 * Handles profile management, password resets, and dashboard access.
 */
class UserController extends Controller
{
    private Validator $validator;
    private TokenService $tokenService;
    protected ExceptionHandler $exceptionHandler;
    protected LoggerInterface $logger;
    private AuthService $authService;
    private AuditService $auditService;
    private User $userModel; // Added User model

    public function __construct(
        LoggerInterface $logger,
        Validator $validator,
        TokenService $tokenService,
        ExceptionHandler $exceptionHandler,
        AuthService $authService,
        AuditService $auditService,
        User $userModel // Injected dependency
    ) {
        parent::__construct($logger, $exceptionHandler);
        $this->validator     = $validator;
        $this->tokenService  = $tokenService;
        $this->exceptionHandler = $exceptionHandler;
        $this->authService   = $authService;
        $this->auditService  = $auditService;
        $this->userModel     = $userModel;
    }

    /**
     * Register a new user.
     */
    public function registerUser(Request $request, Response $response)
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            $this->logger->info("Processing user registration", ['email' => $data['email'] ?? 'not provided']);

            $rules = [
                'email'    => 'required|email',
                'password' => 'required|min:6',
                'name'     => 'required|string',
            ];
            $this->validator->validate($data, $rules);

            // Check if email is already in use via User model
            if ($this->userModel->findByEmail($data['email'])) {
                return ApiHelper::sendJsonResponse('error', 'Email already in use', [], 400);
            }
            
            // Prepare and create new user (User model handles password hashing and timestamps)
            $data['role'] = 'user';
            $userId = $this->userModel->create($data);
            
            // Removed direct audit logging – User model handles logging after creation
            
            $this->logger->info("User registered successfully", [
                'user_id' => $userId,
                'email' => $data['email']
            ]);
            return ApiHelper::sendJsonResponse('success', 'User registered successfully', ['user_id' => $userId], 201);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'Registration failed', [], 500);
        }
    }

    /**
     * Retrieve current user profile.
     */
    public function getUserProfile(Request $request, Response $response)
    {
        try {
            $user = $this->tokenService->validateRequest($request);
            if (!$user) {
                return ApiHelper::sendJsonResponse('error', 'User not authenticated', [], 401);
            }
            $userId = $user['id'];
            $this->logger->info("Fetching user profile", ['user_id' => $userId]);

            // Fetch user profile using the model
            $userData = $this->userModel->find($userId);
            if (!$userData) {
                return ApiHelper::sendJsonResponse('error', 'User not found', [], 404);
            }
            
            // Log profile view via AuditService if needed
            $this->auditService->logEvent('profile_viewed', 'User viewed their profile', ['user_id' => $userId], $userId, null, 'user');

            return ApiHelper::sendJsonResponse('success', 'User profile retrieved', $userData, 200);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'Failed to retrieve profile', [], 500);
        }
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request, Response $response)
    {
        try {
            $user = $this->tokenService->validateRequest($request);
            if (!$user) {
                return ApiHelper::sendJsonResponse('error', 'User not authenticated', [], 401);
            }
            $userId = $user['id'];

            $data = json_decode($request->getBody()->getContents(), true);
            $this->logger->info("Updating user profile", ['user_id' => $userId]);

            $rules = [
                'name'       => 'string|max:100',
                'bio'        => 'string|max:500',
                'location'   => 'string|max:100',
                'avatar_url' => 'url|max:255'
            ];
            $this->validator->validate($data, $rules);

            // Delegate profile update to the User model logic
            $this->userModel->updateProfile($userId, $data);

            $this->auditService->logEvent('profile_updated', 'User updated their profile', ['user_id' => $userId, 'fields_updated' => array_keys($data)], $userId, null, 'user');

            // Retrieve updated profile
            $updatedProfile = $this->userModel->find($userId);
            return ApiHelper::sendJsonResponse('success', 'Profile updated successfully', $updatedProfile, 200);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'Failed to update profile', [], 500);
        }
    }

    /**
     * Request password reset.
     */
    public function requestPasswordReset(Request $request, Response $response)
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            if (!isset($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return ApiHelper::sendJsonResponse('error', 'Valid email is required', [], 400);
            }
            
            $this->logger->info("Processing password reset request", ['email' => $data['email']]);
            $user = $this->userModel->findByEmail($data['email']);
            if (!$user) {
                $this->logger->info("Password reset requested for non-existent email", ['email' => $data['email']]);
                return ApiHelper::sendJsonResponse('success', 'If your email is in our system, you will receive reset instructions shortly', [], 200);
            }
            
            $token    = bin2hex(random_bytes(30));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

            // Use model method to create reset token
            $this->userModel->createPasswordReset($data['email'], $token, $ipAddress, $expiresAt);
            $this->auditService->logEvent('password_reset_requested', 'Password reset requested', ['email' => $data['email'], 'expires_at' => $expiresAt], $user['id'], null, 'user');
            
            return ApiHelper::sendJsonResponse('success', 'Password reset instructions sent to your email', [], 200);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'Failed to process password reset request', [], 500);
        }
    }

    /**
     * Reset password with token.
     */
    public function resetPassword(Request $request, Response $response)
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            if (!isset($data['token']) || !isset($data['password']) || strlen($data['password']) < 6) {
                return ApiHelper::sendJsonResponse('error', 'Token and password (min 6 chars) required', [], 400);
            }
            
            $this->logger->info("Processing password reset with token");
            $tokenData = $this->userModel->verifyResetToken($data['token']);
            if (!$tokenData) {
                $this->logger->warning("Invalid or expired password reset token used");
                return ApiHelper::sendJsonResponse('error', 'Invalid or expired token', [], 400);
            }
            $email = $tokenData['email'];
            $user = $this->userModel->findByEmail($email);
            if (!$user) {
                $this->logger->warning("User not found for password reset", ['email' => $email]);
                return ApiHelper::sendJsonResponse('error', 'User not found', [], 404);
            }
            
            // Update user's password using model method
            $this->userModel->updatePassword($user['id'], $data['password']);
            // Optionally, mark the token as used if desired (User model provides markResetTokenUsed)
            
            $this->logger->info("Password reset successful", [
                'user_id' => $user['id'],
                'email' => $email
            ]);
            return ApiHelper::sendJsonResponse('success', 'Password has been reset successfully', [], 200);
        } catch (\Exception $e) {
            $this->logger->error("Failed to reset password", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ApiHelper::sendJsonResponse('error', 'Failed to reset password', [], 500);
        }
    }

    /**
     * User dashboard access.
     */
    public function userDashboard(Request $request, Response $response)
    {
        try {
            $user = $this->tokenService->validateRequest($request);
            if (!$user) {
                return ApiHelper::sendJsonResponse('error', 'User not authenticated', [], 401);
            }

            $userId = $user['id'];
            $this->logger->info("User accessing dashboard", ['user_id' => $userId]);

            // Replace direct DB calls with model methods
            $userData = $this->userModel->find($userId);
            if (!$userData) {
                return ApiHelper::sendJsonResponse('error', 'User not found', [], 404);
            }

            // Get recent activity from model
            $recentActivity = $this->userModel->getRecentActivity($userId, 5);

            // Log activity via model
            $this->userModel->logActivity($userId, 'dashboard_access', 'User accessed their dashboard', $_SERVER['REMOTE_ADDR'] ?? null);

            $dashboardData = [
                'user' => $userData,
                'recent_activity' => $recentActivity
            ];

            return ApiHelper::sendJsonResponse('success', 'User Dashboard', $dashboardData, 200);
        } catch (\Exception $e) {
            $this->logger->error("Failed to load user dashboard", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->getAttribute('user_id') ?? 'unknown'
            ]);
            return ApiHelper::sendJsonResponse('error', 'Failed to load dashboard', [], 500);
        }
    }
}
