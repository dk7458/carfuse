<?php

namespace App\Services;

use Psr\Log\LoggerInterface;
use Firebase\JWT\JWT;
use Exception;
use App\Helpers\DatabaseHelper;
use App\Helpers\ApiHelper;
use App\Helpers\ExceptionHandler;
use App\Models\User;

class UserService
{
    public const DEBUG_MODE = true;

    private DatabaseHelper $db;
    private LoggerInterface $logger;
    private ExceptionHandler $exceptionHandler;
    private AuditService $auditService;
    private User $userModel;
    private string $jwtSecret;

    public function __construct(
        LoggerInterface $logger,
        DatabaseHelper $db,
        ExceptionHandler $exceptionHandler,
        AuditService $auditService,
        User $userModel,
        string $jwtSecret = 'default_secret'
    ) {
        $this->logger = $logger;
        $this->db = $db;
        $this->exceptionHandler = $exceptionHandler;
        $this->auditService = $auditService;
        $this->userModel = $userModel;
        $this->jwtSecret = $jwtSecret;
        
        if (self::DEBUG_MODE) {
            $this->logger->info("[auth] UserService initialized", ['service' => 'UserService']);
        }
    }

    public function createUser(array $data): array
    {
        $rules = User::$rules;

        try {
            Validator::validate($data, $rules);
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }

        try {
            // Use User model to create the user
            $userId = $this->userModel->createWithDefaultRole($data);
            
            if (self::DEBUG_MODE) {
                $this->logger->info("[auth] ✅ User registered.", ['userId' => $userId]);
            }
            
            return ['status' => 'success', 'message' => 'User created successfully', 'data' => ['user_id' => $userId]];
        } catch (Exception $e) {
            $this->logger->error("[auth] ❌ User creation failed: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            return ['status' => 'error', 'message' => 'User creation failed'];
        }
    }

    public function updateUser(int $id, array $data): array
    {
        try {
            // First check if user exists
            $user = $this->userModel->find($id);
            if (!$user) {
                $this->logger->error("User not found", ['userId' => $id]);
                return ['status' => 'error', 'message' => 'User not found', 'code' => 404];
            }
            
            // Use User model to update the user
            $result = $this->userModel->updateProfile($id, $data);
            
            if ($result) {
                // Changed from audit service to logger for profile updates
                $this->logger->info("✅ User profile updated", [
                    'userId' => $id,
                    'fields' => array_keys($data)
                ]);
                return ['status' => 'success', 'message' => 'User updated successfully', 'data' => ['user_id' => $id]];
            } else {
                return ['status' => 'error', 'message' => 'User update failed'];
            }
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ['status' => 'error', 'message' => 'User update failed: ' . $e->getMessage()];
        }
    }

    public function updateUserRole(int $id, string $role): array
    {
        try {
            // First check if user exists
            $user = $this->userModel->find($id);
            if (!$user) {
                $this->logger->error("User not found", ['userId' => $id]);
                return ['status' => 'error', 'message' => 'User not found', 'code' => 404];
            }
            
            // Use User model to update role
            $result = $this->userModel->updateUserRole($id, $role);
            
            if ($result) {
                $this->logger->info("✅ User role updated.", ['userId' => $id, 'role' => $role]);
                
                // Keep audit logging for admin role updates
                $this->auditService->logEvent(
                    'user',
                    'Role changed',
                    [
                        'user_id' => $id, 
                        'previous_role' => $user['role'] ?? 'unknown',
                        'new_role' => $role
                    ],
                    $id,
                    null,
                    $_SERVER['REMOTE_ADDR'] ?? null
                );
                
                return ['status' => 'success', 'message' => 'User role updated successfully'];
            } else {
                return ['status' => 'error', 'message' => 'User role update failed'];
            }
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ['status' => 'error', 'message' => 'Role update failed: ' . $e->getMessage()];
        }
    }

    public function deleteUser(int $id): array
    {
        try {
            // First check if user exists
            $user = $this->userModel->find($id);
            if (!$user) {
                $this->logger->error("User not found", ['userId' => $id]);
                return ['status' => 'error', 'message' => 'User not found', 'code' => 404];
            }
            
            // Check if user is a super admin (this prevents super admin deletion)
            if (isset($user['role']) && $user['role'] === 'super_admin') {
                $this->logger->warning("Attempted to delete super admin account", ['userId' => $id]);
                return ['status' => 'error', 'message' => 'Super admin accounts cannot be deleted', 'code' => 403];
            }
            
            // Use User model to delete user
            $result = $this->userModel->deleteUser($id);
            
            if ($result) {
                $this->logger->info("✅ User deleted.", ['userId' => $id]);
                
                // Keep audit log for user deletions
                $this->auditService->logEvent(
                    'user',
                    'User deleted',
                    [
                        'user_id' => $id,
                        'user_email' => $user['email'] ?? 'unknown',
                        'user_role' => $user['role'] ?? 'unknown'
                    ],
                    null,
                    null,
                    $_SERVER['REMOTE_ADDR'] ?? null
                );
                
                return ['status' => 'success', 'message' => 'User deleted successfully'];
            } else {
                return ['status' => 'error', 'message' => 'User deletion failed'];
            }
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ['status' => 'error', 'message' => 'User deletion failed: ' . $e->getMessage()];
        }
    }

    public function changePassword(int $id, string $currentPassword, string $newPassword): array
    {
        try {
            // Use User model to change password
            $result = $this->userModel->changePassword($id, $currentPassword, $newPassword);
            
            if ($result) {
                $this->logger->info("✅ Password changed.", ['userId' => $id]);
                
                // Log password change through audit service
                $this->auditService->logEvent(
                    'user',
                    'Password changed',
                    ['user_id' => $id],
                    $id,
                    null,
                    $_SERVER['REMOTE_ADDR'] ?? null
                );
                
                return ['status' => 'success', 'message' => 'Password changed successfully'];
            } else {
                return ['status' => 'error', 'message' => 'Password change failed'];
            }
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ['status' => 'error', 'message' => 'Password change failed: ' . $e->getMessage()];
        }
    }

    public function authenticate(string $email, string $password): array
    {
        try {
            $user = $this->userModel->getUserByEmail($email);
            
            if (!$user || !$this->userModel->verifyPassword($password, $user['password_hash'])) {
                $this->logger->error("Authentication failed", ['email' => $email]);
                
                // Use logger instead of audit service for failed authentication
                $this->logger->warning("Failed authentication attempt", [
                    'email' => $email,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null
                ]);
                
                return ['status' => 'error', 'message' => 'Authentication failed', 'code' => 401];
            }
            
            $this->logger->info("✅ Authentication successful.", ['userId' => $user['id']]);
            
            // Use logger instead of audit service for successful authentication
            $this->logger->info("User authenticated successfully", [
                'userId' => $user['id'],
                'email' => $email,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);
            
            $jwt = $this->generateJWT($user);
            return ['status' => 'success', 'message' => 'Authentication successful', 'data' => ['token' => $jwt]];
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ['status' => 'error', 'message' => 'Authentication error: ' . $e->getMessage()];
        }
    }

    private function generateJWT($user): string
    {
        $payload = [
            'sub' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'iat' => time(),
            'exp' => time() + 3600,
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    public function requestPasswordReset(string $email): array
    {
        try {
            $user = $this->userModel->getUserByEmail($email);
            
            if (!$user) {
                $this->logger->error("Password reset request failed", ['email' => $email]);
                return ['status' => 'error', 'message' => 'User not found', 'code' => 404];
            }
            
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Use User model's createPasswordReset method
            $result = $this->userModel->createPasswordReset($email, $token, $_SERVER['REMOTE_ADDR'] ?? null, $expiresAt);
            
            if ($result) {
                $this->logger->info("✅ Password reset requested.", [
                    'userId' => $user['id'],
                    'email' => $email,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? null
                ]);
                
                // Keep audit logging for password reset requests
                $this->auditService->logEvent(
                    'auth',
                    'Password reset requested',
                    ['email' => $email],
                    $user['id'],
                    null,
                    $_SERVER['REMOTE_ADDR'] ?? null
                );
                
                return [
                    'status' => 'success',
                    'message' => 'Password reset requested',
                    'data' => ['reset_token' => $token]
                ];
            } else {
                return ['status' => 'error', 'message' => 'Password reset request failed'];
            }
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ['status' => 'error', 'message' => 'Password reset request error: ' . $e->getMessage()];
        }
    }
}
