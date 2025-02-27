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

class AuthService
{
    private $pdo;
    private TokenService $tokenService;
    private ExceptionHandler $exceptionHandler;
    private LoggerInterface $authLogger;
    private LoggerInterface $auditLogger;
    private array $encryptionConfig;
    private Validator $validator;

    public function __construct(
        DatabaseHelper $dbHelper,
        TokenService $tokenService,
        ExceptionHandler $exceptionHandler,
        LoggerInterface $authLogger,
        LoggerInterface $auditLogger,
        array $encryptionConfig,
        Validator $validator
    ) {
        $this->pdo = $dbHelper->getPdo();
        $this->tokenService = $tokenService;
        $this->exceptionHandler = $exceptionHandler;
        $this->authLogger = $authLogger;
        $this->auditLogger = $auditLogger;
        $this->encryptionConfig = $encryptionConfig;
        $this->validator = $validator;

        $this->authLogger->info("AuthService initialized with app_database connection");
    }

    public function login(array $data)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($data['password'], $user['password_hash'])) {
                $this->authLogger->warning("Authentication failed", ['email' => $data['email']]);
                throw new Exception("Invalid credentials", 401);
            }

            // Cast user array to object for TokenService
            $userObject = (object)$user;
            $this->authLogger->debug("User data converted to object", ['type' => gettype($userObject)]);

            $token = $this->tokenService->generateToken($userObject);
            $refreshToken = $this->tokenService->generateRefreshToken($userObject);

            // Include minimal user information in the result
            return [
                'token'         => $token,
                'refresh_token' => $refreshToken,
                'user_id'       => $user['id'],
                'name'          => $user['name'],
                'email'         => $user['email']
            ];
        } catch (Exception $e) {
            $this->authLogger->error("[auth] ❌ Login error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    public function register(array $data)
    {
        try {
            $this->authLogger->info("Starting registration process", ['email' => $data['email'] ?? 'unknown']);
            
            $rules = [
                'email'           => 'required|email|unique:users,email',
                'password'        => 'required|min:8',
                'confirm_password'=> 'required|same:password',
                'name'            => 'required|string',
                'surname'         => 'required|string',
                'phone'           => 'string'
            ];

            // Log sanitized input data (without passwords)
            $logData = $data;
            if (isset($logData['password'])) unset($logData['password']);
            if (isset($logData['confirm_password'])) unset($logData['confirm_password']);
            $this->authLogger->debug("Registration input data", ['data' => $logData]);
            
            // Validate input data
            $this->validator->validate($data, $rules);
            
            // Check passwords match
            if (!isset($data['password']) || !isset($data['confirm_password']) || $data['password'] !== $data['confirm_password']) {
                $this->authLogger->warning("Passwords don't match during registration");
                throw new Exception("Passwords do not match", 400);
            }
            
            // Prepare user data for database insertion
            $userData = [
                'name' => $data['name'],
                'surname' => $data['surname'],
                'email' => $data['email'],
                'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
                'phone' => $data['phone'] ?? null,
                'role' => $data['role'] ?? 'user',
                'status' => $data['status'] ?? 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Log prepared data (without password_hash)
            $logUserData = $userData;
            unset($logUserData['password_hash']);
            $this->authLogger->debug("Prepared user data for database", ['data' => $logUserData]);
            
            // Insert user into database
            $columns = implode(', ', array_keys($userData));
            $placeholders = implode(', ', array_fill(0, count($userData), '?'));
            
            $stmt = $this->pdo->prepare("INSERT INTO users ({$columns}) VALUES ({$placeholders})");
            $stmt->execute(array_values($userData));
            $userId = $this->pdo->lastInsertId();
            
            $this->authLogger->info("User registered successfully", ['user_id' => $userId, 'email' => $data['email']]);
            $this->auditLogger->info("New user registration", ['user_id' => $userId, 'email' => $data['email']]);
            
            return ['user_id' => $userId];
        } catch (\InvalidArgumentException $e) {
            $this->authLogger->warning("Validation error during registration", ['error' => $e->getMessage()]);
            throw $e;
        } catch (Exception $e) {
            $this->authLogger->error("Registration error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    public function refresh(array $data)
    {
        try {
            if (!isset($data['refresh_token'])) {
                $this->authLogger->warning("Missing refresh token");
                throw new Exception("Refresh token is required", 400);
            }
            
            $decoded = JWT::decode($data['refresh_token'], new Key($this->tokenService->jwtSecret, 'HS256'));
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$decoded->sub]);
            $user = $stmt->fetch();

            if (!$user) {
                $this->authLogger->warning("Invalid refresh token", ['token_sub' => $decoded->sub]);
                throw new Exception("Invalid refresh token", 400);
            }

            // Cast user array to object for TokenService
            $userObject = (object)$user;
            $this->authLogger->debug("User data converted to object for token refresh", ['type' => gettype($userObject)]);

            $token = $this->tokenService->generateToken($userObject);
            $this->authLogger->info("Token refreshed successfully", ['user_id' => $user['id']]);
            
            return ['token' => $token];
        } catch (Exception $e) {
            $this->authLogger->error("Refresh token error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    public function logout(array $data)
    {
        $this->auditLogger->info("User logged out");
        return ["message" => "Logged out successfully"];
    }

    public function updateProfile($user, array $data)
    {
        // ...existing code...
    }
}
?>
