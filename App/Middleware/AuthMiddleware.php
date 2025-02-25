<?php

namespace App\Middleware;

use App\Helpers\ApiHelper;
use App\Services\TokenService;
use App\Helpers\ExceptionHandler;
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
    private TokenService $tokenService;
    private ExceptionHandler $exceptionHandler;
    private LoggerInterface $authLogger;
    private LoggerInterface $securityLogger;

    public function __construct(
        TokenService $tokenService,
        ExceptionHandler $exceptionHandler,
        LoggerInterface $authLogger,
        LoggerInterface $securityLogger
    ) {
        $this->tokenService = $tokenService;
        $this->exceptionHandler = $exceptionHandler;
        $this->authLogger = $authLogger;
        $this->securityLogger = $securityLogger;
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
        try {
            $token = $this->extractToken($request);

            if (!$token || !$this->tokenService->validateToken($token)) {
                $this->authLogger->warning("Invalid or missing token", ['ip' => $request->getServerParams()['REMOTE_ADDR']]);
                return ApiHelper::sendJsonResponse('error', 'Unauthorized', [], 401);
            }

            $user = $this->tokenService->getUserFromToken($token);
            $request = $request->withAttribute('user', $user);

            // Role-based access control
            if (!empty($roles) && !in_array($user->role, $roles)) {
                $this->securityLogger->warning("Unauthorized role access attempt.", [
                    'userId' => $user->id,
                    'requiredRoles' => $roles
                ]);
                return ApiHelper::sendJsonResponse('error', 'Forbidden', [], 403);
            }

            $this->authLogger->info("✅ User authenticated.", [
                'userId' => $user->id,
                'role' => $user->role
            ]);

            return $handler->handle($request);
        } catch (\Exception $e) {
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

        return $_COOKIE['jwt'] ?? null;
    }
}
?>
