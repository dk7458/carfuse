<?php

namespace App\Models;

use App\Models\BaseModel; // updated base model
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Ramsey\Uuid\Uuid;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\AuditTrail;
use App\Models\Log;
use App\Models\Contract;
use App\Helpers\DatabaseHelper;
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
    use SoftDeletes;

    protected $table = 'users';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';
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

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'email_verified_at',
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

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'user_id', 'id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'user_id', 'id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id', 'id');
    }

    public function logs()
    {
        return $this->hasMany(Log::class, 'user_reference', 'id');
    }

    public function auditTrails()
    {
        return $this->hasMany(AuditTrail::class, 'user_reference', 'id');
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class, 'user_reference', 'id');
    }

    /**
     * Accessors
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->name} {$this->surname}";
    }

    /**
     * Mutators
     */
    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password_hash'] = Hash::make($value);
    }

    /**
     * Helpers
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function hasPermission(string $permission): bool
    {
        $rolePermissions = [
            'user' => ['read_own'],
            'admin' => ['read_own', 'read_all', 'write_all'],
            'super_admin' => ['read_own', 'read_all', 'write_all', 'delete_all'],
        ];

        return in_array($permission, $rolePermissions[$this->role] ?? []);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function scopeWithRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->id)) {
                $user->id = (string) Uuid::uuid4();
            }
        });

        static::updating(function ($user) {
            if ($user->isDirty('email')) {
                error_log("[SECURITY] User {$user->id} updated email to {$user->email}");
            }
        });

        static::deleting(function ($user) {
            error_log("[SECURITY] User {$user->id} was deleted.");
        });
    }

    /**
     * Create a new user.
     *
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        if (isset($data['password'])) {
            $data['password_hash'] = Hash::make($data['password']);
            unset($data['password']);
        }

        if ($this->useUuid && !isset($data['id'])) {
            $data['id'] = Uuid::uuid4()->toString();
        }

        return parent::create($data);
    }

    /**
     * Update a user.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        if (isset($data['password'])) {
            $data['password_hash'] = Hash::make($data['password']);
            unset($data['password']);
        }

        return parent::update($id, $data);
    }

    /**
     * Get bookings for a user.
     *
     * @param int $userId
     * @return array
     */
    public function getBookings(int $userId): array
    {
        $query = "SELECT * FROM bookings WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get payments for a user.
     *
     * @param int $userId
     * @return array
     */
    public function getPayments(int $userId): array
    {
        $query = "SELECT * FROM payments WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get notifications for a user.
     *
     * @param int $userId
     * @return array
     */
    public function getNotifications(int $userId): array
    {
        $query = "SELECT * FROM notifications WHERE user_id = :user_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get logs for a user.
     *
     * @param int $userId
     * @return array
     */
    public function getLogs(int $userId): array
    {
        $query = "SELECT * FROM logs WHERE user_reference = :user_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get audit trails for a user.
     *
     * @param int $userId
     * @return array
     */
    public function getAuditTrails(int $userId): array
    {
        $query = "SELECT * FROM audit_trails WHERE user_reference = :user_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get contracts for a user.
     *
     * @param int $userId
     * @return array
     */
    public function getContracts(int $userId): array
    {
        $query = "SELECT * FROM contracts WHERE user_reference = :user_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Find a user by email.
     *
     * @param string $email
     * @return User|null
     */
    public static function findByEmail(string $email): ?array
    {
        $user = self::where('email', $email)->first();
        return $user ? $user->toArray() : null;
    }
}
