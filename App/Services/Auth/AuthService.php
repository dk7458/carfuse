<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Helpers\SecurityHelper;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class AuthService
{
    private $tokenService;

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
            $encryptionConfig['jwt_refresh_secret']
        );
    }

    public function login($email, $password)
    {
        $user = User::where('email', $email)->first();
        if (!$user || !password_verify($password, $user->password_hash)) {
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
        $data['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT);
        unset($data['password']);

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
            throw new Exception("Email not found.");
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        Capsule::table('password_resets')->insert([
            'email' => $email,
            'token' => password_hash($token, PASSWORD_BCRYPT),
            'expires_at' => $expiresAt,
            'created_at' => date('Y-m-d H:i:s')
        ]);

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
