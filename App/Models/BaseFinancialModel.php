<?php

namespace App\Models;

use App\Helpers\DatabaseHelper;
use App\Services\AuditService;
use App\Services\EncryptionService;

/**
 * Base class for any model dealing with financial data
 * to provide consistent handling of sensitive information
 */
abstract class BaseFinancialModel extends BaseModel
{
    /** @var DatabaseHelper */
    protected $dbHelper;
    
    /** @var AuditService|null */
    protected $auditService;
    
    /**
     * Fields that should be encrypted when stored in the database
     *
     * @var array
     */
    protected $encryptedFields = ['amount', 'card_number', 'card_last4'];
    
    /**
     * Encrypt sensitive data before insert/update
     *
     * @param array $data
     * @return array
     */
    protected function encryptSensitiveData(array $data): array
    {
        foreach ($this->encryptedFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = EncryptionService::encrypt($data[$field]);
            }
        }
        
        return $data;
    }
    
    /**
     * Decrypt sensitive data after fetch
     *
     * @param array $data
     * @return array
     */
    protected function decryptSensitiveData(array $data): array
    {
        foreach ($this->encryptedFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = EncryptionService::decrypt($data[$field]);
            }
        }
        
        return $data;
    }
    
    /**
     * Record a security event in the audit log
     *
     * @param string $action
     * @param array $data
     * @param int|null $userId
     */
    protected function recordAuditEvent(string $action, array $data, ?int $userId = null): void
    {
        if ($this->auditService) {
            $this->auditService->logEvent(
                $this->resourceName,
                $action,
                $data,
                $userId
            );
        }
    }
}
