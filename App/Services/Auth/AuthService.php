<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Helpers\DatabaseHelper; // added for database operations
use App\Helpers\SecurityHelper;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Hash; // added for password checking
use Exception;
use Psr\Log\NullLogger; // added for logger

class AuthService
{
    private $tokenService;
    private $db;

    public function __construct()
    {
        $configPath = __DIR__ . '/../../../config/encryption.php';
        if (!file_exists($configPath)) {
            throw new Exception("Encryption configuration missing.");
        }

        $encryptionConfig = require $configPath;

        if (!isset($encryptionConfig['jwt_secret'], $encryptionConfig['jwt_refresh_secret'])) {
            throw new Exception("JWT configuration missing in encryption.php.");
        }

        $this->tokenService = new TokenService(
            $encryptionConfig['jwt_secret'],
            $encryptionConfig['jwt_refresh_secret'],
            new NullLogger() // added logger argument
        );
        // Initialize DatabaseHelper for raw password reset operations
        $this->db = DatabaseHelper::getInstance();
    }

    public function login($email, $password)
    {
        $user = User::where('email', $email)->first();
        if (!$user || !Hash::check($password, $user->password_hash)) {
            SecurityHelper::logAuthFailure("Failed login attempt for email: " . $email);
            throw new Exception("Invalid credentials");
        }

        $token = $this->tokenService->generateToken($user);
        $refreshToken = $this->tokenService->generateRefreshToken($user);

        return [
            'token' => $token,
            'refresh_token' => $refreshToken
        ];
    }

    public function registerUser(array $data)
    {
        $user = User::create($data);
        if (!$user) {
            throw new Exception("User registration failed");
        }

        return $user;
    }

    public function resetPasswordRequest($email)
    {
        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->logger?->error("[AuthService] Password reset failed: email not found ($email)");
            throw new Exception("Email not found.");
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = now()->addHour();

        try {
            // Replace Eloquent insert with DatabaseHelper insert
            $this->db->table('password_resets')->insert([
                'email'      => $email,
                'token'      => password_hash($token, PASSWORD_BCRYPT),
                'expires_at' => $expiresAt,
            ]);
            SecurityHelper::logAuthFailure("[AuthService] Password reset requested for {$email}");
        } catch (Exception $e) {
            $this->logger?->error("[AuthService] Failed to insert password reset: " . $e->getMessage());
            throw $e;
        }

        // Send email (mock implementation)
        // ...existing code...

        return ['token' => $token];
    }

    public function validateToken($token)
    {
        return $this->tokenService->validateToken($token);
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
        session_destroy();
    }
}
?>
