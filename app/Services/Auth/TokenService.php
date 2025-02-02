<?php

namespace App\Services\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;

class TokenService
{
    private $secretKey;
    private $refreshSecretKey;

    public function __construct()
    {
        $this->secretKey = env('JWT_SECRET');
        $this->refreshSecretKey = env('JWT_REFRESH_SECRET');
    }

    public function generateToken($user)
    {
        $payload = [
            'iss' => "your-issuer", // Issuer
            'sub' => $user->id, // Subject
            'iat' => time(), // Issued at
            'exp' => time() + 3600 // Expiration time
        ];

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }

    public function verifyToken($token)
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            return (array) $decoded;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function generateRefreshToken($user)
    {
        $payload = [
            'iss' => "your-issuer",
            'sub' => $user->id,
            'iat' => time(),
            'exp' => time() + 604800 // 1 week
        ];

        return JWT::encode($payload, $this->refreshSecretKey, 'HS256');
    }

    public function refreshAccessToken($refreshToken)
    {
        $decoded = $this->verifyToken($refreshToken, $this->refreshSecretKey);
        if ($decoded) {
            $userId = $decoded['sub'];
            // Check if the refresh token is revoked
            if (Cache::has("revoked_refresh_token_$refreshToken")) {
                return false;
            }
            // Generate new access token
            return $this->generateToken((object) ['id' => $userId]);
        }
        return false;
    }

    public function revokeToken($token)
    {
        Cache::put("revoked_refresh_token_$token", true, 604800); // 1 week
    }
}
