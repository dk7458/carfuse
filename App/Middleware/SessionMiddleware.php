<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Log\LoggerInterface;

class SessionMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;
    private array $config;

    /**
     * SessionMiddleware constructor
     * 
     * @param LoggerInterface $logger For logging session events
     * @param array $config Configuration options for session
     */
    public function __construct(LoggerInterface $logger, array $config = [])
    {
        $this->logger = $logger;
        
        // Default session configuration with secure settings
        $this->config = array_merge([
            'name' => 'carfuse_session',
            'lifetime' => 7200,           // 2 hours
            'path' => '/',
            'domain' => '',
            'secure' => true,             // Only transmit over HTTPS
            'httponly' => true,           // Not accessible via JavaScript
            'samesite' => 'Lax',          // Protects against CSRF
            'regenerate_interval' => 1800 // 30 minutes
        ], $config);
    }

    /**
     * Process an incoming server request and return a response
     *
     * @param Request $request
     * @param RequestHandler $handler
     * @return Response
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        $this->logger->debug("SessionMiddleware initializing session");
        
        // Configure session
        $this->configureSession();
        
        // Start the session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Regenerate session ID periodically to prevent fixation attacks
        $this->regenerateSessionIfNeeded();
        
        // Add session data to request attributes instead of using $_SESSION globally
        $session = $_SESSION ?? [];
        $request = $request->withAttribute('session', $session);
        
        $this->logger->debug("Session initialized", [
            'session_id' => session_id(),
            'user_id' => $session['user_id'] ?? 'guest'
        ]);
        
        // Process the request and get the response
        $response = $handler->handle($request);
        
        // Update session with any changes made to the session attribute
        $this->updateSessionFromRequest($request);
        
        return $response;
    }
    
    /**
     * Configure secure session settings
     */
    private function configureSession(): void
    {
        // Set secure session parameters
        session_name($this->config['name']);
        
        // Set session cookie parameters
        session_set_cookie_params([
            'lifetime' => $this->config['lifetime'],
            'path' => $this->config['path'],
            'domain' => $this->config['domain'],
            'secure' => $this->config['secure'],
            'httponly' => $this->config['httponly'],
            'samesite' => $this->config['samesite']
        ]);
        
        // Other security measures
        ini_set('session.use_strict_mode', 1);        // Only use sessions created by the server
        ini_set('session.use_only_cookies', 1);       // Only use cookies to store session IDs
        ini_set('session.gc_maxlifetime', $this->config['lifetime']);
    }
    
    /**
     * Regenerate session ID periodically
     */
    private function regenerateSessionIfNeeded(): void
    {
        if (isset($_SESSION['CREATED']) && 
            (time() - $_SESSION['CREATED'] > $this->config['regenerate_interval'])) {
            // Session started more than configured interval ago
            session_regenerate_id(true);    // Change session ID and invalidate old session ID
            $_SESSION['CREATED'] = time();  // Update creation time
            $this->logger->debug("Session ID regenerated for security");
        } else if (!isset($_SESSION['CREATED'])) {
            $_SESSION['CREATED'] = time();
        }
    }
    
    /**
     * Update the actual session from request attribute
     * This ensures any modifications to the session attribute are persisted
     */
    private function updateSessionFromRequest(Request $request): void
    {
        $sessionData = $request->getAttribute('session');
        if (is_array($sessionData)) {
            $_SESSION = $sessionData;
        }
    }
    
    /**
     * Helper method to retrieve session data from request
     * Can be used in controllers to avoid direct $_SESSION access
     * 
     * @param Request $request
     * @return array Session data
     */
    public static function getSession(Request $request): array
    {
        return $request->getAttribute('session', []);
    }
    
    /**
     * Helper method to set session data in the request
     * 
     * @param Request $request
     * @param array $sessionData
     * @return Request Updated request with new session data
     */
    public static function setSession(Request $request, array $sessionData): Request
    {
        return $request->withAttribute('session', $sessionData);
    }
}
