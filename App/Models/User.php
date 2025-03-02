<?php

namespace App\Models;

use App\Helpers\DatabaseHelper;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;
use App\Services\AuditService;

/**
 * User Model
 * 
 * Represents a user in the system with their associated data and relationships.
 * 
 * @property string $id UUID of the user
 * @property string $name User's first name
 * @property string $surname User's last name
 * @property string $email User's email address
 * @property string $password_hash Hashed password
 * @property string $role User role (user, admin, super_admin)
 * @property string $phone Phone number
 * @property string $address Physical address
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * @property \DateTime $deleted_at
 */
class User extends BaseModel
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $resourceName = 'user';
    protected $useTimestamps = true;
    protected $useSoftDeletes = true;
    protected $useUuid = true;

    protected $fillable = [
        'name',
        'surname',
        'email',
        'password_hash',
        'role',
        'phone',
        'address',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
        'deleted_at',
    ];

    public static array $rules = [
        'name' => 'required|string|max:255',
        'surname' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:8|regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/',
        'role' => 'required|in:user,admin,super_admin',
        'phone' => 'nullable|string|max:20',
        'address' => 'nullable|string|max:255',
    ];

    /**
     * Relationships
     */

    /**
     * Get user's bookings
     * 
     * @param string $userId
     * @return array
     */
    public function getBookings(string $userId): array
    {
        $query = "SELECT * FROM bookings WHERE user_id = :user_id";
        if ($this->useSoftDeletes) {
            $query .= " AND deleted_at IS NULL";
        }
        $query .= " ORDER BY created_at DESC";
        
        return $this->dbHelper->select($query, [':user_id' => $userId]);
    }

    /**
     * Get user's payments
     * 
     * @param string $userId
     * @return array
     */
    public function getPayments(string $userId): array
    {
        $query = "SELECT * FROM payments WHERE user_id = :user_id";
        if ($this->useSoftDeletes) {
            $query .= " AND deleted_at IS NULL";
        }
        $query .= " ORDER BY created_at DESC";
        
        return $this->dbHelper->select($query, [':user_id' => $userId]);
    }

    /**
     * Get user's notifications
     * 
     * @param string $userId
     * @return array
     */
    public function getNotifications(string $userId): array
    {
        $query = "SELECT * FROM notifications WHERE user_id = :user_id";
        if ($this->useSoftDeletes) {
            $query .= " AND deleted_at IS NULL";
        }
        $query .= " ORDER BY created_at DESC";
        
        return $this->dbHelper->select($query, [':user_id' => $userId]);
    }

    /**
     * Get user's logs
     * 
     * @param string $userId
     * @return array
     */
    public function getLogs(string $userId): array
    {
        $query = "SELECT * FROM logs WHERE user_reference = :user_id";
        return $this->dbHelper->select($query, [':user_id' => $userId]);
    }

    /**
     * Get user's audit trails
     * 
     * @param string $userId
     * @return array
     */
    public function getAuditTrails(string $userId): array
    {
        $query = "SELECT * FROM audit_trails WHERE user_reference = :user_id";
        return $this->dbHelper->select($query, [':user_id' => $userId]);
    }

    /**
     * Get user's contracts
     * 
     * @param string $userId
     * @return array
     */
    public function getContracts(string $userId): array
    {
        $query = "SELECT * FROM contracts WHERE user_reference = :user_id";
        if ($this->useSoftDeletes) {
            $query .= " AND deleted_at IS NULL";
        }
        $query .= " ORDER BY created_at DESC";
        
        return $this->dbHelper->select($query, [':user_id' => $userId]);
    }

    /**
     * Accessors & Helpers
     */
    
    /**
     * Get full name by combining first and last name
     * 
     * @param array $user User data
     * @return string
     */
    public static function getFullName(array $user): string
    {
        return "{$user['name']} {$user['surname']}";
    }

    /**
     * Check if user is an admin
     * 
     * @param array $user User data
     * @return bool
     */
    public static function isAdmin(array $user): bool
    {
        return in_array($user['role'], ['admin', 'super_admin']);
    }

    /**
     * Check if user is a super admin
     * 
     * @param array $user User data
     * @return bool
     */
    public static function isSuperAdmin(array $user): bool
    {
        return $user['role'] === 'super_admin';
    }

    /**
     * Check if user has a specific permission
     * 
     * @param array $user User data
     * @param string $permission
     * @return bool
     */
    public static function hasPermission(array $user, string $permission): bool
    {
        $rolePermissions = [
            'user' => ['read_own'],
            'admin' => ['read_own', 'read_all', 'write_all'],
            'super_admin' => ['read_own', 'read_all', 'write_all', 'delete_all'],
        ];

        return in_array($permission, $rolePermissions[$user['role']] ?? []);
    }

    /**
     * Password handling
     */
    
    /**
     * Hash a password
     * 
     * @param string $password
     * @return string
     */
    public static function hashPassword(string $password): string
    {
        return Hash::make($password);
    }
    
    /**
     * Verify a password
     * 
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return Hash::check($password, $hash);
    }

    /**
     * Database operations
     */

    /**
     * Create a new user
     * 
     * @param array $data
     * @return int The ID of the created user (or UUID converted to integer if using UUID)
     */
    public function create(array $data): int
    {
        if (isset($data['password'])) {
            $data['password_hash'] = self::hashPassword($data['password']);
            unset($data['password']);
        }

        if ($this->useUuid && !isset($data['id'])) {
            $data['id'] = Uuid::uuid4()->toString();
        }

        if ($this->useTimestamps) {
            $now = date('Y-m-d H:i:s');
            $data['created_at'] = $now;
            $data['updated_at'] = $now;
        }

        $id = $this->dbHelper->insert($this->table, $data);
        
        // Log the creation if audit service is available
        if ($this->auditService) {
            $this->auditService->logEvent('user', 'created', [
                'id' => $id,
                'email' => $data['email'] ?? 'unknown'
            ]);
        }

        // Ensure we return an integer to match the parent class signature
        return is_numeric($id) ? (int)$id : crc32($id);
    }

    /**
     * Update user data
     * 
     * @param string|int $id
     * @param array $data
     * @return bool
     */
    public function update(string|int $id, array $data): bool
    {
        if (isset($data['password'])) {
            $data['password_hash'] = self::hashPassword($data['password']);
            unset($data['password']);
        }

        if ($this->useTimestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        $conditions = ['id' => $id];
        if ($this->useSoftDeletes) {
            $conditions['deleted_at IS NULL'] = null;
        }

        $result = $this->dbHelper->update($this->table, $data, $conditions);
        
        // Log the update if audit service is available
        if ($result && $this->auditService) {
            $this->auditService->logEvent('user', 'updated', [
                'id' => $id,
                'updated_fields' => array_keys($data)
            ]);
        }

        return $result;
    }

    /**
     * Find a user by their email address
     * 
     * @param string $email
     * @return array|null
     */
    public function findByEmail(string $email): ?array
    {
        $query = "SELECT * FROM {$this->table} WHERE email = :email";
        
        if ($this->useSoftDeletes) {
            $query .= " AND deleted_at IS NULL";
        }
        
        $result = $this->dbHelper->select($query, [':email' => $email]);
        return $result ? $result[0] : null;
    }

    /**
     * Get active users (not deleted)
     * 
     * @return array
     */
    public function getActive(): array
    {
        $query = "SELECT * FROM {$this->table} WHERE deleted_at IS NULL";
        return $this->dbHelper->select($query);
    }

    /**
     * Get users with a specific role
     * 
     * @param string $role
     * @return array
     */
    public function getWithRole(string $role): array
    {
        $query = "SELECT * FROM {$this->table} WHERE role = :role";
        
        if ($this->useSoftDeletes) {
            $query .= " AND deleted_at IS NULL";
        }
        
        return $this->dbHelper->select($query, [':role' => $role]);
    }
}
