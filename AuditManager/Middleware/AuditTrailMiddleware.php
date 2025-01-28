<?php

namespace AuditManager\Middleware;

use AuditManager\Services\AuditService;
use Psr\Log\LoggerInterface;

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
     * Handle an incoming request.
     * Logs relevant details to the audit trail.
     */
    public function handle(array $request, callable $next)
    {
        try {
            // Extract relevant data from the request
            $action = $this->determineAction($request);
            $details = json_encode($request); // Sanitize or limit this as necessary
            $userId = $request['user_id'] ?? null;
            $bookingId = $request['booking_id'] ?? null;
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

            // Log the action
            $this->auditService->log($action, $details, $userId, $bookingId, $ipAddress);

            // Continue to the next middleware/controller
            return $next($request);
        } catch (\Exception $e) {
            $this->logger->error('Failed to log action in audit trail', ['error' => $e->getMessage()]);
            // Allow the request to proceed even if logging fails
            return $next($request);
        }
    }

    /**
     * Determine the action based on the request.
     */
    private function determineAction(array $request): string
    {
        // Determine action based on the request (e.g., URL, HTTP method, etc.)
        $action = $_SERVER['REQUEST_METHOD'] . ' ' . ($_SERVER['REQUEST_URI'] ?? 'unknown');
        return $action;
    }
}
