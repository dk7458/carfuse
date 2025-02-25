<?php

namespace App\Middleware;

use App\Services\Auth\AuthService;
use App\Helpers\ApiHelper;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Log\LoggerInterface;

class TokenValidationMiddleware
{
    private AuthService $authService;
    private LoggerInterface $logger;

    public function __construct(AuthService $authService, LoggerInterface $logger)
    {
        $this->authService = $authService;
        $this->logger = $logger;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $token = $this->extractToken($request);

        if (!$token || !$this->authService->validateToken($token)) {
            $this->logger->warning("Invalid or missing token", ['ip' => $request->getServerParams()['REMOTE_ADDR']]);
            return ApiHelper::sendJsonResponse('error', 'Unauthorized', [], 401);
        }

        $user = $this->authService->getUserFromToken($token);
        $request = $request->withAttribute('user', $user);

        return $handler->handle($request);
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
