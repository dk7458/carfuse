<?php

namespace App\Services\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;
use App\Helpers\ApiHelper;

class TokenService
{
    private string $jwtSecret;
    private string $refreshSecret;
    private int $jwtTtl;
    private int $refreshTokenTtl;
    private LoggerInterface $authLogger;
    private ExceptionHandler $exceptionHandler;

    public function __construct(
        string $jwtSecret,
        string $refreshSecret,
        int $jwtTtl = 3600,
        int $refreshTokenTtl = 604800,
        LoggerInterface $authLogger,
        ExceptionHandler $exceptionHandler
    ) {
        $this->jwtSecret = $jwtSecret;
        $this->refreshSecret = $refreshSecret;
        $this->jwtTtl = $jwtTtl;
        $this->refreshTokenTtl = $refreshTokenTtl;
        $this->authLogger = $authLogger;
        $this->exceptionHandler = $exceptionHandler;
    }

    /**
     * Generate a new JWT token
     * 
     * @param array $userData User data to include in token
     * @return string JWT token
     */
    public function generateToken(array $userData): string
    {
        try {
            $issuedAt = time();
            $expires = $issuedAt + $this->jwtTtl;
            
            $payload = [
                'iss' => 'carfuse_api',  // issuer
                'iat' => $issuedAt,      // issued at
                'exp' => $expires,       // expiration
                'sub' => $userData['sub'] ?? $userData['id'] ?? null,  // subject (user ID)
                'email' => $userData['email'] ?? null,
                'role' => $userData['role'] ?? 'user'
            ];
            
            return JWT::encode($payload, $this->jwtSecret, 'HS256');
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            throw new Exception("Token generation failed: " . $e->getMessage());
        }
    }

    /**
     * Generate a refresh token
     * 
     * @param array $userData User data
     * @return string Refresh token
     */
    public function generateRefreshToken(array $userData): string
    {
        try {
            $issuedAt = time();
            $expires = $issuedAt + $this->refreshTokenTtl;
            
            $payload = [
                'iss' => 'carfuse_api',  // issuer
                'iat' => $issuedAt,      // issued at
                'exp' => $expires,       // expiration
                'sub' => $userData['sub'] ?? $userData['id'] ?? null,  // subject (user ID)
                'type' => 'refresh'
            ];
            
            return JWT::encode($payload, $this->refreshSecret, 'HS256');
        } catch (Exception $e) {
            $this->exceptionHandler->handleException($e);
            throw new Exception("Refresh token generation failed: " . $e->getMessage());
        }
    }

    /**
     * Refresh an access token using a refresh token
     * 
     * @param string $refreshToken The refresh token
     * @return string|null New access token or null if invalid
     */
    public function refreshToken(string $refreshToken): ?string
    {
        try {
            // Verify the refresh token
            $decoded = JWT::decode($refreshToken, new Key($this->refreshSecret, 'HS256'));
            
            // Check if token is intended for refresh
            if (!isset($decoded->type) || $decoded->type !== 'refresh') {
                $this->authLogger->warning("Invalid refresh token type");
                return null;
            }
            
            // Check if token is revoked
            if ($this->isTokenRevoked($refreshToken)) {
                $this->authLogger->warning("Attempted to use revoked refresh token", ['sub' => $decoded->sub ?? 'unknown']);
                return null;
            }
            
            // Generate new access token
            return $this->generateToken([
                'sub' => $decoded->sub,
                'role' => $decoded->role ?? 'user'
            ]);
        } catch (Exception $e) {
            $this->authLogger->warning("Refresh token verification failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Validate a JWT token
     * 
     * @param string $token JWT token
     * @return array|null Decoded token data or null if invalid
     */
    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->jwtSecret, 'HS256'));
            
            // Check if token is revoked
            if ($this->isTokenRevoked($token)) {
                $this->authLogger->warning("Attempted to use revoked token", ['sub' => $decoded->sub ?? 'unknown']);
                return null;
            }
            
            return (array)$decoded;
        } catch (Exception $e) {
            $this->authLogger->warning("Token validation failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Decode a token without validation (for getting data from expired tokens)
     * 
     * @param string $token JWT token
     * @return array|null Decoded token data or null if invalid
     */
    public function decodeToken(string $token): ?array
    {
        try {
            list($header, $payload, $signature) = explode('.', $token);
            $decodedPayload = json_decode(base64_decode($payload), true);
            return $decodedPayload;
        } catch (Exception $e) {
            $this->authLogger->warning("Token decode failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Revoke a token (add to blacklist)
     * 
     * @param string $token JWT token
     * @return void
     */
    public function revokeToken(string $token): void
    {
        try {
            // Get token ID or data to identify it
            $decoded = $this->decodeToken($token);
            if (!$decoded) {
                return;
            }

            $tokenId = isset($decoded['jti']) ? $decoded['jti'] : md5($token);
            $expiration = $decoded['exp'] ?? (time() + $this->refreshTokenTtl);
            
            // Store in blacklist - using apcu as temporary storage
            // In production, you'd use Redis or a database
            $ttl = max(0, $expiration - time());
            apcu_store("revoked_token:{$tokenId}", true, $ttl);
            
            $this->authLogger->info("Token revoked successfully", [
                'userId' => $decoded['sub'] ?? 'unknown',
                'exp' => date('Y-m-d H:i:s', $expiration)
            ]);
        } catch (Exception $e) {
            $this->authLogger->error("Failed to revoke token: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
        }
    }

    /**
     * Check if a token is revoked
     * 
     * @param string $token JWT token
     * @return bool True if revoked, false otherwise
     */
    private function isTokenRevoked(string $token): bool
    {
        try {
            $decoded = $this->decodeToken($token);
            if (!$decoded) {
                return true;
            }
            
            $tokenId = isset($decoded['jti']) ? $decoded['jti'] : md5($token);
            return apcu_exists("revoked_token:{$tokenId}");
        } catch (Exception $e) {
            $this->authLogger->error("Error checking if token is revoked: " . $e->getMessage());
            return true; // Fail closed for security
        }
    }
}
