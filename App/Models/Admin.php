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
     * @var array The attributes that are mass assignable
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role'
    ];

    /**
     * @var array Data type casting definitions
     */
    protected $casts = [
        'id' => 'int',
        'email' => 'string',
        'role' => 'string'
    ];
    
    /**
     * Constructor
     *
     * @param DatabaseHelper $dbHelper
     * @param AuditService|null $auditService
     */
    public function __construct(DatabaseHelper $dbHelper, AuditService $auditService)
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
     * Override create to handle password hashing.
     *
     * @param array $data
     * @return int|string
     */
    public function create(array $data): int
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
     * Override update to handle password hashing.
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
     * 
     * @param string $token
     * @return array|null
     */
    public function findByToken(string $token): ?array
    {
        $query = "SELECT id, email, role FROM {$this->table} 
                 WHERE token = :token AND token_expiry > NOW()";
                 
        $result = $this->dbHelper->select($query, [':token' => $token], true);
        return !empty($result) ? $result[0] : null;
    }

    /**
     * Get paginated list of all users with their roles
     * 
     * @param int $page
     * @param int $perPage
     * @return array
     */
    public function getPaginatedUsers(int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;
        
        $query = "SELECT u.*, r.name as role_name 
                 FROM users u 
                 LEFT JOIN roles r ON u.role_id = r.id 
                 ORDER BY u.created_at DESC 
                 LIMIT :limit OFFSET :offset";
                 
        return $this->dbHelper->select($query, [
            ':limit' => $perPage,
            ':offset' => $offset
        ], false);
    }

    /**
     * Get total user count
     * 
     * @return int
     */
    public function getTotalUserCount(): int
    {
        $result = $this->dbHelper->select(
            "SELECT COUNT(*) as count FROM users",
            [],
            false
        );
        
        return (int)$result[0]['count'];
    }

    /**
     * Get user by ID
     * 
     * @param int $userId
     * @return array|null
     */
    public function getUserById(int $userId): ?array
    {
        $result = $this->dbHelper->select(
            "SELECT id, email, role FROM users WHERE id = :id",
            [':id' => $userId],
            false
        );
        
        return !empty($result) ? $result[0] : null;
    }

    /**
     * Update user role
     * 
     * @param int $userId
     * @param string $role
     * @return bool
     */
    public function updateUserRole(int $userId, string $role): bool
    {
        return $this->dbHelper->update(
            "users",
            ["role" => $role],
            ["id" => $userId],
            false
        );
    }

    /**
     * Soft delete user
     * 
     * @param int $userId
     * @return bool
     */
    public function softDeleteUser(int $userId): bool
    {
        return $this->dbHelper->update(
            "users",
            ["deleted_at" => date('Y-m-d H:i:s')],
            ["id" => $userId],
            false
        );
    }

    /**
     * Get dashboard statistics
     * 
     * @return array
     */
    public function getDashboardStatistics(): array
    {
        // Get total users count
        $userQuery = "SELECT COUNT(*) as count FROM users WHERE deleted_at IS NULL";
        $totalUsers = $this->dbHelper->select($userQuery, [], false)[0]['count'];
        
        // Get total bookings count
        $bookingQuery = "SELECT COUNT(*) as count FROM bookings";
        $totalBookings = $this->dbHelper->select($bookingQuery, [], false)[0]['count'];
        
        // Get total revenue
        $revenueQuery = "SELECT SUM(amount) as total FROM payments WHERE status = 'completed'";
        $revenueResult = $this->dbHelper->select($revenueQuery, [], false);
        $totalRevenue = $revenueResult[0]['total'] ?? 0;
        
        // Get latest 5 users
        $latestUserQuery = "SELECT u.*, r.name as role_name 
            FROM users u 
            LEFT JOIN roles r ON u.role_id = r.id 
            WHERE u.deleted_at IS NULL 
            ORDER BY u.created_at DESC 
            LIMIT 5";
        $latestUsers = $this->dbHelper->select($latestUserQuery, [], false);
        
        // Get latest 5 transactions
        $transactionQuery = "SELECT * FROM transaction_logs ORDER BY created_at DESC LIMIT 5";
        $latestTransactions = $this->dbHelper->select($transactionQuery, [], false);
        
        return [
            'total_users' => $totalUsers,
            'total_bookings' => $totalBookings,
            'total_revenue' => $totalRevenue,
            'latest_users' => $latestUsers,
            'latest_transactions' => $latestTransactions,
        ];
    }

    /**
     * Find admin by email
     * 
     * @param string $email
     * @return array|null
     */
    public function findByEmail(string $email): ?array
    {
        $query = "SELECT id FROM {$this->table} WHERE email = :email";
        
        $result = $this->dbHelper->select($query, [':email' => $email], true);
        return !empty($result) ? $result[0] : null;
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
     * Find admin by ID
     * 
     * @param int $adminId
     * @return array|null
     */
    public function findById(int $adminId): ?array
    {
        $query = "SELECT id, name, email, role, created_at FROM {$this->table} WHERE id = :id";
        
        $result = $this->dbHelper->select($query, [':id' => $adminId], true);
        return !empty($result) ? $result[0] : null;
    }
}
