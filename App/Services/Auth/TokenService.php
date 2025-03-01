<?php

namespace App\Services\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;
use App\Helpers\DatabaseHelper;
use App\Services\AuditService;

class TokenService
{
    public const DEBUG_MODE = true;

    private string $jwtSecret;
    private string $jwtRefreshSecret;
    private LoggerInterface $logger;
    private ExceptionHandler $exceptionHandler;
    private DatabaseHelper $db;
    private AuditService $auditService;

    public function __construct(
        string $jwtSecret,
        string $jwtRefreshSecret,
        LoggerInterface $logger,
        ExceptionHandler $exceptionHandler,
        DatabaseHelper $db,
        AuditService $auditService
    ) {
        $this->jwtSecret = $jwtSecret;
        $this->jwtRefreshSecret = $jwtRefreshSecret;
        if (empty($this->jwtSecret) || empty($this->jwtRefreshSecret)) {
            throw new \RuntimeException('❌ JWT secrets are missing.');
        }
        $this->logger = $logger;
        $this->exceptionHandler = $exceptionHandler;
        $this->db = $db;
        $this->auditService = $auditService;
        
        if (self::DEBUG_MODE) {
            $this->logger->info("[auth] TokenService initialized.");
        }
    }

    public function generateToken($user): string
    {
        // Extract user ID safely from either array or object
        $userId = is_array($user) ? $user['id'] : $user->id;

        $payload = [
            'iss' => "your-issuer",
            'sub' => $userId,
            'iat' => time(),
            'exp' => time() + 3600
        ];
        try {
            $token = JWT::encode($payload, $this->jwtSecret, 'HS256');
            if (self::DEBUG_MODE) {
                $this->logger->info("[auth] ✅ Token generated.", ['userId' => $userId]);
            }
            
            // Log JWT creation as a business-level event in audit trail
            $this->auditService->logEvent(
                'auth',
                'jwt_created',
                ['user_id' => $userId],
                $userId,
                null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );
            
            return $token;
        } catch (\Exception $e) {
            $this->logger->error("[auth] ❌ Token generation failed: " . $e->getMessage());
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
            $this->logger->info("✅ Token verified.", ['userId' => $decoded->sub]);
            return (array)$decoded;
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    public function generateRefreshToken($user): string
    {
        // Extract user ID safely from either array or object
        $userId = is_array($user) ? $user['id'] : $user->id;

        $payload = [
            'iss' => "your-issuer",
            'sub' => $userId,
            'iat' => time(),
            'exp' => time() + 604800
        ];
        try {
            $refreshToken = JWT::encode($payload, $this->jwtRefreshSecret, 'HS256');
            
            // Store the refresh token in database using DatabaseHelper
            $this->storeRefreshToken($userId, $refreshToken);
            
            return $refreshToken;
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }
    
    /**
     * Store refresh token in database
     */
    private function storeRefreshToken(int $userId, string $refreshToken): void
    {
        try {
            // Store the token in the refresh_tokens table using db helper
            $this->db->insert('refresh_tokens', [
                'user_id' => $userId,
                'token' => hash('sha256', $refreshToken), // Store hashed token for security
                'expires_at' => date('Y-m-d H:i:s', time() + 604800),
                'created_at' => date('Y-m-d H:i:s'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);
            
            if (self::DEBUG_MODE) {
                $this->logger->info("[auth] Refresh token stored in database", ['user_id' => $userId]);
            }
        } catch (\Exception $e) {
            $this->logger->error("[auth] Failed to store refresh token: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            // Continue without failing - JWT will still work even if storage fails
        }
    }

    /**
     * Decode and validate a refresh token
     *
     * @param string $refreshToken The refresh token to decode
     * @return object The decoded token payload
     * @throws \Exception If token is invalid or expired
     */
    public function decodeRefreshToken(string $refreshToken)
    {
        try {
            // Check if token has been revoked
            if ($this->isTokenRevoked($refreshToken)) {
                throw new \Exception("Refresh token has been revoked.");
            }
            
            $decoded = JWT::decode($refreshToken, new Key($this->jwtRefreshSecret, 'HS256'));
            
            if ($decoded->exp < time()) {
                throw new \Exception("Refresh token has expired.");
            }
            
            $this->logger->debug("Refresh token decoded successfully", ['sub' => $decoded->sub]);
            return $decoded;
        } catch (\Exception $e) {
            $this->logger->error("Failed to decode refresh token: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }
    
    /**
     * Check if a token has been revoked
     */
    private function isTokenRevoked(string $refreshToken): bool
    {
        try {
            // Check cache first for performance
            if (apcu_exists("revoked_refresh_token_$refreshToken")) {
                return true;
            }
            
            // If not in cache, check database
            $hashedToken = hash('sha256', $refreshToken);
            $query = "SELECT 1 FROM refresh_tokens WHERE token = :token AND revoked = 1 LIMIT 1";
            $revoked = $this->db->select($query, [':token' => $hashedToken]);
                
            // If revoked in database, store in cache for next time
            if ($revoked) {
                apcu_store("revoked_refresh_token_$refreshToken", true, 604800);
            }
            
            return !empty($revoked);
        } catch (\Exception $e) {
            $this->logger->warning("Error checking if token is revoked: " . $e->getMessage());
            // Default to not revoked if there's an error checking, but log it
            return false;
        }
    }

    public function refreshToken(string $refreshToken): string
    {
        try {
            $decoded = $this->decodeRefreshToken($refreshToken);
            
            // Generate a new access token
            $newToken = $this->generateToken((object)['id' => $decoded->sub]);
            
            // Log token refresh as a business event
            $this->auditService->logEvent(
                'auth',
                'jwt_refreshed',
                ['user_id' => $decoded->sub],
                $decoded->sub,
                null,
                $_SERVER['REMOTE_ADDR'] ?? null
            );
            
            return $newToken;
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            throw $e;
        }
    }

    public function revokeToken(string $token): void
    {
        try {
            // Store in cache for quick lookups
            apcu_store("revoked_refresh_token_$token", true, 604800);
            
            // Store in database for persistence
            $hashedToken = hash('sha256', $token);
            
            // Update the token status in database using db helper
            $this->db->update('refresh_tokens', [
                'revoked' => 1,
                'revoked_at' => date('Y-m-d H:i:s')
            ], ['token' => $hashedToken]);
                
            // Try to get the user ID for audit logging
            $query = "SELECT user_id FROM refresh_tokens WHERE token = :token LIMIT 1";
            $tokenData = $this->db->select($query, [':token' => $hashedToken]);
            
            $userId = $tokenData[0]['user_id'] ?? null;
            
            // Log token revocation as a business event
            if ($userId) {
                $this->auditService->logEvent(
                    'auth',
                    'token_revoked',
                    [],
                    $userId,
                    null,
                    $_SERVER['REMOTE_ADDR'] ?? null
                );
            }
            
            $this->logger->info("✅ [TokenService] Revoked refresh token.");
        } catch (\Exception $e) {
            $this->logger->error("Failed to revoke token: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
        }
    }
    
    /**
     * Remove expired tokens from the database
     */
    public function purgeExpiredTokens(): int
    {
        try {
            $query = "DELETE FROM refresh_tokens WHERE expires_at < :now";
            $count = $this->db->update($query, [':now' => date('Y-m-d H:i:s')]);
                
            $this->logger->info("[TokenService] Purged {$count} expired tokens");
            return $count;
        } catch (\Exception $e) {
            $this->logger->error("Failed to purge expired tokens: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            return 0;
        }
    }
    
    /**
     * Get all active tokens for a user
     */
    public function getActiveTokensForUser(int $userId): array
    {
        try {
            $query = "SELECT * FROM refresh_tokens WHERE user_id = :user_id AND revoked = 0 AND expires_at > :now";
            $tokens = $this->db->select($query, [
                ':user_id' => $userId,
                ':now' => date('Y-m-d H:i:s')
            ]);
                
            return $tokens ?: [];
        } catch (\Exception $e) {
            $this->logger->error("Failed to get active tokens: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
            return [];
        }
    }
}
