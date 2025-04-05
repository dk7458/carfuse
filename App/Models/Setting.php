<?php

namespace App\Models;

use App\Database\Database;
use PDO;
use PDOException;

/**
 * Setting Model
 *
 * Handles database operations for system settings
 */
class Setting extends BaseModel
{
    protected $table = 'settings';
    
    /**
     * Get all settings
     */
    public function getAll(): array
    {
        try {
            $query = "SELECT * FROM {$this->table} ORDER BY `key` ASC";
            $statement = $this->db->prepare($query);
            $statement->execute();
            
            return $statement->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logger->error('Failed to get all settings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [];
        }
    }
    
    /**
     * Get setting by key
     */
    public function getByKey(string $key): ?array
    {
        try {
            // Try to get from cache first
            $cacheKey = "setting_{$key}";
            $cached = $this->getFromCache($cacheKey);
            
            if ($cached !== null) {
                return $cached;
            }
            
            $query = "SELECT * FROM {$this->table} WHERE `key` = :key LIMIT 1";
            $statement = $this->db->prepare($query);
            $statement->bindParam(':key', $key, PDO::PARAM_STR);
            $statement->execute();
            
            $result = $statement->fetch(PDO::FETCH_ASSOC);
            
            // Cache the result if found
            if ($result) {
                $this->saveToCache($cacheKey, $result);
            }
            
            return $result ?: null;
        } catch (PDOException $e) {
            $this->logger->error("Failed to get setting by key: {$key}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }
    
    /**
     * Save or update a setting
     */
    public function saveSetting(string $key, string $value, string $type): bool
    {
        try {
            // Check if setting exists
            $existing = $this->getByKey($key);
            
            if ($existing) {
                // Update existing setting
                $query = "UPDATE {$this->table} SET `value` = :value, `type` = :type, `updated_at` = NOW() WHERE `key` = :key";
            } else {
                // Create new setting
                $query = "INSERT INTO {$this->table} (`key`, `value`, `type`, `created_at`, `updated_at`) 
                         VALUES (:key, :value, :type, NOW(), NOW())";
            }
            
            $statement = $this->db->prepare($query);
            $statement->bindParam(':key', $key, PDO::PARAM_STR);
            $statement->bindParam(':value', $value, PDO::PARAM_STR);
            $statement->bindParam(':type', $type, PDO::PARAM_STR);
            
            $result = $statement->execute();
            
            // Clear cache for this key
            $this->deleteFromCache("setting_{$key}");
            
            return $result;
        } catch (PDOException $e) {
            $this->logger->error("Failed to save setting: {$key}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }
    
    /**
     * Delete a setting
     */
    public function deleteSetting(string $key): bool
    {
        try {
            $query = "DELETE FROM {$this->table} WHERE `key` = :key";
            $statement = $this->db->prepare($query);
            $statement->bindParam(':key', $key, PDO::PARAM_STR);
            
            $result = $statement->execute();
            
            // Clear cache for this key
            $this->deleteFromCache("setting_{$key}");
            
            return $result;
        } catch (PDOException $e) {
            $this->logger->error("Failed to delete setting: {$key}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }
    
    /**
     * Get settings by tab
     */
    public function getByTab(string $tab): array
    {
        try {
            // For tab-specific keys, we look for patterns in the key names
            $patterns = $this->getTabKeyPatterns($tab);
            
            if (empty($patterns)) {
                return [];
            }
            
            // Build the WHERE clause for pattern matching
            $whereClause = [];
            foreach ($patterns as $pattern) {
                $whereClause[] = "`key` LIKE :pattern{$pattern}";
            }
            $whereClauseString = implode(' OR ', $whereClause);
            
            $query = "SELECT * FROM {$this->table} WHERE {$whereClauseString} ORDER BY `key` ASC";
            $statement = $this->db->prepare($query);
            
            // Bind each pattern parameter
            foreach ($patterns as $index => $pattern) {
                $paramValue = "{$pattern}%";
                $statement->bindParam(":pattern{$pattern}", $paramValue, PDO::PARAM_STR);
            }
            
            $statement->execute();
            return $statement->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $this->logger->error("Failed to get settings for tab: {$tab}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [];
        }
    }
    
    /**
     * Get key patterns for a specific tab
     */
    private function getTabKeyPatterns(string $tab): array
    {
        // Map tab names to key prefixes
        $tabMappings = [
            'general' => ['company_', 'site_', 'meta_', 'tax_', 'currency'],
            'booking' => ['booking_', 'rental_', 'deposit', 'buffer_time', 'cancellation_', 'pickup_', 'dropoff_', 'pricing'],
            'notifications' => ['email_', 'smtp_', 'sms_', 'notify_'],
            'security' => ['password_', 'login_', 'session_', 'maintenance_', 'captcha_'],
            'integrations' => ['api_', 'payment_gateway', 'maps_', 'social_', 'share_', 'facebook_', 'google_', 'stripe_', 'paypal_']
        ];
        
        return $tabMappings[$tab] ?? [];
    }
    
    /**
     * Save to cache
     */
    private function saveToCache(string $key, $data): void
    {
        if (function_exists('apcu_store')) {
            apcu_store($key, $data, 3600); // Cache for 1 hour
        }
    }
    
    /**
     * Get from cache
     */
    private function getFromCache(string $key)
    {
        if (function_exists('apcu_fetch')) {
            $success = false;
            $result = apcu_fetch($key, $success);
            return $success ? $result : null;
        }
        return null;
    }
    
    /**
     * Delete from cache
     */
    private function deleteFromCache(string $key): void
    {
        if (function_exists('apcu_delete')) {
            apcu_delete($key);
        }
    }
}
