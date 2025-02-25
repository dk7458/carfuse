<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RefundLog Model
 *
 * Represents a refund and handles interactions with the `refund_logs` table.
 */
class RefundLog extends Model  // Directly extend Eloquent's Model for testing
{
    use SoftDeletes;

    protected $fillable = ['booking_id', 'amount', 'reason', 'status', 'user_id', 'payment_id'];

    /**
     * Validation rules for the model.
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
     * Relationship with User.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship with Payment.
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}