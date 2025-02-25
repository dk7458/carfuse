<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Models\User;
use App\Models\Booking;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo; // Ensure this is included

/**
 * Payment Model
 *
 * Represents a payment transaction in the system.
 *
 * @property int $id Primary key
 * @property int $user_id ID of the user who made the payment
 * @property int $booking_id ID of the associated booking
 * @property float $amount Transaction amount
 * @property string $method Payment method (credit_card, PayPal, etc.)
 * @property string $status Status of the payment (pending, completed, failed)
 * @property string|null $transaction_id Unique external transaction identifier
 * @property \DateTime $created_at Timestamp when the record was created
 * @property \DateTime $updated_at Timestamp when the record was last updated
 * @property \DateTime|null $deleted_at Soft delete timestamp
 */
class Payment extends BaseModel
{
    use SoftDeletes;

    /**
     * Attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'booking_id',
        'amount',
        'method',
        'status',
        'transaction_id'
    ];

    /**
     * Attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at'
    ];

    /**
     * Validation rules for the model.
     *
     * @var array
     */
    public static $rules = [
        'user_id' => 'required|exists:users,id',
        'booking_id' => 'required|exists:bookings,id',
        'amount' => 'required|numeric|min:0',
        'method' => 'required|string|in:credit_card,paypal,bank_transfer',
        'status' => 'required|string|in:pending,completed,failed',
        'transaction_id' => 'nullable|string|max:255',
    ];

    /**
     * Relationships
     */

    /**
     * Get the user who made the payment.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the booking associated with the payment.
     *
     * @return BelongsTo
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Scopes
     */

    /**
     * Scope a query to filter payments by user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to fetch completed payments.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to filter payments by status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter payments by a date range.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
