<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Helpers\DatabaseHelper;
use Firebase\JWT\JWT;
use App\Helpers\ExceptionHandler;
use Firebase\JWT\Key;
use Exception;
use Psr\Log\LoggerInterface;
use App\Helpers\ApiHelper;
use App\Services\Validator;
use App\Services\AuditService;

class AuthService
{
    private $pdo;
    private TokenService $tokenService;
    private ExceptionHandler $exceptionHandler;
    private LoggerInterface $logger;
    private AuditService $auditService;
    private array $encryptionConfig;
    private Validator $validator;
    private User $userModel;

    public function __construct(
        DatabaseHelper $dbHelper,
        TokenService $tokenService,
        ExceptionHandler $exceptionHandler,
        LoggerInterface $logger,
        AuditService $auditService,
        array $encryptionConfig,
        Validator $validator,
        User $userModel
    ) {
        $this->pdo = $dbHelper->getPdo();
        $this->tokenService = $tokenService;
        $this->exceptionHandler = $exceptionHandler;
        $this->logger = $logger;
        $this->auditService = $auditService;
        $this->encryptionConfig = $encryptionConfig;
        $this->validator = $validator;
        $this->userModel = $userModel;

        $this->logger->info("AuthService initialized with app_database connection");
    }

    public function login(array $data)
    {
        try {
            // Use the User model to find by email
            $user = $this->userModel->findByEmail($data['email']);
            $this->logger->debug("Executing login query for user email: {$data['email']}");
            
            if (!$user || !password_verify($data['password'], $user['password_hash']) || !$user['active']) {
                $this->logger->warning("Authentication failed", ['email' => $data['email']]);
                
                // Log failed authentication with unified AuditService
                $this->auditService->logEvent(
                    'auth',
                    'Authentication failed',
                    ['email' => $data['email']],
                    null,
                    null,
                    $_SERVER['REMOTE_ADDR'] ?? null
                );
                
                throw new Exception("Invalid credentials", 401);
            }

            // Cast user array to object for TokenService
            $userObject = (object)$user;
            $this->logger->debug("User data converted to object", ['type' => gettype($userObject)]);

            $token = $this->tokenService->generateToken($userObject);
            $refreshToken = $this->tokenService->generateRefreshToken($userObject);

            // Log successful login with unified AuditService
            $this->auditService->logEvent(
                'auth',
                'Authentication successful',
                ['email' => $user['email'], 'user_id' => $user['id']],
                $user['id'],
                null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );

            // Include minimal user information in the result
            return [
                'token'         => $token,
                'refresh_token' => $refreshToken,
                'user_id'       => $user['id'],
                'name'          => $user['name'],
                'email'         => $user['email']
            ];
        } catch (Exception $e) {
            $this->logger->error("[auth] ❌ Login error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    public function register(array $data)
    {
        try {
            $this->logger->info("Starting registration process", ['email' => $data['email'] ?? 'unknown']);
            
            // Define the validation rules, ensuring surname and confirm_password are required
            $rules = [
                'email'           => 'required|email|unique:users,email',
                'password'        => 'required|min:8',
                'confirm_password'=> 'required|same:password',
                'name'            => 'required|string',
                'surname'         => 'required|string', // Ensure surname is required
                'phone'           => 'string|nullable',
                'address'         => 'string|nullable',
                'pesel_or_id'     => 'string|nullable'
            ];

            // Log sanitized input data (without passwords)
            $logData = $data;
            if (isset($logData['password'])) unset($logData['password']);
            if (isset($logData['confirm_password'])) unset($logData['confirm_password']);
            $this->logger->debug("Registration input data", ['data' => $logData]);
            
            // Validate input data
            $this->validator->validate($data, $rules);
            
            // Check passwords match (redundant with validation but keeping as a double-check)
            if (!isset($data['password']) || !isset($data['confirm_password']) || $data['password'] !== $data['confirm_password']) {
                $this->logger->warning("Passwords don't match during registration");
                throw new Exception("Passwords do not match", 400);
            }
            
            // Prepare user data for creation via model
            $userData = [
                'name' => $data['name'],
                'surname' => $data['surname'],
                'email' => $data['email'],
                'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'pesel_or_id' => $data['pesel_or_id'] ?? null,
                'role' => 'user', // Default role, only override if admin is creating the user
                'email_notifications' => $data['email_notifications'] ?? 0,
                'sms_notifications' => $data['sms_notifications'] ?? 0,
                'active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Log prepared data (without password_hash)
            $logUserData = $userData;
            unset($logUserData['password_hash']);
            $this->logger->debug("Prepared user data for database", ['data' => $logUserData]);
            
            // Use the User model to create the user
            $userId = $this->userModel->create($userData);
            
            $this->logger->info("User registered successfully", ['user_id' => $userId, 'email' => $data['email']]);
            
            // Log registration with unified AuditService - business logic event
            $this->auditService->logEvent(
                'auth',
                'User registration',
                ['email' => $data['email'], 'name' => $data['name']],
                $userId,
                null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );
            
            return ['user_id' => $userId];
        } catch (\InvalidArgumentException $e) {
            $this->logger->warning("Validation error during registration", ['error' => $e->getMessage()]);
            throw $e;
        } catch (Exception $e) {
            $this->logger->error("Registration error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    public function refresh(array $data)
    {
        try {
            // Use the new method to decode the refresh token
            $decoded = $this->tokenService->decodeRefreshToken($data['refresh_token']);
            
            // Use the User model to find user by ID
            $user = $this->userModel->find($decoded->sub);
            $this->logger->debug("Executing refresh query for user ID: {$decoded->sub}");
            
            if (!$user) {
                $this->logger->warning("Invalid refresh token", ['token_sub' => $decoded->sub]);
                throw new Exception("Invalid refresh token", 400);
            }

            // Cast user array to object for TokenService
            $userObject = (object)$user;
            $this->logger->debug("User data converted to object for token refresh", ['type' => gettype($userObject)]);

            $token = $this->tokenService->generateToken($userObject);
            $this->logger->info("Token refreshed successfully", ['user_id' => $user['id']]);
            
            // Log token refresh with unified AuditService - business logic event
            $this->auditService->logEvent(
                'auth',
                'Token refreshed',
                ['user_id' => $user['id']],
                $user['id'],
                null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );
            
            return ['token' => $token];
        } catch (Exception $e) {
            $this->logger->error("Refresh token error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    public function logout(array $data)
    {
        // Extract user ID from token if available
        $userId = null;
        if (!empty($data['user_id'])) {
            $userId = (int)$data['user_id'];
        }
        
        // Log logout with unified AuditService - business logic event
        $this->auditService->logEvent(
            'auth',
            'User logged out',
            [],
            $userId,
            null,
            $_SERVER['REMOTE_ADDR'] ?? null
        );
        
        return ["message" => "Logged out successfully"];
    }

    public function updateProfile($userId, array $data)
    {
        try {
            // Get current user data
            $user = $this->userModel->find($userId);
            if (!$user) {
                throw new Exception("User not found", 404);
            }
            
            // Prepare update data
            $updateData = [];
            
            // Handle fields that can be updated
            if (isset($data['name'])) {
                $updateData['name'] = $data['name'];
            }
            if (isset($data['surname'])) {
                $updateData['surname'] = $data['surname'];
            }
            if (isset($data['phone'])) {
                $updateData['phone'] = $data['phone'];
            }
            if (isset($data['address'])) {
                $updateData['address'] = $data['address'];
            }
            if (isset($data['email_notifications'])) {
                $updateData['email_notifications'] = (int)$data['email_notifications'];
            }
            if (isset($data['sms_notifications'])) {
                $updateData['sms_notifications'] = (int)$data['sms_notifications'];
            }
            
            // Only update if we have data
            if (!empty($updateData)) {
                $updateData['updated_at'] = date('Y-m-d H:i:s');
                
                // Update the user via model
                $this->userModel->update($userId, $updateData);
                
                // Log the profile update - business logic event
                $this->auditService->logEvent(
                    'auth',
                    'Profile updated',
                    ['user_id' => $userId],
                    $userId,
                    null,
                    $_SERVER['REMOTE_ADDR'] ?? null
                );
                
                return ["message" => "Profile updated successfully"];
            }
            
            return ["message" => "No changes to update"];
        } catch (Exception $e) {
            $this->logger->error("Update profile error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    /**
     * Initiates the password reset process
     */
    public function resetPasswordRequest(array $data): array
    {
        try {
            if (!isset($data['email'])) {
                throw new Exception("Email is required", 400);
            }
            
            // Validate email format
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format", 400);
            }
            
            // Use the User model to find user by email
            $user = $this->userModel->findByEmail($data['email']);
            $this->logger->debug("Executing password reset request query for email: {$data['email']}");
            
            if (!$user) {
                // Don't reveal that the email doesn't exist (security best practice)
                $this->logger->info("Password reset requested for non-existent email", ['email' => $data['email']]);
                return ["message" => "If your email is registered, you will receive a password reset link"];
            }
            
            // Generate a secure reset token
            $resetToken = bin2hex(random_bytes(32));
            $tokenExpiry = date('Y-m-d H:i:s', time() + 3600); // Token valid for 1 hour
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            
            // Store the token using a model method
            $this->userModel->createPasswordReset($user['email'], $resetToken, $ipAddress, $tokenExpiry);
            
            // Log password reset request with unified AuditService - business logic event
            $this->auditService->logEvent(
                'auth',
                'Password reset requested',
                ['email' => $user['email']],
                $user['id'],
                null,
                $ipAddress
            );
            
            // In a real application, you would send an email here
            // For this example, we'll just return the token (not secure for production)
            return [
                "message" => "Password reset email sent",
                "debug_token" => $resetToken // Remove this in production!
            ];
        } catch (Exception $e) {
            $this->logger->error("Password reset request error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Completes the password reset process
     */
    public function resetPassword(array $data): array
    {
        try {
            // Validate required fields
            if (!isset($data['token']) || !isset($data['password']) || !isset($data['confirm_password'])) {
                throw new Exception("Token, password and confirmation are required", 400);
            }
            
            // Validate password
            if (strlen($data['password']) < 8) {
                throw new Exception("Password must be at least 8 characters", 400);
            }
            
            // Check passwords match
            if ($data['password'] !== $data['confirm_password']) {
                throw new Exception("Passwords do not match", 400);
            }
            
            // Verify token using the User model
            $tokenRecord = $this->userModel->verifyResetToken($data['token']);
            $this->logger->debug("Verifying reset token: {$data['token']}");
            
            if (!$tokenRecord) {
                throw new Exception("Invalid or expired token", 400);
            }
            
            // Get user via model
            $user = $this->userModel->findByEmail($tokenRecord['email']);
            $this->logger->debug("Retrieving user for password reset, email: {$tokenRecord['email']}");
            
            if (!$user) {
                throw new Exception("User not found", 404);
            }
            
            // Update the password via model
            $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
            $this->userModel->updatePassword($user['id'], $hashedPassword);
            $this->logger->debug("Updating password for user ID: {$user['id']}");
            
            // Mark token as used via model
            $this->userModel->markResetTokenUsed($tokenRecord['id']);
            
            // Log password reset completion with unified AuditService - business logic event
            $this->auditService->logEvent(
                'auth',
                'Password reset completed',
                ['email' => $user['email']],
                $user['id'],
                null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );
            
            return ["message" => "Password has been reset successfully"];
        } catch (Exception $e) {
            $this->logger->error("Password reset error: " . $e->getMessage());
            throw $e;
        }
    }
}
?>
