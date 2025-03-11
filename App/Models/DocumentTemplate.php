<?php

namespace App\Models;

use App\Helpers\DatabaseHelper;
use App\Services\AuditService;
use Psr\Log\LoggerInterface;

/**
 * DocumentTemplate Model
 *
 * Manages templates for documents such as contracts, invoices, and Terms & Conditions.
 */
class DocumentTemplate extends BaseModel
{
    protected $table = 'document_templates';
    protected $resourceName = 'document_template';
    protected $useSoftDeletes = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'content',
        'description',
        'file_path'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Constructor
     *
     * @param DatabaseHelper $dbHelper
     * @param LoggerInterface $logger
     * @param AuditService $auditService
     */
    public function __construct(
        DatabaseHelper $dbHelper, 
        LoggerInterface $logger,
        AuditService $auditService
    )
    {
        parent::__construct($dbHelper, $auditService, $logger);
    }

    /**
     * Get the validation rules for the model.
     *
     * @return array
     */
    public static function validationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'content' => 'required|string',
            'file_path' => 'required|string|max:255',
        ];
    }

    /**
     * Find a template by its name
     * 
     * @param string $name The template name
     * @return array|null The template or null if not found
     */
    public function findByName(string $name): ?array
    {
        return $this->findOneBy('name', $name);
    }
}
