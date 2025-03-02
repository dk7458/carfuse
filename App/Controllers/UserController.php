<?php

namespace App\Controllers;

use App\Helpers\ApiHelper;
use App\Helpers\DatabaseHelper;
use App\Services\Validator;
use App\Services\Auth\TokenService;
use App\Services\AuditService;
use App\Helpers\ExceptionHandler;
use Psr\Log\LoggerInterface;
use App\Services\Auth\AuthService;
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
    private ExceptionHandler $exceptionHandler;
    protected LoggerInterface $logger;
    private AuthService $authService;
    private AuditService $auditService;

    public function __construct(
        LoggerInterface $logger,
        Validator $validator,
        TokenService $tokenService,
        ExceptionHandler $exceptionHandler,
        AuthService $authService,
        AuditService $auditService
    ) {
        parent::__construct($logger);
        $this->validator = $validator;
        $this->tokenService = $tokenService;
        $this->exceptionHandler = $exceptionHandler;
        $this->authService = $authService;
        $this->auditService = $auditService;
    }

    /**
     * Register a new user.
     */
    public function registerUser(Request $request, Response $response)
    {
        try {
            $data = json_decode($request->getBody()->getContents(), true);
            $this->logger->info("Processing user registration", ['email' => $data['email'] ?? 'not provided']);
            
            // Validate input data
            $rules = [
                'email'    => 'required|email',
                'password' => 'required|min:6',
                'name'     => 'required|string',
            ];
            
            $this->validator->validate($data, $rules);
            
            // Check if email is already in use
            $existingUser = DatabaseHelper::select(
                "SELECT id FROM users WHERE email = ?",
                [$data['email']],
                false // Using application database
            );
            
            if (!empty($existingUser)) {
                return ApiHelper::sendJsonResponse('error', 'Email already in use', [], 400);
            }
            
            // Hash password and prepare user data
            $userData = [
                'email' => $data['email'],
                'name' => $data['name'],
                'password' => password_hash($data['password'], PASSWORD_BCRYPT),
                'created_at' => date('Y-m-d H:i:s'),
                'role' => 'user',
                'status' => 'active'
            ];
            
            // Insert new user using application database
            $userId = DatabaseHelper::insert(
                'users',
                $userData,
                false, // Use application database
                ['operation' => 'user_registration']
            );
            
            // Log the registration in audit logs
            $this->auditService->logEvent(
                'user_registered',
                'User registered successfully',
                ['email' => $data['email']],
                $userId,
                null,
                'user'
            );
            
            $this->logger->info("User registered successfully", [
                'user_id' => $userId,
                'email' => $data['email']
            ]);
            
            return ApiHelper::sendJsonResponse('success', 'User registered successfully', ['user_id' => $userId], 201);
            
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return ApiHelper::sendJsonResponse('error', 'Registration failed', [], 500);
        }
    }

    /**
     * Retrieve current user profile.
     */
    public function getUserProfile(Request $request, Response $response)
    {
        try {
            // Get user from TokenService validation
            $user = $this->tokenService->validateRequest($request);
            if (!$user) {
                return ApiHelper::sendJsonResponse('error', 'User not authenticated', [], 401);
            }
            
            $userId = $user['id'];
            $this->logger->info("Fetching user profile", ['user_id' => $userId]);
            
            // Fetch user data with a single optimized query
            $userData = DatabaseHelper::select(
                "SELECT u.id, u.name, u.email, u.created_at, u.role, 
                        u.status, u.last_login, p.bio, p.avatar_url, p.location
                 FROM users u
                 LEFT JOIN user_profiles p ON u.id = p.user_id
                 WHERE u.id = ? AND u.deleted_at IS NULL",
                [$userId],
                false // Using application database
            );
            
            if (empty($userData)) {
                return ApiHelper::sendJsonResponse('error', 'User not found', [], 404);
            }
            
            // Log profile view in audit logs
            $this->auditService->logEvent(
                'profile_viewed',
                'User viewed their profile',
                ['user_id' => $userId],
                $userId,
                null,
                'user'
            );
            
            return ApiHelper::sendJsonResponse('success', 'User profile retrieved', $userData[0], 200);
            
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
            return ApiHelper::sendJsonResponse('error', 'Failed to retrieve profile', [], 500);
        }
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request, Response $response)
    {
        try {
            // Get user from TokenService validation
            $user = $this->tokenService->validateRequest($request);
            if (!$user) {
                return ApiHelper::sendJsonResponse('error', 'User not authenticated', [], 401);
            }
            
            $userId = $user['id'];
            
            $data = json_decode($request->getBody()->getContents(), true);
            $this->logger->info("Updating user profile", ['user_id' => $userId]);
            
            // Validate input data
            $rules = [
                'name'     => 'string|max:100',
                'bio'      => 'string|max:500',
                'location' => 'string|max:100',
                'avatar_url' => 'url|max:255'
            ];
            
            $this->validator->validate($data, $rules);
            
            // Separate user table fields from profile fields
            $userData = array_intersect_key($data, array_flip(['name']));
            $profileData = array_intersect_key($data, array_flip(['bio', 'location', 'avatar_url']));
            
            // Start transaction for updating both tables
            DatabaseHelper::rawQuery(
                "START TRANSACTION",
                [],
                false // Using application database
            );
            
            // Update user main data if needed
            if (!empty($userData)) {
                DatabaseHelper::update(
                    'users',
                    $userData,
                    ['id' => $userId],
                    false, // Using application database
                    ['operation' => 'profile_update', 'user_id' => $userId]
                );
            }
            
            // Update or insert profile data if needed
            if (!empty($profileData)) {
                // Check if profile exists
                $existingProfile = DatabaseHelper::select(
                    "SELECT user_id FROM user_profiles WHERE user_id = ?",
                    [$userId],
                    false // Using application database
                );
                
                if (empty($existingProfile)) {
                    // Create new profile entry
                    $profileData['user_id'] = $userId;
                    $profileData['created_at'] = date('Y-m-d H:i:s');
                    
                    DatabaseHelper::insert(
                        'user_profiles',
                        $profileData,
                        false, // Using application database
                        ['operation' => 'profile_create', 'user_id' => $userId]
                    );
                } else {
                    // Update existing profile
                    $profileData['updated_at'] = date('Y-m-d H:i:s');
                    
                    DatabaseHelper::update(
                        'user_profiles',
                        $profileData,
                        ['user_id' => $userId],
                        false, // Using application database
                        ['operation' => 'profile_update', 'user_id' => $userId]
                    );
                }
            }
            
            // Commit the transaction
            DatabaseHelper::rawQuery(
                "COMMIT",
                [],
                false // Using application database
            );
            
            // Log profile update in audit logs
            $this->auditService->logEvent(
                'profile_updated',
                'User updated their profile',
                [
                    'user_id' => $userId, 
                    'fields_updated' => array_merge(array_keys($userData), array_keys($profileData))
                ],
                $userId,
                null,
                'user'
            );
            
            // Get updated profile
            $updatedProfile = DatabaseHelper::select(
                "SELECT u.id, u.name, u.email, u.role, u.created_at, 
                        p.bio, p.location, p.avatar_url
                 FROM users u
                 LEFT JOIN user_profiles p ON u.id = p.user_id
                 WHERE u.id = ?",
                [$userId],
                false // Using application database
            );
            
            return ApiHelper::sendJsonResponse('success', 'Profile updated successfully', $updatedProfile[0], 200);
            
        } catch (\Exception $e) {
            // Rollback transaction on error
            DatabaseHelper::rawQuery(
                "ROLLBACK",
                [],
                false // Using application database
            );
            
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
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
            
            // Check if user exists
            $user = DatabaseHelper::select(
                "SELECT id, email FROM users WHERE email = ? AND deleted_at IS NULL",
                [$data['email']],
                false // Using application database
            );
            
            if (empty($user)) {
                // Don't reveal that the email doesn't exist, but log it
                $this->logger->info("Password reset requested for non-existent email", [
                    'email' => $data['email']
                ]);
                return ApiHelper::sendJsonResponse('success', 'If your email is in our system, you will receive reset instructions shortly', [], 200);
            }
            
            // Generate a secure token
            $token = bin2hex(random_bytes(30));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token in secure database
            DatabaseHelper::insert(
                'password_resets',
                [
                    'email' => $data['email'],
                    'token' => $token,
                    'expires_at' => $expiresAt,
                    'created_at' => date('Y-m-d H:i:s')
                ],
                true, // Use secure database
                ['operation' => 'password_reset_request', 'user_id' => $user[0]['id']]
            );
            
            // Log password reset request in audit logs
            $this->auditService->logEvent(
                'password_reset_requested',
                'Password reset requested',
                ['email' => $data['email'], 'expires_at' => $expiresAt],
                $user[0]['id'],
                null,
                'user'
            );
            
            return ApiHelper::sendJsonResponse('success', 'Password reset instructions sent to your email', [], 200);
            
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // The following won't execute if handleException exits as expected
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
            
            // Validate input
            if (!isset($data['token']) || !isset($data['password']) || strlen($data['password']) < 6) {
                return ApiHelper::sendJsonResponse('error', 'Token and password (min 6 chars) required', [], 400);
            }
            
            $this->logger->info("Processing password reset with token");
            
            // Check if token is valid and not expired using secure database
            $resetRequest = DatabaseHelper::select(
                "SELECT email, token, expires_at FROM password_resets 
                 WHERE token = ? AND expires_at > NOW()",
                [$data['token']],
                true // Use secure database
            );
            
            if (empty($resetRequest)) {
                $this->logger->warning("Invalid or expired password reset token used");
                return ApiHelper::sendJsonResponse('error', 'Invalid or expired token', [], 400);
            }
            
            $email = $resetRequest[0]['email'];
            
            // Get user ID
            $user = DatabaseHelper::select(
                "SELECT id FROM users WHERE email = ?",
                [$email]
            );
            
            if (empty($user)) {
                $this->logger->warning("User not found for password reset", ['email' => $email]);
                return ApiHelper::sendJsonResponse('error', 'User not found', [], 404);
            }
            
            $userId = $user[0]['id'];
            
            // Update the password
            $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
            
            DatabaseHelper::update(
                'users',
                [
                    'password' => $hashedPassword,
                    'updated_at' => date('Y-m-d H:i:s')
                ],
                ['id' => $userId],
                false,
                ['operation' => 'password_reset', 'user_id' => $userId]
            );
            
            // Delete the used token using secure database
            DatabaseHelper::delete(
                'password_resets',
                ['token' => $data['token']],
                false,
                true // Use secure database
            );
            
            $this->logger->info("Password reset successful", [
                'user_id' => $userId,
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
            // Get user from TokenService validation
            $user = $this->tokenService->validateRequest($request);
            if (!$user) {
                return ApiHelper::sendJsonResponse('error', 'User not authenticated', [], 401);
            }
            
            $userId = $user['id'];
            
            $this->logger->info("User accessing dashboard", ['user_id' => $userId]);
            
            // Get user data
            $user = DatabaseHelper::select(
                "SELECT id, name, email, role FROM users WHERE id = ? AND deleted_at IS NULL",
                [$userId]
            );
            
            if (empty($user)) {
                return ApiHelper::sendJsonResponse('error', 'User not found', [], 404);
            }
            
            // Get user's recent activity in a single query
            $userActivity = DatabaseHelper::select(
                "SELECT activity_type, description, created_at 
                 FROM user_activities 
                 WHERE user_id = ? 
                 ORDER BY created_at DESC LIMIT 5",
                [$userId]
            );
            
            // Build dashboard data
            $dashboardData = [
                'user' => $user[0],
                'recent_activity' => $userActivity,
                'account_age_days' => $this->calculateAccountAge($userId)
            ];
            
            // Log dashboard access
            DatabaseHelper::insert(
                'user_activities',
                [
                    'user_id' => $userId,
                    'activity_type' => 'dashboard_access',
                    'description' => 'User accessed their dashboard',
                    'created_at' => date('Y-m-d H:i:s'),
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
                ]
            );
            
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
    
    /**
     * Calculate user account age in days
     */
    private function calculateAccountAge(int $userId): int
    {
        $creationDate = DatabaseHelper::select(
            "SELECT created_at FROM users WHERE id = ?",
            [$userId]
        );
        
        if (empty($creationDate)) {
            return 0;
        }
        
        $createdTimestamp = strtotime($creationDate[0]['created_at']);
        $currentTimestamp = time();
        return floor(($currentTimestamp - $createdTimestamp) / (60 * 60 * 24));
    }
}
