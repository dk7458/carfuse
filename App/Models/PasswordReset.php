<?php

namespace App\Models;

use App\Services\DatabaseHelper;
use App\Services\AuditService;

/**
 * PasswordReset Model
 *
 * Represents a password reset request.
 */
class PasswordReset extends BaseModel
{
    protected $table = 'password_resets';
    protected $resourceName = 'password_reset';
    protected $useTimestamps = true;
    protected $useSoftDeletes = false;

    /**
     * Find a password reset by token.
     *
     * @param string $token
     * @return array|null
     */
    public function findByToken(string $token): ?array
    {
        $query = "SELECT * FROM {$this->table} WHERE token = :token";
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([':token' => $token]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Create a new password reset.
     *
     * @param array $data
     * @return int
     */
    public function create(array $data): int
    {
        // Generate a token if not provided
        if (!isset($data['token'])) {
            $data['token'] = bin2hex(random_bytes(32));
        }

        $id = parent::create($data);

        // Add custom audit logging if needed
        if ($this->auditService) {
            $this->auditService->logEvent($this->resourceName, 'password_reset_created', [
                'id' => $id,
                'email' => $data['email'] ?? null
            ]);
        }

        return $id;
    }
}
