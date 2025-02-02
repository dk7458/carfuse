<?php

namespace App\Models;

use PDO;
use App\Models\BaseModel;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RefundLog Model
 *
 * Represents a refund and handles interactions with the `refund_logs` table.
 */
class RefundLog extends BaseModel
{
    use SoftDeletes;

    protected $fillable = ['booking_id', 'amount', 'reason', 'status', 'user_id', 'payment_id'];

    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Validation rules for the model.
     *
     * @var array
     */
    public static $rules = [
        'booking_id' => 'required|exists:bookings,id',
        'amount' => 'required|numeric|min:0',
        'reason' => 'nullable|string',
        'status' => 'required|string|in:pending,approved,denied',
        'user_id' => 'required|exists:users,id',
        'payment_id' => 'required|exists:payments,id',
    ];

    /**
     * Get refund by ID.
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM refund_logs WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Get all refunds for a booking.
     */
    public function getByBookingId(int $bookingId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM refund_logs WHERE booking_id = :booking_id");
        $stmt->execute([':booking_id' => $bookingId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Create a refund record.
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO refund_logs (booking_id, amount, reason, status, created_at)
            VALUES (:booking_id, :amount, :reason, :status, NOW())
        ");
        $stmt->execute([
            ':booking_id' => $data['booking_id'],
            ':amount' => $data['amount'],
            ':reason' => $data['reason'] ?? '',
            ':status' => $data['status'] ?? 'pending',
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * Update refund status.
     */
    public function updateStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare("UPDATE refund_logs SET status = :status WHERE id = :id");
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }
    Report.php (Ensure Admin Reports Are Well-Structured)
    Prompt:
    
    Modify Report.php to:
    
    Extend BaseModel.php and use soft deletes.
    Implement query scopes:
    scopeByDateRange($query, $start, $end) → Fetch reports within date range.
    Ensure relationships:
    admin() → BelongsTo(Admin::class).
    Define fillable attributes for storing report metadata.
    /**
     * Define relationship with User.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Define relationship with Payment.
     *
     * @return BelongsTo
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
