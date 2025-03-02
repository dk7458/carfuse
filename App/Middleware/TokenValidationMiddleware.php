<?php

namespace App\Middleware;

use App\Services\Auth\AuthService;
use App\Services\Auth\TokenService;
use App\Helpers\ApiHelper;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Log\LoggerInterface;

class TokenValidationMiddleware
{
    private AuthService $authService;
    private LoggerInterface $logger;
    private TokenService $tokenService;

    public function __construct(
        AuthService $authService, 
        LoggerInterface $logger,
        TokenService $tokenService
    ) {
        $this->authService = $authService;
        $this->logger = $logger;
        $this->tokenService = $tokenService;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // Use TokenService to extract and validate the token
        $user = $this->tokenService->validateRequest($request);

        if (!$user) {
            $this->logger->warning("Invalid or missing token", ['ip' => $request->getServerParams()['REMOTE_ADDR']]);
            return ApiHelper::sendJsonResponse('error', 'Unauthorized', [], 401);
        }

        $request = $request->withAttribute('user', $user);

        return $handler->handle($request);
    }
}
?>
