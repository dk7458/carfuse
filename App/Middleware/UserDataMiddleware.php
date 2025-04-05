<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Log\LoggerInterface;

/**
 * Middleware that loads user data from session into request attributes
 * To be used after SessionMiddleware has processed the request
 */
class UserDataMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;
    private bool $required;

    /**
     * @param LoggerInterface $logger
     * @param bool $required Whether user data is required (returns 401 if missing when true)
     */
    public function __construct(LoggerInterface $logger, bool $required = false)
    {
        $this->logger = $logger;
        $this->required = $required;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        // Get session data using SessionMiddleware helper
        $session = SessionMiddleware::getSession($request);
        $userData = $session['user'] ?? null;
        
        // If we have user data in session, add it to request attributes
        if ($userData) {
            $this->logger->debug("User data loaded from session", [
                'user_id' => $userData['id'] ?? 'unknown'
            ]);
            
            // Attach user data to request
            $request = $request->withAttribute('user', $userData);
        } else {
            $this->logger->debug("No user data found in session");
            
            // If user data is required but not found, return 401 Unauthorized
            if ($this->required) {
                $this->logger->warning("User data required but not found in session");
                
                $response = new \Slim\Psr7\Response();
                $response->getBody()->write(json_encode([
                    'error' => 'Authentication required',
                    'status' => 401
                ]));
                
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(401);
            }
        }
        
        // Continue to the next middleware/handler
        return $handler->handle($request);
    }
    
    /**
     * Helper method to set user data in both session and request
     *
     * @param Request $request
     * @param array $userData
     * @return Request Updated request with user data
     */
    public static function setUserData(Request $request, array $userData): Request
    {
        // First update the session
        $session = SessionMiddleware::getSession($request);
        $session['user'] = $userData;
        $request = SessionMiddleware::setSession($request, $session);
        
        // Then set the user attribute directly
        return $request->withAttribute('user', $userData);
    }
    
    /**
     * Helper method to clear user data from both session and request
     *
     * @param Request $request
     * @return Request Updated request without user data
     */
    public static function clearUserData(Request $request): Request
    {
        // First update the session
        $session = SessionMiddleware::getSession($request);
        unset($session['user']);
        $request = SessionMiddleware::setSession($request, $session);
        
        // Then remove the user attribute if it exists
        if ($request->getAttribute('user') !== null) {
            $request = $request->withoutAttribute('user');
        }
        
        return $request;
    }
}
