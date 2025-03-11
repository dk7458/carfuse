<?php

namespace App\Models;

use App\Helpers\DatabaseHelper;
use App\Services\AuditService;

/**
 * Vehicle Model
 *
 * Represents a vehicle in the system.
 */
class Vehicle extends BaseModel
{
    protected $table = 'vehicles';
    protected $resourceName = 'vehicle';
    protected $useSoftDeletes = false; // Vehicles use hard deletes

    /**
     * Validation rules for the model.
     *
     * @var array
     */
    public static $rules = [
        'registration_number' => 'required|string|unique:vehicles,registration_number',
        'type' => 'required|string',
        'status' => 'required|in:available,unavailable,maintenance',
        'make' => 'required|string|max:255',
        'model' => 'required|string|max:255',
        'year' => 'required|integer|min:1886|max:' . PHP_INT_MAX,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'registration_number',
        'type',
        'status',
        'make',
        'model',
        'year',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'year' => 'integer',
    ];

    /**
     * Find available vehicles
     *
     * @return array
     */
    public function findAvailable(): array
    {
        $query = "SELECT * FROM {$this->table} WHERE status = :status";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':status' => 'available']);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Find vehicles by type
     *
     * @param string $type
     * @return array
     */
    public function findByType(string $type): array
    {
        $query = "SELECT * FROM {$this->table} WHERE type = :type";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':type' => $type]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get vehicle's bookings
     *
     * @param int $vehicleId
     * @return array
     */
    public function getBookings(int $vehicleId): array
    {
        $query = "SELECT * FROM bookings WHERE vehicle_id = :vehicle_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':vehicle_id' => $vehicleId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
    
    /**
     * Set a vehicle to maintenance status
     *
     * @param int $id
     * @param string $reason
     * @return bool
     */
    public function setToMaintenance(int $id, string $reason = ''): bool
    {
        $result = $this->update($id, ['status' => 'maintenance']);
        
        // Add custom audit logging for maintenance status
        if ($result && $this->auditService) {
            $this->auditService->logEvent($this->resourceName, 'vehicle_maintenance', [
                'vehicle_id' => $id,
                'reason' => $reason
            ]);
        }
        
        return $result;
    }
    
    /**
     * Set a vehicle to available status
     *
     * @param int $id
     * @return bool
     */
    public function setToAvailable(int $id): bool
    {
        return $this->update($id, ['status' => 'available']);
    }
}
