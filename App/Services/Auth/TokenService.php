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

    public function revokeToken(string $token): void
    {
        Cache::put("revoked_refresh_token_$token", true, 604800);
    }
}
