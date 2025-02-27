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

        // Log the database connection being used
        $this->authLogger->info("Using database connection from app_database.");
    }

    public function login(array $data)
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            $user = $stmt->fetch();

            if (!$user || !password_verify($data['password'], $user['password_hash'])) {
                $this->authLogger->warning("Authentication failed", ['email' => $data['email']]);
                throw new InvalidCredentialsException("Invalid credentials");
            }

            $token = $this->tokenService->generateToken($user);
            $refreshToken = $this->tokenService->generateRefreshToken($user);

            return [
                'token'         => $token,
                'refresh_token' => $refreshToken
            ];
        } catch (Exception $e) {
            $this->authLogger->error("[auth] ❌ Credential error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    public function register(array $data)
    {
        $rules = [
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'name'     => 'required|string',
        ];

        try {
            $this->validator->validate($data, $rules);
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);

            $stmt = $this->pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$data['name'], $data['email'], $data['password']]);
            $userId = $this->pdo->lastInsertId();

            return ['user_id' => $userId];
        } catch (\InvalidArgumentException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    public function refresh(array $data)
    {
        try {
            $decoded = JWT::decode($data['refresh_token'], new Key($this->tokenService->jwtSecret, 'HS256'));
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$decoded->sub]);
            $user = $stmt->fetch();

            if (!$user) {
                throw new Exception("Invalid refresh token.");
            }

            $token = $this->tokenService->generateToken($user);
            return ['token' => $token];
        } catch (Exception $e) {
            $this->authLogger->error("[auth] ❌ Refresh token error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    public function logout(array $data)
    {
        $this->auditLogger->info("User logged out");
    }

    public function updateProfile($user, array $data)
    {
        // ...update profile logic...
    }
}
?>
