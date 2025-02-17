<?php

namespace App\Services;

use Psr\Log\LoggerInterface;

/**
 * Rate Limiter Service
 *
 * Implements IP-based rate limiting.
 */
class RateLimiter
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function isRateLimited(string $ip): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['rate_limit'])) {
            $_SESSION['rate_limit'] = [];
        }
        $attempts = $_SESSION['rate_limit'][$ip] ?? 0;
        if ($attempts >= 5) {
            $this->logger->warning("[RateLimit] Rate limit exceeded for IP: {$ip}", ['category' => 'auth']);
            return true;
        }
        return false;
    }

    public function recordFailedAttempt(string $ip): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['rate_limit'])) {
            $_SESSION['rate_limit'] = [];
        }
        $_SESSION['rate_limit'][$ip] = ($_SESSION['rate_limit'][$ip] ?? 0) + 1;
        $this->logger->info("[RateLimit] Recorded failed attempt for IP: {$ip}", ['category' => 'auth']);
    }
}
