<?php

namespace App\Models;

use App\Models\BaseModel;
use App\Models\Admin;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Report Model
 *
 * Represents an admin report in the system.
 */
class Report extends BaseModel
{
    use SoftDeletes;

    /**
     * Attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'admin_id',
        'title',
        'content',
        'status',
        'created_at',
        'updated_at'
    ];

    /**
     * Relationships
     */

    /**
     * Get the admin who created the report.
     *
     * @return BelongsTo
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Scopes
     */

    /**
     * Scope a query to filter reports by a date range.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $start
     * @param string $end
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDateRange($query, string $start, string $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }
}
