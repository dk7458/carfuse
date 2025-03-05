<?php

namespace App\Models;

use App\Helpers\DatabaseHelper;
use Psr\Log\LoggerInterface;
use Exception;

class RefreshToken
{
    private DatabaseHelper $dbHelper;
    private LoggerInterface $logger;
    private bool $useSecureDb = true;

    public function __construct(DatabaseHelper $dbHelper, LoggerInterface $logger)
    {
        $this->dbHelper = $dbHelper;
        $this->logger = $logger;
    }

    /**
     * Store a refresh token in the database
     * 
     * @param int $userId The user ID
     * @param string $refreshToken The unhashed token
     * @param int $expiresIn Expiry time in seconds
     * @return bool Success status
     */
    public function store(int $userId, string $refreshToken, int $expiresIn = 604800): bool
    {
        try {
            // Hash token for secure storage
            $hashedToken = hash('sha256', $refreshToken);
            
            // Store the token in the refresh_tokens table
            $this->dbHelper->insert('refresh_tokens', [
                'user_id' => $userId,
                'token' => $hashedToken,
                'expires_at' => date('Y-m-d H:i:s', time() + $expiresIn),
                'created_at' => date('Y-m-d H:i:s'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
            ], $this->useSecureDb);
            
            $this->logger->info("Refresh token stored", ['user_id' => $userId]);
            return true;
        } catch (Exception $e) {
            $this->logger->error("Failed to store refresh token: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if a token has been revoked
     * 
     * @param string $token The unhashed token
     * @return bool True if revoked
     */
    public function isRevoked(string $token): bool
    {
        try {
            // Check cache first for performance
            if (apcu_exists("revoked_refresh_token_$token")) {
                return true;
            }
            
            // If not in cache, check secure database
            $hashedToken = hash('sha256', $token);
            $query = "SELECT 1 FROM refresh_tokens WHERE token = :token AND revoked = 1 LIMIT 1";
            $revoked = $this->dbHelper->select($query, [':token' => $hashedToken], $this->useSecureDb);
                
            // If revoked in database, store in cache for next time
            if ($revoked) {
                apcu_store("revoked_refresh_token_$token", true, 604800);
            }
            
            return !empty($revoked);
        } catch (Exception $e) {
            $this->logger->warning("Error checking if token is revoked: " . $e->getMessage());
            // Default to not revoked if there's an error checking, but log it
            return false;
        }
    }

    /**
     * Find token data by the token string
     * 
     * @param string $token The unhashed token
     * @return array|null Token data or null if not found
     */
    public function findByToken(string $token): ?array
    {
        try {
            $hashedToken = hash('sha256', $token);
            $query = "SELECT * FROM refresh_tokens WHERE token = :token LIMIT 1";
            $result = $this->dbHelper->select($query, [':token' => $hashedToken], $this->useSecureDb);
            
            return $result[0] ?? null;
        } catch (Exception $e) {
            $this->logger->error("Error finding token: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Revoke a token
     * 
     * @param string $token The unhashed token
     * @return bool Success status
     */
    public function revoke(string $token): bool
    {
        try {
            // Store in cache for quick lookups
            apcu_store("revoked_refresh_token_$token", true, 604800);
            
            // Store in secure database for persistence
            $hashedToken = hash('sha256', $token);
            
            // Update the token status in secure database
            $result = $this->dbHelper->update(
                'refresh_tokens', 
                [
                    'revoked' => 1,
                    'revoked_at' => date('Y-m-d H:i:s')
                ], 
                ['token' => $hashedToken], 
                $this->useSecureDb
            );
                
            return $result;
        } catch (Exception $e) {
            $this->logger->error("Failed to revoke token: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Purge all expired tokens
     * 
     * @return int Number of tokens purged
     */
    public function purgeExpired(): int
    {
        try {
            $count = $this->dbHelper->delete(
                'refresh_tokens', 
                ['expires_at < ' => date('Y-m-d H:i:s')], 
                false,
                $this->useSecureDb
            );
                
            $this->logger->info("Purged {$count} expired tokens");
            return $count;
        } catch (Exception $e) {
            $this->logger->error("Failed to purge expired tokens: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get all active tokens for a user
     * 
     * @param int $userId The user ID
     * @return array List of token records
     */
    public function getActiveForUser(int $userId): array
    {
        try {
            $query = "SELECT * FROM refresh_tokens WHERE user_id = :user_id AND revoked = 0 AND expires_at > :now";
            $tokens = $this->dbHelper->select(
                $query, 
                [
                    ':user_id' => $userId,
                    ':now' => date('Y-m-d H:i:s')
                ], 
                $this->useSecureDb
            );
                
            return $tokens ?? [];
        } catch (Exception $e) {
            $this->logger->error("Failed to get active tokens: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user ID associated with a token
     * 
     * @param string $token The unhashed token
     * @return int|null User ID or null if not found
     */
    public function getUserId(string $token): ?int
    {
        $tokenData = $this->findByToken($token);
        return $tokenData['user_id'] ?? null;
    }
}
