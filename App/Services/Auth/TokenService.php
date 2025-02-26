<?php

namespace App\Services\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;

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

    public function generateToken($user): string
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
            return $token;
        } catch (\Exception $e) {
            $this->tokenLogger->error("[auth] ❌ Token generation failed: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    public function verifyToken(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            if ($decoded->exp < time()) {
                throw new \Exception("Token has expired.");
            }
            $this->tokenLogger->info("✅ Token verified.", ['userId' => $decoded->sub]);
            return (array)$decoded;
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            throw $e;
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
            throw $e;
        }
    }

    public function refreshToken(string $refreshToken): string
    {
        try {
            $decoded = JWT::decode($refreshToken, new Key($this->jwtRefreshSecret, 'HS256'));
            if ($decoded->exp < time()) {
                throw new \Exception("Refresh token has expired.");
            }
            if (apcu_exists("revoked_refresh_token_$refreshToken")) {
                throw new \Exception("Refresh token has been revoked.");
            }
            return $this->generateToken((object)['id' => $decoded->sub]);
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    public function revokeToken(string $token): void
    {
        apcu_store("revoked_refresh_token_$token", true, 604800);
        $this->tokenLogger->info("✅ [TokenService] Revoked refresh token.");
    }
}
