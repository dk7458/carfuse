<?php

namespace App\Services;

use Psr\Log\LoggerInterface;
use Firebase\JWT\JWT;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use App\Helpers\DatabaseHelper;
use App\Helpers\ApiHelper;
use App\Helpers\ExceptionHandler;
use App\Helpers\LoggingHelper;

class UserService
{
    public const DEBUG_MODE = true;

    private DatabaseHelper $db;
    private LoggerInterface $logger;
    private ExceptionHandler $exceptionHandler;
    private AuditService $auditService;

    public function __construct(
        LoggerInterface $logger,
        DatabaseHelper $db,
        ExceptionHandler $exceptionHandler,
        AuditService $auditService
    ) {
        $this->logger = $logger;
        $this->db = $db;
        $this->exceptionHandler = $exceptionHandler;
        $this->auditService = $auditService;
        
        if (self::DEBUG_MODE) {
            $this->logger->info("[auth] UserService initialized", ['service' => 'UserService']);
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
                $this->logger->info("[auth] ✅ User registered.", ['userId' => $userId]);
            }
            
            // Log using the unified audit service
            $this->auditService->logEvent(
                'user',
                'User created',
                ['email' => $data['email']],
                $userId,
                null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );
            
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
            $user = $this->db->table('users')->where('id', $id)->first();
            if (!$user) {
                $this->logger->error("User not found", ['userId' => $id]);
                throw new ModelNotFoundException();
            }
            
            $this->db->table('users')->where('id', $id)->update($data);
            $this->logger->info("✅ User updated.", ['userId' => $id]);
            
            // Log using the unified audit service
            $this->auditService->logEvent(
                'user',
                'User updated',
                array_merge(['user_id' => $id], $data),
                $id,
                null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );
            
            return ['status' => 'success', 'message' => 'User updated successfully', 'data' => ['user_id' => $id]];
        } catch (ModelNotFoundException $e) {
            $this->exceptionHandler->handleException($e);
            return ['status' => 'error', 'message' => 'User not found', 'code' => 404];
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ['status' => 'error', 'message' => 'User update failed'];
        }
    }

    public function authenticate(string $email, string $password): array
    {
        try {
            $user = $this->db->table('users')->where('email', $email)->first();
            
            if (!$user || !Hash::check($password, $user->password_hash)) {
                $this->logger->error("Authentication failed", ['email' => $email]);
                
                // Log failed authentication
                $this->auditService->logEvent(
                    'auth',
                    'Authentication failed',
                    ['email' => $email],
                    null,
                    null,
                    $_SERVER['REMOTE_ADDR'] ?? null
                );
                
                return ['status' => 'error', 'message' => 'Authentication failed', 'code' => 401];
            }
            
            $this->logger->info("✅ Authentication successful.", ['userId' => $user->id]);
            
            // Log successful authentication
            $this->auditService->logEvent(
                'auth',
                'Authentication successful',
                ['email' => $email],
                $user->id,
                null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );
            
            $jwt = $this->generateJWT($user);
            return ['status' => 'success', 'message' => 'Authentication successful', 'data' => ['token' => $jwt]];
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ['status' => 'error', 'message' => 'Authentication error'];
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
                $this->logger->error("Password reset request failed", ['email' => $email]);
                return ['status' => 'error', 'message' => 'User not found', 'code' => 404];
            }
            
            $token = bin2hex(random_bytes(32));
            $expiresAt = now()->addHour();
            
            $this->db->table('password_resets')->insert([
                'email' => $email,
                'token' => $token,
                'expires_at' => $expiresAt
            ]);
            
            $this->logger->info("✅ Password reset requested.", ['userId' => $user->id]);
            
            // Log password reset request
            $this->auditService->logEvent(
                'auth',
                'Password reset requested',
                ['email' => $email],
                $user->id,
                null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );
            
            return [
                'status' => 'success',
                'message' => 'Password reset requested',
                'data' => ['reset_token' => $token]
            ];
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ['status' => 'error', 'message' => 'Password reset request error'];
        }
    }
}
