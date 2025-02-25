<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Helpers\DatabaseHelper;
use Firebase\JWT\JWT;
use App\Helpers\ExceptionHandler;
use Firebase\JWT\Key;
use Exception;
use App\Helpers\SecurityHelper;
use Psr\Log\LoggerInterface;
use App\Helpers\ApiHelper;
use App\Services\Validator;

class AuthService
{
    private $db;
    private TokenService $tokenService;
    private ExceptionHandler $exceptionHandler;
    private LoggerInterface $authLogger;
    private LoggerInterface $auditLogger;
    private array $encryptionConfig;
    private Validator $validator;

    public function __construct(
        DatabaseHelper $dbHelper,  // Ensure DatabaseHelper is injected
        TokenService $tokenService,
        ExceptionHandler $exceptionHandler,
        LoggerInterface $authLogger,
        LoggerInterface $auditLogger,
        array $encryptionConfig,
        Validator $validator // Inject Validator
    ) {
        $this->db = $dbHelper->getCapsule();  // Get the Capsule instance
        $this->tokenService = $tokenService;
        $this->exceptionHandler = $exceptionHandler;
        $this->authLogger = $authLogger;
        $this->auditLogger = $auditLogger;
        $this->encryptionConfig = $encryptionConfig;
        $this->validator = $validator; // Initialize Validator
    }

    public function login($email, $password)
    {
        try {
            // Use injected DatabaseHelper to query the user
            $user = $this->db->table('users')->where('email', $email)->first();
            if (!$user || !password_verify($password, $user->password_hash)) {
                $this->authLogger->warning("Authentication failed", ['email' => $email]);
                throw new Exception("Invalid credentials");
            }
            if (self::DEBUG_MODE) {
                $this->authLogger->info("[auth] User authenticated", ['userId' => $user->id, 'email' => $user->email]);
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
            // Optionally use ApiHelper to send an error response
            ApiHelper::sendJsonResponse('error', $e->getMessage(), [], 401);
        }
    }

    public function registerUser(array $data)
    {
        $rules = [
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'name'     => 'required|string',
        ];

        try {
            // Validate the input data
            $this->validator->validate($data, $rules);

            // Hash the password before storing it
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);

            // Insert the user into the database
            $userId = $this->db->table('users')->insertGetId($data);
            return ApiHelper::sendJsonResponse('success', 'User registered', ['user_id' => $userId], 201);
        } catch (\InvalidArgumentException $e) {
            return ApiHelper::sendJsonResponse('error', 'Validation failed', json_decode($e->getMessage(), true), 400);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
        }
    }

    public function resetPasswordRequest($email)
    {
        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->authLogger->error("Password reset failed: email not found", ['email' => $email]);
            throw new Exception("Email not found.");
        }

        $token = bin2hex(random_bytes(32));
        $hashedToken = password_hash($token, PASSWORD_BCRYPT);
        $expiresAt = now()->addHour();

        try {
            $this->db->table('password_resets')->insert([
                'email'      => $email,
                'token'      => $hashedToken,
                'expires_at' => $expiresAt,
            ]);
            getLogger('auth')->info("[Auth] Password reset requested for {$email}");
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
        }

        // Send email (mock implementation)
        // ...existing code...

        return ['token' => $token];
    }

    public function validateToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->tokenService->jwtSecret, 'HS256'));
            return (array)$decoded;
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            return false;
        }
    }

    public function getUserFromToken($token)
    {
        $decoded = $this->tokenService->validateToken($token);
        if (!$decoded) {
            throw new Exception("Invalid token.");
        }

        return User::find($decoded['sub']);
    }

    public function refreshToken($refreshToken)
    {
        try {
            $decoded = JWT::decode($refreshToken, new Key($this->tokenService->jwtSecret, 'HS256'));
            $user = User::find($decoded->sub);
            if (!$user) {
                throw new Exception("Invalid refresh token.");
            }

            $token = $this->tokenService->generateToken($user);
            return ['token' => $token];
        } catch (Exception $e) {
            $this->authLogger->error("[auth] ❌ Refresh token error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            ApiHelper::sendJsonResponse('error', $e->getMessage(), [], 401);
        }
    }

    public function logout()
    {
        // No session management; tokens are stateless
        $this->auditLogger->info("User logged out");
    }
}
?>
