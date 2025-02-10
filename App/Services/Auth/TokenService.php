<?php

namespace App\Services\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;

class TokenService
{
    private string $secretKey;
    private string $refreshSecretKey;

    public function __construct(string $secretKey, string $refreshSecretKey)
    {
        if (empty($secretKey) || empty($refreshSecretKey)) {
            throw new \RuntimeException('❌ JWT secrets are missing.');
        }

        $this->secretKey = $secretKey;
        $this->refreshSecretKey = $refreshSecretKey;
    }

    public function generateToken($user): string
    {
        $payload = [
            'iss' => "your-issuer",
            'sub' => $user->id,
            'iat' => time(),
            'exp' => time() + 3600
        ];

        return JWT::encode($payload, $this->secretKey, 'HS256');
    }

    public function verifyToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            return (array) $decoded;
        } catch (\Exception $e) {
            return null;
        }
    }

    // New method: validateToken() to reject expired tokens and log failures
    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            if ($decoded->exp < time()) {
                \logAuthFailure("Expired token for user id: " . ($decoded->sub ?? 'unknown'));
                return null;
            }
            return (array)$decoded;
        } catch (\Exception $e) {
            \logAuthFailure("Token validation failed: " . $e->getMessage());
            return null;
        }
    }

    public function generateRefreshToken($user): string
    {
        $payload = [
            'iss' => "your-issuer",
            'sub' => $user->id,
            'iat' => time(),
            'exp' => time() + 604800
        ];

        return JWT::encode($payload, $this->refreshSecretKey, 'HS256');
    }

    public function refreshAccessToken(string $refreshToken): ?string
    {
        $decoded = $this->verifyToken($refreshToken);
        if ($decoded) {
            $userId = $decoded['sub'];

            if (Cache::has("revoked_refresh_token_$refreshToken")) {
                return null;
            }

            return $this->generateToken((object) ['id' => $userId]);
        }
        return null;
    }

    // New method: refreshToken() to securely generate a new access token using a valid refresh token
    public function refreshToken(string $refreshToken): ?string
    {
        try {
            $decoded = JWT::decode($refreshToken, new Key($this->refreshSecretKey, 'HS256'));
            if ($decoded->exp < time()) {
                \logAuthFailure("Expired refresh token for user id: " . ($decoded->sub ?? 'unknown'));
                return null;
            }
            if (\Illuminate\Support\Facades\Cache::has("revoked_refresh_token_$refreshToken")) {
                \logAuthFailure("Revoked refresh token attempted for user id: " . ($decoded->sub ?? 'unknown'));
                return null;
            }
            return $this->generateToken((object)['id' => $decoded->sub]);
        } catch (\Exception $e) {
            \logAuthFailure("Refresh token validation failed: " . $e->getMessage());
            return null;
        }
    }

    public function revokeToken(string $token): void
    {
        Cache::put("revoked_refresh_token_$token", true, 604800);
    }
}
