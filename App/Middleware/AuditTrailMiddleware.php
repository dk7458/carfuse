<?php

namespace App\Middleware;

use AuditManager\Services\AuditService;
use Psr\Log\LoggerInterface;

/**
 * AuditTrailMiddleware - Logs user actions for audit tracking.
 */
class AuditTrailMiddleware
{
    private AuditService $auditService;
    private LoggerInterface $logger;

    public function __construct(AuditService $auditService, LoggerInterface $logger)
    {
        $this->auditService = $auditService;
        $this->logger = $logger;
    }

    /**
     * Handle an incoming request and log relevant details.
     *
     * @param array $request The request data.
     * @param callable $next The next middleware function.
     */
    public function handle(array $request, callable $next)
    {
        try {
            // Extract request details
            $action = $this->determineAction();
            $details = json_encode($this->sanitizeRequestData($request));
            $userId = $_SESSION['user_id'] ?? null;
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

            // Log the action
            $this->auditService->log($action, $details, $userId, $ipAddress);

            // Continue to the next middleware/controller
            return $next($request);
        } catch (\Exception $e) {
            $this->logger->error('[AuditTrail] Failed to log action', ['error' => $e->getMessage()]);
            return $next($request); // Allow the request to proceed even if logging fails
        }
    }

    /**
     * Determine the action performed based on the request.
     *
     * @return string
     */
    private function determineAction(): string
    {
        return $_SERVER['REQUEST_METHOD'] . ' ' . ($_SERVER['REQUEST_URI'] ?? 'unknown');
    }

    /**
     * Sanitize request data before logging.
     *
     * @param array $request The raw request data.
     * @return array The sanitized request data.
     */
    private function sanitizeRequestData(array $request): array
    {
        unset($request['password'], $request['token']); // Remove sensitive fields
        return $request;
    }
}
