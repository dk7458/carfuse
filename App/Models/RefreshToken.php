<?php

namespace App\Models;

use App\Helpers\DatabaseHelper;
use App\Services\AuditService;
use Psr\Log\LoggerInterface;
use Exception;

class RefreshToken extends BaseModel
{
    protected $table = 'refresh_tokens';
    protected $resourceName = 'refresh_token';
    protected $useTimestamps = true;
    protected $useSoftDeletes = false;

    /**
     * RefreshToken constructor
     * 
     * @param DatabaseHelper $dbHelper
     * @param LoggerInterface $logger
     */
    public function __construct(DatabaseHelper $dbHelper, AuditService $auditService, LoggerInterface $logger)
    {
        // Call parent constructor with null AuditService as third parameter
        parent::__construct($dbHelper, $auditService, $logger);
    }

    /**
     * Store a refresh token in the database
     * 
     * @param int $userId The user ID
     * @param string $refreshToken The unhashed token
     * @param int $expiresIn Expiry time in seconds
     * @return bool Success status
     */
    public function storeToken(int $userId, string $refreshToken, int $expiresIn = 604800): bool
    {
        try {
            // Hash token for secure storage
            $hashedToken = hash('sha256', $refreshToken);
            
            // Create token data
            $data = [
                'user_id' => $userId,
                'token' => $hashedToken,
                'expires_at' => date('Y-m-d H:i:s', time() + $expiresIn),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null
            ];
            
            // Use BaseModel's create method
            $result = $this->create($data);
            
            $this->logger->info("Refresh token stored", ['user_id' => $userId]);
            return (bool)$result;
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
            if (function_exists('apcu_exists') && apcu_exists("revoked_refresh_token_$token")) {
                return true;
            }
            
            // If not in cache, check database
            $hashedToken = hash('sha256', $token);
            $query = "SELECT 1 FROM {$this->table} WHERE token = :token AND revoked = 1 LIMIT 1";
            $revoked = $this->dbHelper->select($query, [':token' => $hashedToken]);
                
            // If revoked in database, store in cache for next time
            if (!empty($revoked) && function_exists('apcu_store')) {
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
            $query = "SELECT * FROM {$this->table} WHERE token = :token LIMIT 1";
            $result = $this->dbHelper->select($query, [':token' => $hashedToken]);
            
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
    public function revokeToken(string $token): bool
    {
        try {
            // Store in cache for quick lookups if APCu is available
            if (function_exists('apcu_store')) {
                apcu_store("revoked_refresh_token_$token", true, 604800);
            }
            
            // Store in database for persistence
            $hashedToken = hash('sha256', $token);
            
            // Update the token status using BaseModel's update method
            $result = $this->dbHelper->update(
                $this->table, 
                [
                    'revoked' => 1,
                    'revoked_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ], 
                ['token' => $hashedToken]
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
            $query = "DELETE FROM {$this->table} WHERE expires_at < :now";
            $params = [':now' => date('Y-m-d H:i:s')];
            $count = $this->dbHelper->execute($query, $params);
                
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
            $query = "SELECT * FROM {$this->table} WHERE user_id = :user_id AND revoked = 0 AND expires_at > :now";
            $tokens = $this->dbHelper->select(
                $query, 
                [
                    ':user_id' => $userId,
                    ':now' => date('Y-m-d H:i:s')
                ]
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
