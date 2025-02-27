<?php

namespace App\Helpers;

use Psr\Log\LoggerInterface;
use App\Helpers\DatabaseHelper;

/**
 * Helper class to setup and verify the application environment
 */
class SetupHelper
{
    private $pdo;
    private LoggerInterface $logger;
    
    public function __construct(DatabaseHelper $dbHelper, LoggerInterface $logger)
    {
        $this->pdo = $dbHelper->getPdo();
        $this->logger = $logger;
    }
    
    /**
     * Add required indexes to database tables if they don't exist
     */
    public function ensureIndexes(): void
    {
        try {
            // Check for email index on users table
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as count
                FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                AND table_name = 'users'
                AND index_name = 'idx_users_email'
            ");
            $stmt->execute();
            $result = $stmt->fetch();
            
            if ($result['count'] == 0) {
                $this->logger->info("Creating index on users.email");
                $this->pdo->exec("CREATE INDEX idx_users_email ON users(email)");
            }
            
            // Check for other important indexes
            $this->logger->info("Database indexes verified");
        } catch (\Exception $e) {
            $this->logger->error("Failed to ensure indexes: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Verify that the application is running in a secure environment
     */
    public function verifySecureEnvironment(): array
    {
        $issues = [];
        
        // Check if we're running over HTTPS
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                  || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
                  
        if (!$isSecure) {
            $issues[] = "Application is not running over HTTPS. This is insecure for production.";
            $this->logger->warning("Security warning: Not running over HTTPS");
        }
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4.0', '<')) {
            $issues[] = "PHP version is below 7.4. Please upgrade for security.";
            $this->logger->warning("Security warning: PHP version below 7.4", ['version' => PHP_VERSION]);
        }
        
        // Return issues found
        return $issues;
    }
}
