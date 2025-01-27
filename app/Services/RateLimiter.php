<?php

namespace App\Services;

use PDO;

/**
 * Rate Limiter Service
 *
 * Implements IP-based rate limiting.
 */
class RateLimiter
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Check if an IP is rate-limited.
     */
    public function isRateLimited(string $ip): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM login_attempts 
            WHERE ip_address = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
        ");
        $stmt->execute([$ip]);

        return $stmt->fetchColumn() >= 5;
    }

    /**
     * Record a failed login attempt.
     */
    public function recordFailedAttempt(string $ip): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO login_attempts (ip_address, created_at) 
            VALUES (?, NOW())
        ");
        $stmt->execute([$ip]);
    }
}
