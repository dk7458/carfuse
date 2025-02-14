<?php

namespace App\Services;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;

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
        if (RateLimiter::tooManyAttempts($ip, 5)) {
            Log::channel('security')->warning("[RateLimit] Rate limit exceeded for IP: {$ip}");
            return true;
        }
        return false;
    }

    public function recordFailedAttempt(string $ip): void
    {
        RateLimiter::hit($ip, 900); // 900 seconds = 15 minutes
        Log::channel('security')->warning("[RateLimit] Recorded failed attempt for IP: {$ip}");
    }
}
