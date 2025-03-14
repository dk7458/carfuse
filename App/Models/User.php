<?php

namespace App\Models;

use App\Helpers\DatabaseHelper;
use Exception;
use Psr\Log\LoggerInterface;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;
use App\Services\AuditService;
use App\Services\EmailService;

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
    protected $logger;
    protected $emailService;

    public function __construct(DatabaseHelper $dbHelper, LoggerInterface $logger, AuditService $auditService)
    {
        parent::__construct($dbHelper, $auditService, $logger);
        $this->logger = $logger;
    }

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
        'status',
        'created_at',
        'updated_at'
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
     * Create a new user with role assignment and password hashing
     * 
     * @param array $data
     * @return int|string
     */
    public function createWithDefaultRole(array $data): int|string
    {
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password_hash'] = self::hashPassword($data['password']);
            unset($data['password']);
        }
        
        // Set default role if not provided
        if (!isset($data['role'])) {
            $data['role'] = 'user';
        }
        
        // Call parent create method to handle database insertion
        return parent::create($data);
    }

    /**
     * Update user data with password handling
     * 
     * @param string|int $id
     * @param array $data
     * @return bool
     */
    public function updateWithPasswordHandling(string|int $id, array $data): bool
    {
        if (isset($data['password'])) {
            $data['password_hash'] = self::hashPassword($data['password']);
            unset($data['password']);
        }

        return parent::update($id, $data);
    }

    /**
     * Override create method to hash password and assign default role.
     * 
     * @param array $data
     * @return int|string
     */
    public function create(array $data): int|string
    {
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password_hash'] = self::hashPassword($data['password']);
            unset($data['password']);
        }
        
        // Set default role if not provided
        if (!isset($data['role'])) {
            $data['role'] = 'user';
        }
        
        // Call parent create method to handle database insertion
        return parent::create($data);
    }

    /**
     * Override create method to hash password, assign default role, and send welcome email.
     * 
     * @param array $data
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
     * @param int $userId
     * @return array
     */
    public function getPayments(int $userId): array
    {
        $query = "SELECT p.* FROM payments p 
                 JOIN bookings b ON p.booking_id = b.id 
                 WHERE b.user_id = :user_id AND p.deleted_at IS NULL 
                 ORDER BY p.created_at DESC";
        
        return $this->dbHelper->select($query, [':user_id' => $userId]);
    }
    
    /**
     * Get all transactions for a user
     * 
     * @param int $userId
     * @return array
     */
    public function getTransactions(int $userId): array
    {
        $query = "SELECT t.* FROM transaction_logs t 
                 WHERE t.user_id = :user_id 
                 ORDER BY t.created_at DESC";
        
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
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ? AND active = 1");
            $stmt->execute([$email]);
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {
            $this->logger->error("Error finding user by email: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Find a user by their ID
     * 
     * @param string|int $id
     * @return array|null
     */
    public function find(string|int $id): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ? AND active = 1");
            $stmt->execute([$id]);
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {
            $this->logger->error("Error finding user by ID: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate a user's password
     */
    public function validatePassword(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }
        
        return $user;
    }

    /**
     * Update a user's password
     */
    public function updatePassword(int $userId, string $newPassword): bool
    {
        try {
            $hashedPassword = $this->hashPassword($newPassword);
            return $this->update($userId, [
                'password_hash' => $hashedPassword,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            $this->logger->error("Error updating password: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a password reset token
     */
    public function createPasswordReset(string $email, string $token, ?string $ipAddress, string $expiry): bool
    {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO password_resets (email, token, ip_address, expires_at, created_at) VALUES (?, ?, ?, ?, ?)");
            return $stmt->execute([
                $email, 
                $token, 
                $ipAddress, 
                $expiry, 
                date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            $this->logger->error("Error creating password reset: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verify a password reset token
     */
    public function verifyResetToken(string $token): ?array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM password_resets 
                 WHERE token = ? AND used = 0 AND expires_at > ? 
                 ORDER BY created_at DESC LIMIT 1"
            );
            $stmt->execute([$token, date('Y-m-d H:i:s')]);
            return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {
            $this->logger->error("Error verifying reset token: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Mark a reset token as used
     */
    public function markResetTokenUsed(int $tokenId): bool
    {
        try {
            $stmt = $this->pdo->prepare("UPDATE password_resets SET used = 1, used_at = ? WHERE id = ?");
            return $stmt->execute([date('Y-m-d H:i:s'), $tokenId]);
        } catch (Exception $e) {
            $this->logger->error("Error marking reset token as used: " . $e->getMessage());
            throw $e;
        }
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

    /**
     * Get a user by email
     * 
     * @param string $email
     * @return array|null
     */
    public function getUserByEmail(string $email): ?array
    {
        return $this->findByEmail($email);
    }

    /**
     * Update user profile
     * 
     * @param string|int $userId
     * @param array $profileData
     * @return bool
     */
    public function updateProfile(string|int $userId, array $profileData): bool
    {
        try {
            // Filter out sensitive fields that shouldn't be updated via profile update
            $allowedFields = ['name', 'surname', 'phone', 'address'];
            $filteredData = array_intersect_key($profileData, array_flip($allowedFields));
            
            return $this->update($userId, $filteredData);
        } catch (Exception $e) {
            $this->logger->error("Error updating user profile: " . $e->getMessage(), [
                'user_id' => $userId,
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * Update user role
     * 
     * @param string|int $userId
     * @param string $newRole
     * @return bool
     * @throws Exception If role is invalid
     */
    public function updateUserRole(string|int $userId, string $newRole): bool
    {
        try {
            // Validate role
            $validRoles = ['user', 'admin', 'super_admin'];
            if (!in_array($newRole, $validRoles)) {
                throw new Exception("Invalid role: {$newRole}");
            }

            return $this->update($userId, ['role' => $newRole]);
        } catch (Exception $e) {
            $this->logger->error("Error updating user role: " . $e->getMessage(), [
                'user_id' => $userId,
                'new_role' => $newRole,
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * Soft delete a user
     * 
     * @param string|int $userId
     * @return bool
     */
    public function deleteUser(string|int $userId): bool
    {
        try {
            // Fetch the user first to check role
            $user = $this->find($userId);
            
            // Return false if the user is a super admin
            if ($user && isset($user['role']) && $user['role'] === 'super_admin') {
                return false;
            }

            if (!$this->useSoftDeletes) {
                throw new Exception("Soft deletes not enabled for User model");
            }
            
            $data = ['deleted_at' => date('Y-m-d H:i:s')];
            $conditions = ['id' => $userId];
            
            $result = $this->dbHelper->update($this->table, $data, $conditions);
            
            if ($result && $this->auditService) {
                $this->auditService->logEvent('user', 'deleted', [
                    'id' => $userId
                ]);
            }
            
            return $result;
        } catch (Exception $e) {
            $this->logger->error("Error deleting user: " . $e->getMessage(), [
                'user_id' => $userId,
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * Change user password
     * 
     * @param string|int $userId 
     * @param string $currentPassword
     * @param string $newPassword
     * @return bool
     * @throws Exception If current password is invalid
     */
    public function changePassword(string|int $userId, string $currentPassword, string $newPassword): bool
    {
        try {
            // Get user first to verify current password
            $user = $this->find($userId);
            
            if (!$user) {
                throw new Exception("User not found");
            }
            
            // Verify current password
            if (!self::verifyPassword($currentPassword, $user['password_hash'])) {
                throw new Exception("Current password is incorrect");
            }
            
            // Update password
            return $this->updatePassword($userId, $newPassword);
        } catch (Exception $e) {
            $this->logger->error("Error changing password: " . $e->getMessage(), [
                'user_id' => $userId,
                'exception' => $e
            ]);
            throw $e;
        }
    }

    public function getRecentActivity(string|int $userId, int $limit = 5): array
    {
        $query = "SELECT activity_type, description, created_at 
                  FROM user_activities 
                  WHERE user_id = :user_id 
                  ORDER BY created_at DESC 
                  LIMIT $limit";
        return $this->dbHelper->select($query, [':user_id' => $userId]);
    }

    public function logActivity(string|int $userId, string $activityType, string $description, ?string $ipAddress = null): bool
    {
        $data = [
            'user_id'       => $userId,
            'activity_type' => $activityType,
            'description'   => $description,
            'created_at'    => date('Y-m-d H:i:s'),
            'ip_address'    => $ipAddress
        ];
        return (bool) $this->dbHelper->insert('user_activities', $data);
    }

    /**
     * Get users within a date range with optional filters
     *
     * @param string $start
     * @param string $end
     * @param array $filters
     * @return array
     */
    public function getByDateRange(string $start, string $end, array $filters = []): array
    {
        $query = "SELECT * FROM {$this->table} WHERE created_at BETWEEN :start AND :end";
        
        $params = [':start' => $start, ':end' => $end];
        
        if (!empty($filters['status'])) {
            $query .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
}
