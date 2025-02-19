<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Helpers\DatabaseHelper;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;
use App\Helpers\SecurityHelper;
use function getLogger;

class AuthService
{
    private $tokenService;
    private $db;

    // Removed logger property and dependency injection.
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
            // Use category-based logging
            getLogger('auth')
        );
        $this->db = DatabaseHelper::getInstance();
    }

    public function login($email, $password)
    {
        $user = User::where('email', $email)->first();
        if (!$user || !password_verify($password, $user->password_hash)) {
            getLogger('auth')->warning("[Auth] Failed login attempt for email: {$email}");
            throw new Exception("Invalid credentials");
        }

        if(session_status() === PHP_SESSION_NONE) {
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
    }

    public function registerUser(array $data)
    {
        $user = User::create($data);
        if (!$user) {
            throw new Exception("User registration failed");
        }
        getLogger('auth')->info("[Auth] ✅ User successfully registered (Email: {$data['email']})");
        return $user;
    }

    public function resetPasswordRequest($email)
    {
        $user = User::where('email', $email)->first();
        if (!$user) {
            getLogger('auth')->error("[Auth] Password reset failed: email not found ({$email})");
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
            getLogger('auth')->error("[Auth] Failed to insert password reset: " . $e->getMessage());
            throw $e;
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
            getLogger('auth')->error("[Auth] Invalid token: " . $e->getMessage());
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
    }
}
?>
