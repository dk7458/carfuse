<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\Payment;
use App\Traits\HasUuid;
use App\Traits\SoftDeletes;
use App\Helpers\HashHelper;

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
    use HasUuid, SoftDeletes;

    protected string $table = 'users';

    protected array $fillable = [
        'name',
        'surname',
        'email',
        'password',
        'role',
        'phone',
        'address',
    ];

    protected array $hidden = [
        'password_hash',
        'remember_token',
        'deleted_at',
    ];

    protected array $dates = [
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
        'phone' => 'required|string|max:20',
        'address' => 'required|string|max:255',
    ];

    /**
     * Relationships
     */

    // Get user's bookings
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'user_id', 'id');
    }

    // Get user's payments
    public function payments()
    {
        return $this->hasMany(Payment::class, 'user_id', 'id');
    }

    // Get user's notifications
    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id', 'id');
    }

    /**
     * Accessors
     */

    // Get user's full name
    public function getFullNameAttribute(): string
    {
        return "{$this->name} {$this->surname}";
    }

    /**
     * Mutators
     */

    // Set password (automatically hash)
    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password_hash'] = HashHelper::hash($value);
    }

    /**
     * Helpers
     */

    // Check if user is an admin
    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

    // Check if user is a super admin
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    // Check if user has a specific permission
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

    // Scope a query to only include active users
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    // Scope a query to only include users with a specific role
    public function scopeWithRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Events
     */

    // Actions to take after user creation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->id)) {
                $user->id = (string) \Ramsey\Uuid\Uuid::uuid4();
            }
        });

        static::deleting(function ($user) {
            // Perform any cleanup tasks like logging the deletion
        });
    }
}
