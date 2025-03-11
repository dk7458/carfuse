<?php

namespace App\Models;

use App\Helpers\DatabaseHelper;
use Psr\Log\LoggerInterface;

/**
 * Contract Model
 *
 * Handles contract specific database operations
 */
class Contract extends BaseModel
{
    protected $table = 'contracts';
    protected $resourceName = 'contract';

    /**
     * @var array The attributes that are mass assignable
     */
    protected $fillable = [
        'booking_id',
        'user_id',
        'content',
        'status'
    ];

    /**
     * @var array Data type casting definitions
     */
    protected $casts = [
        'booking_id' => 'int',
        'user_id' => 'int'
    ];
    
    /**
     * Constructor
     * 
     * @param DatabaseHelper $db Database helper instance
     * @param LoggerInterface $logger Logger instance
     */
    public function __construct(DatabaseHelper $db, LoggerInterface $logger)
    {
        parent::__construct($db, null, $logger);
    }
    
    /**
     * Get contract by booking ID
     * 
     * @param int $bookingId Booking ID
     * @return array|null Contract or null if not found
     */
    public function getByBookingId(int $bookingId): ?array
    {
        $query = "SELECT * FROM {$this->table} WHERE booking_id = :booking_id LIMIT 1";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':booking_id' => $bookingId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }
    
    /**
     * Get contracts by user ID
     * 
     * @param int $userId User ID
     * @return array Contracts
     */
    public function getByUserId(int $userId): array
    {
        $query = "SELECT * FROM {$this->table} WHERE user_id = :user_id ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }
}
