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
    private $db; // this is the Capsule instance from the default DatabaseHelper
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
        // Ensure to use the app database's Capsule instance:
        $this->db = $dbHelper->getAppDatabaseConnection();  // Get the Capsule instance
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
                $this->authLogger->warning("Authentication failed for email: {$email}");
                throw new Exception("Invalid credentials");
            }

            $token = $this->tokenService->generateToken([
                'sub' => $user->id,
                'email' => $user->email,
                'role' => $user->role ?? 'user'
            ]);

            $refreshToken = $this->tokenService->generateRefreshToken([
                'sub' => $user->id
            ]);

            $this->authLogger->info("User authenticated successfully", ['userId' => $user->id]);
            return [
                'token' => $token,
                'refresh_token' => $refreshToken
            ];
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    public function registerUser(array $data)
    {
        try {
            // Validate registration data
            $rules = [
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:8',
                'name' => 'required|string',
            ];

            $this->validator->validate($data, $rules);

            // Hash the password
            $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
            unset($data['password']);  // Remove plaintext password

            // Insert into app database
            $userId = $this->db->table('users')->insertGetId($data);

            $this->authLogger->info("User registered successfully", ['email' => $data['email']]);
            return ['userId' => $userId];
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    public function resetPasswordRequest($email)
    {
        try {
            // Check if user exists in app database
            $user = $this->db->table('users')->where('email', $email)->first();
            if (!$user) {
                throw new Exception("Email not found");
            }

            // Generate reset token
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour from now

            // Store in app database
            $this->db->table('password_resets')->insert([
                'email' => $email,
                'token' => password_hash($token, PASSWORD_BCRYPT), // Store hashed token
                'expires_at' => $expiresAt
            ]);

            $this->authLogger->info("Password reset requested", ['email' => $email]);

            // Return token (in production, would send via email)
            return ['token' => $token, 'expires_at' => $expiresAt];
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    public function resetPassword($email, $token, $newPassword)
    {
        try {
            // Get reset record from app database
            $resetRecord = $this->db->table('password_resets')
                ->where('email', $email)
                ->where('expires_at', '>', date('Y-m-d H:i:s'))
                ->first();

            if (!$resetRecord || !password_verify($token, $resetRecord->token)) {
                throw new Exception("Invalid or expired token");
            }

            // Update password in app database
            $this->db->table('users')
                ->where('email', $email)
                ->update(['password_hash' => password_hash($newPassword, PASSWORD_BCRYPT)]);

            // Delete used token
            $this->db->table('password_resets')->where('email', $email)->delete();

            $this->authLogger->info("Password reset completed", ['email' => $email]);
            return true;
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    public function getValidator()
    {
        return $this->validator;
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
        // Any additional logout logic if needed
        return true;
    }
}
?>
