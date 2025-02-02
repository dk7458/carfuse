<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;


class BaseModel extends Model
{
    use SoftDeletes;

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically generate UUID for primary key
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });

        // Apply global scope for soft deletes
        static::addGlobalScope('softDeletes', function (Builder $builder) {
            $builder->whereNull('deleted_at');
        });

        // Apply global scope for default ordering
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('created_at', 'desc');
        });
    }

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the primary key.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * Log changes made to the model.
     *
     * @return void
     */
    protected static function bootLogging()
    {
        static::updated(function ($model) {
            // Log changes
        });
    }
}
