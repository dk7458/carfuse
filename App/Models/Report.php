<?php

namespace App\Models;

use App\Services\DatabaseHelper;
use App\Services\AuditService;

/**
 * Report Model
 *
 * Represents an admin report in the system.
 */
class Report extends BaseModel
{
    protected $table = 'reports';
    protected $resourceName = 'report';
    protected $useSoftDeletes = true;

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
     * Get reports within a date range.
     *
     * @param string $start
     * @param string $end
     * @return array
     */
    public function getByDateRange(string $start, string $end): array
    {
        $query = "
            SELECT * FROM {$this->table}
            WHERE created_at BETWEEN :start AND :end
        ";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':start' => $start, ':end' => $end]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Get the admin who created the report.
     *
     * @param int $reportId
     * @return array|null
     */
    public function getAdmin(int $reportId): ?array
    {
        $report = $this->find($reportId);
        
        if (!$report || !isset($report['admin_id'])) {
            return null;
        }
        
        $query = "SELECT * FROM admins WHERE id = :admin_id";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':admin_id' => $report['admin_id']]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
}
