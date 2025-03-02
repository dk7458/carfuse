<?php

namespace App\Models;

use App\Helpers\DatabaseHelper;
use App\Services\AuditService;

/**
 * Admin Model - Manages system administrators.
 */
class Admin extends BaseModel
{
    protected $table = 'admins';
    protected $resourceName = 'admin';
    protected $useTimestamps = true;
    protected $useSoftDeletes = true;
    
    /**
     * Constructor
     *
     * @param DatabaseHelper $dbHelper
     * @param AuditService|null $auditService
     */
    public function __construct(DatabaseHelper $dbHelper = null, AuditService $auditService = null)
    {
        parent::__construct($dbHelper, $auditService);
    }
    
    /**
     * Hash a password.
     *
     * @param string $password
     * @return string
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Verify password.
     *
     * @param string $plainPassword
     * @param string $hashedPassword
     * @return bool
     */
    public static function verifyPassword(string $plainPassword, string $hashedPassword): bool
    {
        return password_verify($plainPassword, $hashedPassword);
    }
    
    /**
     * Create an admin.
     * Override to handle password hashing.
     *
     * @param array $data
     * @return int|string
     */
    public function create(array $data): int|string
    {
        if (isset($data['password'])) {
            $data['password'] = self::hashPassword($data['password']);
        }
        
        $id = parent::create($data);
        
        // Add custom audit logging if needed
        if ($this->auditService) {
            $this->auditService->logEvent('admin', 'admin_created', [
                'id' => $id,
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
                'role' => $data['role'] ?? null
            ]);
        }
        
        return $id;
    }
    
    /**
     * Update an admin.
     * Override to handle password hashing.
     *
     * @param int|string $id
     * @param array $data
     * @return bool
     */
    public function update(int|string $id, array $data): bool
    {
        if (isset($data['password'])) {
            $data['password'] = self::hashPassword($data['password']);
        }
        
        $result = parent::update($id, $data);
        
        // Add custom audit logging if needed
        if ($result && $this->auditService) {
            $this->auditService->logEvent('admin', 'admin_updated', [
                'id' => $id,
                'updated_fields' => array_keys($data)
            ]);
        }
        
        return $result;
    }
    
    /**
     * Get admin by email.
     *
     * @param string $email
     * @return array|null
     */
    public function getByEmail(string $email): ?array
    {
        $query = "
            SELECT * FROM {$this->table}
            WHERE email = :email
        ";
        
        if ($this->useSoftDeletes) {
            $query .= " AND deleted_at IS NULL";
        }
        
        $result = $this->dbHelper->select($query, [':email' => $email]);
        return $result ? $result[0] : null;
    }
    
    /**
     * Restore a soft deleted admin.
     *
     * @param int|string $id
     * @return bool
     */
    public function restore(int|string $id): bool
    {
        if (!$this->useSoftDeletes) {
            return false;
        }
        
        $result = $this->dbHelper->update($this->table, ['deleted_at' => null], ['id' => $id]);
        
        if ($result && $this->auditService) {
            $this->auditService->logEvent('admin', 'admin_restored', [
                'admin_id' => $id
            ]);
        }
        
        return $result;
    }
    
    /**
     * Get users managed by this admin.
     *
     * @param int|string $adminId
     * @return array
     */
    public function getManagedUsers(int|string $adminId): array
    {
        $query = "
            SELECT * FROM users
            WHERE managed_by = :admin_id
        ";
        
        if ($this->useSoftDeletes) {
            $query .= " AND deleted_at IS NULL";
        }
        
        $query .= " ORDER BY name ASC";
        
        return $this->dbHelper->select($query, [':admin_id' => $adminId]);
    }
    
    /**
     * Get admin permissions.
     *
     * @param int|string $adminId
     * @return array
     */
    public function getPermissions(int|string $adminId): array
    {
        $query = "
            SELECT p.* FROM permissions p
            JOIN admin_permissions ap ON p.id = ap.permission_id
            WHERE ap.admin_id = :admin_id
        ";
        
        return $this->dbHelper->select($query, [':admin_id' => $adminId]);
    }
}
