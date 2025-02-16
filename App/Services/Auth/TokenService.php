<?php

namespace App\Services\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
// Removed: use Illuminate\Support\Facades\Cache;
use Psr\Log\LoggerInterface;

class TokenService
{
    private string $secretKey;
    private string $refreshSecretKey;
    private LoggerInterface $logger;

    public function __construct(string $secretKey, string $refreshSecretKey, LoggerInterface $logger)
    {
        if (empty($secretKey) || empty($refreshSecretKey)) {
            throw new \RuntimeException('❌ JWT secrets are missing.');
        }

        $this->secretKey = $secretKey;
        $this->refreshSecretKey = $refreshSecretKey;
        $this->logger = $logger;
    }

    public function generateToken($user): string
    {
        $payload = [
            'iss' => "your-issuer",
            'sub' => $user->id,
            'iat' => time(),
            'exp' => time() + 3600
        ];
        $this->logger->info("[TokenService] Generated token for user id: {$user->id}");
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

    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            if ($decoded->exp < time()) {
                error_log("Expired token for user id: " . ($decoded->sub ?? 'unknown'));
                return null;
            }
            return (array)$decoded;
        } catch (\Exception $e) {
            error_log("Token validation failed: " . $e->getMessage());
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

            if (apcu_exists("revoked_refresh_token_$refreshToken")) {
                return null;
            }

            return $this->generateToken((object) ['id' => $userId]);
        }
        return null;
    }

    public function refreshToken(string $refreshToken): ?string
    {
        try {
            $decoded = JWT::decode($refreshToken, new Key($this->refreshSecretKey, 'HS256'));
            if ($decoded->exp < time()) {
                error_log("Expired refresh token for user id: " . ($decoded->sub ?? 'unknown'));
                return null;
            }
            if (apcu_exists("revoked_refresh_token_$refreshToken")) {
                error_log("Revoked refresh token attempted for user id: " . ($decoded->sub ?? 'unknown'));
                return null;
            }
            return $this->generateToken((object)['id' => $decoded->sub]);
        } catch (\Exception $e) {
            error_log("Refresh token validation failed: " . $e->getMessage());
            return null;
        }
    }

    public function revokeToken(string $token): void
    {
        apcu_store("revoked_refresh_token_$token", true, 604800);
    }
}
