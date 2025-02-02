<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends BaseModel
{
    protected string $table = 'vehicles';

    protected array $fillable = [
        'registration_number',
        'type',
        'status',
        'make',
        'model',
        'year',
    ];

    public static array $rules = [
        'registration_number' => 'required|string|unique:vehicles,registration_number',
        'type' => 'required|string',
        'status' => 'required|in:available,unavailable,maintenance',
        'make' => 'required|string|max:255',
        'model' => 'required|string|max:255',
        'year' => 'required|integer|min:1886|max:' . date('Y'),
    ];

    /**
     * Relationships
     */

    // Get vehicle's bookings
    public function bookings()
    {
        return $this->hasMany(Booking::class, 'vehicle_id', 'id');
    }

    /**
     * Scopes
     */

    // Scope a query to only include available vehicles
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    // Scope a query to only include vehicles of a specific type
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Events
     */

    // Actions to take after vehicle creation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($vehicle) {
            // Ensure vehicle status is properly managed
            if (empty($vehicle->status)) {
                $vehicle->status = 'available';
            }
        });
    }
}
