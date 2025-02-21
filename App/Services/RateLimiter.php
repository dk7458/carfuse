<?php

namespace App\Services;

use Psr\Log\LoggerInterface;
use App\Handlers\ExceptionHandler;

/**
 * Rate Limiter Service
 *
 * Implements IP-based rate limiting.
 */
class RateLimiter
{
    public const DEBUG_MODE = true;
    private LoggerInterface $logger;
    private ExceptionHandler $exceptionHandler;

    public function __construct(LoggerInterface $logger, ExceptionHandler $exceptionHandler)
    {
        $this->logger = $logger;
        $this->exceptionHandler = $exceptionHandler;
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
            if (self::DEBUG_MODE) {
                $this->logger->warning("[security] Rate limit exceeded for IP: {$ip}", ['category' => 'security']);
            }
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
        if (self::DEBUG_MODE) {
            $this->logger->info("[security] Recorded failed attempt for IP: {$ip}", ['category' => 'security']);
        }
    }
}
