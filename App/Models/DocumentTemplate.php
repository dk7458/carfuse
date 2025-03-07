<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;
use App\Helpers\DatabaseHelper;
use Psr\Log\LoggerInterface;

/**
 * DocumentTemplate Model
 *
 * Manages templates for documents such as contracts, invoices, and Terms & Conditions.
 */
class DocumentTemplate extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'document_templates';
    protected $resourceName = 'document_template';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'content',
        'description'
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
        $query = "SELECT * FROM {$this->table} WHERE name = :name LIMIT 1";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':name' => $name]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Get all templates
     * 
     * @return array Array of templates
     */
    public function getAll(): array
    {
        $query = "SELECT * FROM {$this->table} ORDER BY name ASC";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Create a new document template
     * 
     * @param array $data Template data including name, content, file_path
     * @return int The ID of the newly created template
     */
    public function create(array $data): int
    {
        // Add timestamps
        if (!isset($data['created_at'])) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        // Insert record
        return parent::create($data);
    }

    /**
     * Update an existing template
     * 
     * @param int $id Template ID
     * @param array $data Updated template data
     * @return bool Success status
     */
    public function update(int $id, array $data): bool
    {
        // Update timestamp
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        return parent::update($id, $data);
    }
}
