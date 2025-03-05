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

    /**
     * Find admin by token
     */
    public function findByToken(string $token): ?array
    {
        $adminData = DatabaseHelper::select(
            "SELECT id, email, role FROM admins WHERE token = ? AND token_expiry > NOW()",
            [$token],
            true // Using secure database
        );
        
        return !empty($adminData) ? $adminData[0] : null;
    }

    /**
     * Get paginated list of all users with their roles
     */
    public function getPaginatedUsers(int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;
        
        $users = DatabaseHelper::select(
            "SELECT u.*, r.name as role_name 
             FROM users u 
             LEFT JOIN roles r ON u.role_id = r.id 
             ORDER BY u.created_at DESC 
             LIMIT ? OFFSET ?",
            [$perPage, $offset],
            false // Using application database
        );
        
        return $users;
    }

    /**
     * Get total user count
     */
    public function getTotalUserCount(): int
    {
        $result = DatabaseHelper::select(
            "SELECT COUNT(*) as count FROM users",
            [],
            false // Using application database
        );
        
        return (int)$result[0]['count'];
    }

    /**
     * Get user by ID
     */
    public function getUserById(int $userId): ?array
    {
        $user = DatabaseHelper::select(
            "SELECT id, email, role FROM users WHERE id = ?",
            [$userId],
            false // Using application database
        );
        
        return !empty($user) ? $user[0] : null;
    }

    /**
     * Update user role
     */
    public function updateUserRole(int $userId, string $role): bool
    {
        return DatabaseHelper::update(
            "users",
            ["role" => $role],
            ["id" => $userId],
            false // Using application database
        );
    }

    /**
     * Soft delete user
     */
    public function softDeleteUser(int $userId): bool
    {
        return DatabaseHelper::update(
            "users",
            ["deleted_at" => date('Y-m-d H:i:s')],
            ["id" => $userId],
            false // Using application database
        );
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStatistics(): array
    {
        // Get total users count
        $totalUsers = DatabaseHelper::select(
            "SELECT COUNT(*) as count FROM users WHERE deleted_at IS NULL",
            [],
            false
        )[0]['count'];
        
        // Get total bookings count
        $totalBookings = DatabaseHelper::select(
            "SELECT COUNT(*) as count FROM bookings",
            [],
            false
        )[0]['count'];
        
        // Get total revenue
        $totalRevenue = DatabaseHelper::select(
            "SELECT SUM(amount) as total FROM payments WHERE status = 'completed'",
            [],
            false
        )[0]['total'] ?? 0;
        
        // Get latest 5 users
        $latestUsers = DatabaseHelper::select(
            "SELECT u.*, r.name as role_name 
             FROM users u 
             LEFT JOIN roles r ON u.role_id = r.id 
             WHERE u.deleted_at IS NULL 
             ORDER BY u.created_at DESC 
             LIMIT 5",
            [],
            false
        );
        
        // Get latest 5 transactions
        $latestTransactions = DatabaseHelper::select(
            "SELECT * FROM transaction_logs ORDER BY created_at DESC LIMIT 5",
            [],
            false
        );
        
        return [
            'total_users' => $totalUsers,
            'total_bookings' => $totalBookings,
            'total_revenue' => $totalRevenue,
            'latest_users' => $latestUsers,
            'latest_transactions' => $latestTransactions,
        ];
    }

    /**
     * Check if admin with email exists
     */
    public function findByEmail(string $email): ?array
    {
        $admin = DatabaseHelper::select(
            "SELECT id FROM admins WHERE email = ?",
            [$email],
            true // Using secure database
        );
        
        return !empty($admin) ? $admin[0] : null;
    }

    /**
     * Create new admin user
     * 
     * @param array $adminData
     * @return int|null
     */
    public function createAdmin(array $adminData): ?int
    {
        return DatabaseHelper::insert(
            "admins",
            $adminData,
            true // Using secure database
        );
    }

    /**
     * Get admin by ID
     */
    public function findById(int $adminId): ?array
    {
        $admin = DatabaseHelper::select(
            "SELECT id, name, email, role, created_at FROM admins WHERE id = ?",
            [$adminId],
            true // Using secure database
        );
        
        return !empty($admin) ? $admin[0] : null;
    }
}
