<?php

namespace App\Services\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;
use App\Helpers\ApiHelper;

class TokenService
{
    public const DEBUG_MODE = true;

    private string $jwtSecret;
    private string $jwtRefreshSecret;
    private LoggerInterface $tokenLogger;
    private ExceptionHandler $exceptionHandler;

    public function __construct(
        string $jwtSecret,
        string $jwtRefreshSecret,
        LoggerInterface $tokenLogger,
        ExceptionHandler $exceptionHandler
    ) {
        $this->jwtSecret = $jwtSecret;
        $this->jwtRefreshSecret = $jwtRefreshSecret;
        if (empty($this->jwtSecret) || empty($this->jwtRefreshSecret)) {
            throw new \RuntimeException('❌ JWT secrets are missing.');
        }
        $this->tokenLogger = $tokenLogger;
        $this->exceptionHandler = $exceptionHandler;
        if (self::DEBUG_MODE) {
            $this->tokenLogger->info("[auth] TokenService initialized.");
        }
    }

    public function generateToken($user)
    {
        $payload = [
            'iss' => "your-issuer",
            'sub' => $user->id,
            'iat' => time(),
            'exp' => time() + 3600
        ];
        try {
            $token = JWT::encode($payload, $this->jwtSecret, 'HS256');
            if (self::DEBUG_MODE) {
                $this->tokenLogger->info("[auth] ✅ Token generated.", ['userId' => $user->id]);
            }
            return ApiHelper::sendJsonResponse('success', 'Token generated', ['token' => $token]);
        } catch (\Exception $e) {
            $this->tokenLogger->error("[auth] ❌ Token generation failed: " . $e->getMessage());
            return $this->exceptionHandler->handleException($e);
        }
    }

    public function verifyToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            $this->tokenLogger->info("✅ Token verified.", ['userId' => $decoded->sub]);
            return (array)$decoded;
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return null;
        }
    }

    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            if ($decoded->exp < time()) {
                $this->tokenLogger->error("❌ [TokenService] Expired token.", ['userId' => $decoded->sub ?? 'unknown']);
                return null;
            }
            return (array)$decoded;
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
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
        try {
            return JWT::encode($payload, $this->jwtRefreshSecret, 'HS256');
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return '';
        }
    }

    public function refreshAccessToken(string $refreshToken): ?string
    {
        $decoded = $this->verifyToken($refreshToken);
        if ($decoded) {
            $userId = $decoded['sub'];
            if (apcu_exists("revoked_refresh_token_$refreshToken")) {
                return null;
            }
            return $this->generateToken((object)['id' => $userId]);
        }
        return null;
    }

    public function refreshToken(string $refreshToken): ?string
    {
        try {
            $decoded = JWT::decode($refreshToken, new Key($this->jwtRefreshSecret, 'HS256'));
            if ($decoded->exp < time()) {
                $this->tokenLogger->error("❌ [TokenService] Expired refresh token.", ['userId' => $decoded->sub ?? 'unknown']);
                return null;
            }
            if (apcu_exists("revoked_refresh_token_$refreshToken")) {
                $this->tokenLogger->error("❌ [TokenService] Revoked refresh token attempted.", ['userId' => $decoded->sub ?? 'unknown']);
                return null;
            }
            return $this->generateToken((object)['id' => $decoded->sub]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            return null;
        }
    }

    public function revokeToken(string $token): void
    {
        apcu_store("revoked_refresh_token_$token", true, 604800);
        $this->tokenLogger->info("✅ [TokenService] Revoked refresh token.");
    }
}
