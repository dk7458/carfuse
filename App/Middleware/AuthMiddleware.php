<?php

namespace App\Middleware;

use App\Services\Auth\TokenService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Log\LoggerInterface;
use App\Helpers\DatabaseHelper;

class AuthMiddleware implements MiddlewareInterface
{
    private TokenService $tokenService;
    private LoggerInterface $logger;
    private $pdo;
    private bool $required;

    public function __construct(
        TokenService $tokenService, 
        LoggerInterface $logger,
        DatabaseHelper $dbHelper,
        bool $required = false
    ) {
        $this->tokenService = $tokenService;
        $this->logger = $logger;
        $this->pdo = $dbHelper->getPdo();
        $this->required = $required;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        $this->logger->debug("AuthMiddleware processing request", [
            'required_auth' => $this->required ? 'yes' : 'no'
        ]);
        
        // Try to get token from Authorization header
        $token = null;
        $authHeader = $request->getHeaderLine('Authorization');
        if (strpos($authHeader, 'Bearer ') === 0) {
            $token = substr($authHeader, 7);
            $this->logger->debug("Found token in Authorization header");
        }
        
        // If not in header, try cookies
        if (!$token) {
            $cookies = $request->getCookieParams();
            $token = $cookies['jwt'] ?? null;
            if ($token) {
                $this->logger->debug("Found token in cookies");
            }
        }
        
        $authenticated = false;
        
        if ($token) {
            try {
                // Verify and decode the token
                $decoded = $this->tokenService->verifyToken($token);
                $userId = $decoded['sub'];
                $this->logger->debug("Token verified successfully", ['userId' => $userId]);
                
                // Fetch user from database
                $stmt = $this->pdo->prepare("
                    SELECT id, name, surname, email, phone, role, address, 
                           pesel_or_id, created_at, email_notifications, sms_notifications 
                    FROM users WHERE id = ? AND active = 1
                ");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Attach user to request
                    $this->logger->debug("User attached to request", ['userId' => $user['id']]);
                    $request = $request->withAttribute('user', $user);
                    $authenticated = true;
                } else {
                    $this->logger->warning("User not found or inactive", ['userId' => $userId]);
                }
            } catch (\Exception $e) {
                $this->logger->warning("Token validation failed: " . $e->getMessage());
                // We'll proceed without setting the user attribute
            }
        } else {
            $this->logger->debug("No token found in request");
        }
        
        // If authentication is required but failed, return 401 Unauthorized
        if ($this->required && !$authenticated) {
            $this->logger->warning("Authentication required but failed or missing");
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode([
                'error' => 'Authentication required',
                'status' => 401
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }
        
        return $handler->handle($request);
    }
}
?>
