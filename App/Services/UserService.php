<?php

namespace App\Services;

use Psr\Log\LoggerInterface;
use Firebase\JWT\JWT;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use App\Helpers\DatabaseHelper;
use App\Helpers\ApiHelper;
use App\Helpers\ExceptionHandler;
use App\Services\Validator;

class UserService
{
    public const DEBUG_MODE = true;

    private $db;
    private LoggerInterface $userLogger;
    private LoggerInterface $auditLogger;
    private ExceptionHandler $exceptionHandler;
    private Validator $validator;

    public function __construct(
        DatabaseHelper $dbHelper,
        LoggerInterface $userLogger,
        LoggerInterface $auditLogger,
        ExceptionHandler $exceptionHandler,
        Validator $validator
    ) {
        // Ensure we use the app database for user operations
        $this->db = $dbHelper->getAppDatabaseConnection();
        $this->userLogger = $userLogger;
        $this->auditLogger = $auditLogger;
        $this->exceptionHandler = $exceptionHandler;
        $this->validator = $validator;
        
        if (self::DEBUG_MODE) {
            $this->userLogger->info("UserService initialized", ['service' => 'UserService']);
        }
    }

    public function createUser(array $data): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
        ];

        try {
            Validator::validate($data, $rules);
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }

        try {
            $userId = $this->db->table('users')->insertGetId($data);
            if (self::DEBUG_MODE) {
                $this->userLogger->info("[auth] ✅ User registered.", ['userId' => $userId]);
            }
            $this->logAction($userId, 'user_created', ['email' => $data['email']]);
            return ApiHelper::sendJsonResponse('success', 'User created successfully', ['user_id' => $userId], 201);
        } catch (Exception $e) {
            $this->userLogger->error("[auth] ❌ User creation failed: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'User creation failed', [], 500);
        }
    }

    public function getUserById(int $id)
    {
        try {
            $user = $this->db->table('users')->where('id', $id)->first();
            if (!$user) {
                $this->userLogger->error("User not found", ['userId' => $id]);
                return null;
            }
            return (array)$user;
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            return null;
        }
    }

    public function updateUser(int $id, array $data): array
    {
        try {
            // Validate input data
            $rules = [
                'name' => 'string|max:255',
                'email' => 'email',
                'phone' => 'string|max:20',
                'address' => 'string|max:255',
            ];
            
            $this->validator->validate($data, $rules);
            
            // Check if user exists
            $user = $this->db->table('users')->where('id', $id)->first();
            if (!$user) {
                $this->userLogger->error("User not found for update", ['userId' => $id]);
                throw new Exception("User not found");
            }
            
            // Update user in app database
            $this->db->table('users')->where('id', $id)->update($data);
            $this->userLogger->info("User updated", ['userId' => $id]);
            
            // Log the action
            $this->logAction($id, 'user_updated', $data);
            
            return ['success' => true, 'message' => 'User updated successfully'];
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    public function authenticate(string $email, string $password): array
    {
        try {
            $user = $this->db->table('users')->where('email', $email)->first();
            if (!$user || !Hash::check($password, $user->password_hash)) {
                $this->userLogger->error("Authentication failed", ['email' => $email]);
                $this->logAction(null, 'authentication_failed', ['email' => $email]);
                return ApiHelper::sendJsonResponse('error', 'Authentication failed', [], 401);
            }
            $this->userLogger->info("✅ Authentication successful.", ['userId' => $user->id]);
            $this->logAction($user->id, 'authentication_successful');
            $jwt = $this->generateJWT($user);
            return ApiHelper::sendJsonResponse('success', 'Authentication successful', ['token' => $jwt], 200);
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'Authentication error', [], 500);
        }
    }

    private function generateJWT($user): string
    {
        $payload = [
            'sub' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'iat' => time(),
            'exp' => time() + 3600,
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    public function requestPasswordReset(string $email): array
    {
        try {
            $user = $this->db->table('users')->where('email', $email)->first();
            if (!$user) {
                $this->userLogger->error("Password reset request failed", ['email' => $email]);
                return ApiHelper::sendJsonResponse('error', 'User not found', [], 404);
            }
            $token = bin2hex(random_bytes(32));
            $expiresAt = now()->addHour();
            $this->db->table('password_resets')->insert([
                'email' => $email,
                'token' => $token,
                'expires_at' => $expiresAt
            ]);
            $this->userLogger->info("✅ Password reset requested.", ['userId' => $user->id]);
            $this->logAction($user->id, 'password_reset_requested', ['email' => $email]);
            return ApiHelper::sendJsonResponse('success', 'Password reset requested', ['reset_token' => $token], 200);
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'Password reset request error', [], 500);
        }
    }

    public function getDashboardData(int $userId)
    {
        try {
            // Get user info
            $user = $this->db->table('users')->where('id', $userId)->first();
            if (!$user) {
                return null;
            }
            
            // Get additional dashboard data
            // ...
            
            return [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ],
                'stats' => [
                    // Example dashboard stats
                    'notifications' => 5,
                    'messages' => 2
                ]
            ];
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            return null;
        }
    }

    public function getValidator()
    {
        return $this->validator;
    }

    private function logAction(?int $userId, string $action, array $details = []): void
    {
        try {
            $this->db->table('audit_logs')->insert([
                'user_id' => $userId,
                'action'  => $action,
                'details' => json_encode($details),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $this->auditLogger->info("Action logged", ['userId' => $userId, 'action' => $action]);
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
        }
    }
}
