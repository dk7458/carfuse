<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Helpers\DatabaseHelper;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
// Removed: use Illuminate\Support\Facades\Hash;
use Exception;
use Psr\Log\LoggerInterface;
use App\Helpers\SecurityHelper; // ✅ Updated: use correct namespace

class AuthService
{
    private $tokenService;
    private $db;
    private LoggerInterface $logger; // added

    // Modified constructor to accept LoggerInterface via dependency injection.
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
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
            $this->logger // now injected logger
        );
        // Initialize DatabaseHelper for raw password reset operations
        $this->db = DatabaseHelper::getInstance();
    }

    public function login($email, $password)
    {
        $user = User::where('email', $email)->first();
        // Use PHP's password_verify() instead of Hash::check()
        if (!$user || !password_verify($password, $user->password_hash)) {
            $this->logger->warning("Failed login attempt for email: " . $email);
            throw new Exception("Invalid credentials");
        }

        // Ensure session is started and set session variables
        if(session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_role'] = $user->role ?? 'user';

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
            $this->logger->info("[AuthService] Password reset requested for {$email}");
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
        try {
            $decoded = JWT::decode($token, new Key($this->tokenService->jwtSecret, 'HS256'));
            return (array)$decoded;
        } catch (Exception $e) {
            $this->logger->error("[AuthService] Invalid token: " . $e->getMessage());
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
