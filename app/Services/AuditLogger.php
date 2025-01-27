<?php

namespace App\Services;

use PDO;

/**
 * Audit Logger Service
 *
 * Logs important user actions.
 */
class AuditLogger
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Log an action.
     */
    public function log(string $action, array $details): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO logs (action, details, timestamp) 
            VALUES (:action, :details, NOW())
        ");
        $stmt->execute([
            'action' => $action,
            'details' => json_encode($details),
        ]);
    }
}
