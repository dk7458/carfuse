<?php

namespace App\Middleware;

use App\Services\AuditService;
use Psr\Log\LoggerInterface;

/**
 * @deprecated This middleware is deprecated and will be removed in a future version. 
 * Use AuditService methods directly in your controllers instead.
 * 
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
        
        // Log a warning about using deprecated middleware
        $this->logger->warning(
            'AuditTrailMiddleware is deprecated. Use AuditService methods directly in controllers instead.',
            ['trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)]
        );
    }

    /**
     * Handle an incoming request and log relevant details.
     *
     * @param array $request The request data.
     * @param callable $next The next middleware function.
     * @deprecated Use AuditService methods directly in your controllers instead
     */
    public function handle(array $request, callable $next)
    {
        try {
            // Extract request details
            $requestInfo = AuditService::getRequestInfo();
            $userId = $_SESSION['user_id'] ?? null;
            
            // Use the new API logging method
            $this->auditService->logApiRequest(
                $requestInfo['uri'],
                $requestInfo['method'],
                $this->sanitizeRequestData($request),
                [], // Response not available here
                200, // Status code not available yet
                $userId
            );

            // Continue to the next middleware/controller
            return $next($request);
        } catch (\Exception $e) {
            $this->logger->error('[AuditTrail] Failed to log action', ['error' => $e->getMessage()]);
            return $next($request); // Allow the request to proceed even if logging fails
        }
    }

    /**
     * Sanitize request data before logging.
     *
     * @param array $request The raw request data.
     * @return array The sanitized request data.
     */
    private function sanitizeRequestData(array $request): array
    {
        unset($request['password'], $request['token'], $request['credit_card'], $request['cvv']);
        return $request;
    }
}
