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

class AuthService
{
    private $db;
    private TokenService $tokenService;
    private ExceptionHandler $exceptionHandler;
    private LoggerInterface $authLogger;
    private LoggerInterface $auditLogger;
    private array $encryptionConfig;

    public function __construct(
        DatabaseHelper $dbHelper,  // Ensure DatabaseHelper is injected
        TokenService $tokenService,
        ExceptionHandler $exceptionHandler,
        LoggerInterface $authLogger,
        LoggerInterface $auditLogger,
        array $encryptionConfig
    ) {
        $this->db = $dbHelper->getCapsule();  // Get the Capsule instance
        $this->tokenService = $tokenService;
        $this->exceptionHandler = $exceptionHandler;
        $this->authLogger = $authLogger;
        $this->auditLogger = $auditLogger;
        $this->encryptionConfig = $encryptionConfig;
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

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user->id;
            $_SESSION['user_role'] = $user->role ?? 'user';

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
        try {
            // Using injected DatabaseHelper or User model as preferred
            $user = User::create($data);
            if (!$user) {
                throw new Exception("User registration failed");
            }
            $this->authLogger->info("User successfully registered", ['email' => $data['email'], 'userId' => $user->id]);
            // Standardize API response via ApiHelper
            ApiHelper::sendJsonResponse('success', 'User registered', ['user_id' => $user->id], 201);
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            ApiHelper::sendJsonResponse('error', $e->getMessage(), [], 500);
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

    public function logout()
    {
        // Clear session data and session cookie for secure logout
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            setcookie(session_name(), '', time() - 42000, '/');
        }
        session_destroy();
        $this->auditLogger->info("User logged out", ['session_id' => session_id()]);
    }
}
?>
