<?php

namespace App\Services;

use Psr\Log\LoggerInterface;
use Firebase\JWT\JWT;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use App\Helpers\DatabaseHelper;
use App\Helpers\ApiHelper;
use App\Handlers\ExceptionHandler;

class UserService
{
    public const DEBUG_MODE = true;

    private DatabaseHelper $db;
    private LoggerInterface $authLogger;
    private LoggerInterface $auditLogger;
    private ExceptionHandler $exceptionHandler;

    public function __construct(
        DatabaseHelper $db,
        LoggerInterface $authLogger,
        LoggerInterface $auditLogger,
        ExceptionHandler $exceptionHandler
    ) {
        $this->db = $db;
        $this->authLogger = $authLogger;
        $this->auditLogger = $auditLogger;
        $this->exceptionHandler = $exceptionHandler;
        if (self::DEBUG_MODE) {
            $this->authLogger->info("[auth] UserService initialized", ['service' => 'UserService']);
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
                $this->authLogger->info("[auth] ✅ User registered.", ['userId' => $userId]);
            }
            $this->logAction($userId, 'user_created', ['email' => $data['email']]);
            return ApiHelper::sendJsonResponse('success', 'User created successfully', ['user_id' => $userId], 201);
        } catch (Exception $e) {
            $this->authLogger->error("[auth] ❌ User creation failed: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'User creation failed', [], 500);
        }
    }

    public function updateUser(int $id, array $data): array
    {
        try {
            $user = $this->db->table('users')->where('id', $id)->first();
            if (!$user) {
                $this->authLogger->error("User not found", ['userId' => $id]);
                throw new ModelNotFoundException();
            }
            $this->db->table('users')->where('id', $id)->update($data);
            $this->authLogger->info("✅ User updated.", ['userId' => $id]);
            $this->logAction($id, 'user_updated', $data);
            return ApiHelper::sendJsonResponse('success', 'User updated successfully', ['user_id' => $id], 200);
        } catch (ModelNotFoundException $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'User not found', [], 404);
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'User update failed', [], 500);
        }
    }

    public function authenticate(string $email, string $password): array
    {
        try {
            $user = $this->db->table('users')->where('email', $email)->first();
            if (!$user || !Hash::check($password, $user->password_hash)) {
                $this->authLogger->error("Authentication failed", ['email' => $email]);
                $this->logAction(null, 'authentication_failed', ['email' => $email]);
                return ApiHelper::sendJsonResponse('error', 'Authentication failed', [], 401);
            }
            $this->authLogger->info("✅ Authentication successful.", ['userId' => $user->id]);
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
                $this->authLogger->error("Password reset request failed", ['email' => $email]);
                return ApiHelper::sendJsonResponse('error', 'User not found', [], 404);
            }
            $token = bin2hex(random_bytes(32));
            $expiresAt = now()->addHour();
            $this->db->table('password_resets')->insert([
                'email' => $email,
                'token' => $token,
                'expires_at' => $expiresAt
            ]);
            $this->authLogger->info("✅ Password reset requested.", ['userId' => $user->id]);
            $this->logAction($user->id, 'password_reset_requested', ['email' => $email]);
            return ApiHelper::sendJsonResponse('success', 'Password reset requested', ['reset_token' => $token], 200);
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'Password reset request error', [], 500);
        }
    }

    private function logAction(?int $userId, string $action, array $details = []): void
    {
        try {
            $this->db->table('audit_logs')->insert([
                'user_id' => $userId,
                'action'  => $action,
                'details' => json_encode($details)
            ]);
            $this->auditLogger->info("✅ Logged action.", ['userId' => $userId, 'action' => $action]);
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
        }
    }
}
