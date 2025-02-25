<?php

namespace App\Middleware;

use App\Helpers\ApiHelper;
use App\Services\Auth\AuthService;
use App\Helpers\ExceptionHandler;
use App\Helpers\LoggingHelper;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * AuthMiddleware - Handles authentication and authorization for API requests.
 * Ensures valid JWT tokens and role-based access control.
 */
class AuthMiddleware
{
    private AuthService $authService;
    private ExceptionHandler $exceptionHandler;
    private LoggingHelper $loggingHelper;

    public function __construct(
        AuthService $authService,
        ExceptionHandler $exceptionHandler,
        LoggingHelper $loggingHelper
    ) {
        $this->authService = $authService;
        $this->exceptionHandler = $exceptionHandler;
        $this->loggingHelper = $loggingHelper;
    }

    /**
     * Handle authentication and authorization.
     * 
     * @param Request $request The incoming request.
     * @param RequestHandler $handler The next middleware function.
     * @param array $roles Required roles (e.g., 'admin').
     * @return Response
     */
    public function __invoke(Request $request, RequestHandler $handler, ...$roles): Response
    {
        $logger = $this->loggingHelper->getLoggerByCategory('auth');

        // Log the incoming request with contextual information
        $logger->info('Incoming request', [
            'ip' => $request->getServerParams()['REMOTE_ADDR'],
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);

        try {
            $token = $this->extractToken($request);

            if (!$token || !$this->authService->validateToken($token)) {
                $logger->warning("Invalid or missing token", ['ip' => $request->getServerParams()['REMOTE_ADDR']]);
                return ApiHelper::sendJsonResponse('error', 'Unauthorized', [], 401);
            }

            $user = $this->authService->getUserFromToken($token);
            $request = $request->withAttribute('user', $user);

            // Role-based access control
            if (!empty($roles) && !in_array($user->role, $roles)) {
                $logger->warning("Unauthorized role access attempt.", [
                    'userId' => $user->id,
                    'requiredRoles' => $roles
                ]);
                return ApiHelper::sendJsonResponse('error', 'Forbidden', [], 403);
            }

            $logger->info("✅ User authenticated.", [
                'userId' => $user->id,
                'role' => $user->role
            ]);

            return $handler->handle($request);
        } catch (\Exception $e) {
            $logger->error('Token validation failed', [
                'ip' => $request->getServerParams()['REMOTE_ADDR'],
                'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
                'error' => $e->getMessage(),
            ]);
            $this->exceptionHandler->handleException($e);
            return ApiHelper::sendJsonResponse('error', 'Internal Server Error', [], 500);
        }
    }

    private function extractToken(Request $request): ?string
    {
        $authHeader = $request->getHeaderLine('Authorization');
        if (strpos($authHeader, 'Bearer ') === 0) {
            return substr($authHeader, 7);
        }

        return $request->getCookieParams()['token'] ?? null;
    }
}
?>
