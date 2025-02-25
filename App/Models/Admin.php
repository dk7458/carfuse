<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

/**
 * Admin Model - Manages system administrators.
 * 
 * Features:
 * - Extends Laravel's Authenticatable for authentication.
 * - Implements SoftDeletes to allow admin recovery.
 * - Provides relationships for managing users and audit logs.
 */
class Admin extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * ✅ Table name.
     */
    protected $table = 'admins';

    /**
     * ✅ Mass assignable attributes.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role'
    ];

    /**
     * ✅ Hidden attributes for security.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * ✅ Attribute casting.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * ✅ Mutator: Automatically hash password before saving.
     */
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    /**
     * ✅ Relationship: Admin can have multiple audit logs.
     */
    public function logs()
    {
        return $this->hasMany(AuditLog::class, 'admin_id');
    }

    /**
     * ✅ Relationship: Admin manages multiple users.
     */
    public function managedUsers()
    {
        return $this->hasMany(User::class, 'managed_by');
    }
}
