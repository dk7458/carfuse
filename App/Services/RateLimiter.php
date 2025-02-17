<?php

namespace App\Services;

// ...existing imports removed (no RateLimiter or Log) ...

/**
 * Rate Limiter Service
 *
 * Implements IP-based rate limiting.
 */
class RateLimit
{
    // Removed PDO dependency

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
            error_log("[RateLimit] Rate limit exceeded for IP: {$ip}");
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
        error_log("[RateLimit] Recorded failed attempt for IP: {$ip}");
    }
}
