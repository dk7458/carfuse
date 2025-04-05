<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Log\LoggerInterface;

class CsrfMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;
    private array $excludedPaths;
    
    public function __construct(LoggerInterface $logger, array $excludedPaths = [])
    {
        $this->logger = $logger;
        $this->excludedPaths = $excludedPaths;
    }

    public function process(Request $request, RequestHandler $handler): Response
    {
        // Start session if not started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Generate CSRF token if not exists
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        // Skip CSRF check for excluded paths
        $path = $request->getUri()->getPath();
        foreach ($this->excludedPaths as $excludedPath) {
            if (strpos($path, $excludedPath) === 0) {
                return $handler->handle($request);
            }
        }
        
        // Skip CSRF check for GET, HEAD, OPTIONS requests
        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $handler->handle($request);
        }
        
        // Check CSRF token
        $csrfToken = $this->getCsrfTokenFromRequest($request);
        if (!$csrfToken || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
            $this->logger->warning('CSRF token validation failed', [
                'path' => $path,
                'method' => $request->getMethod(),
                'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $request->getHeaderLine('User-Agent')
            ]);
            
            // Create 403 Forbidden response
            $response = new \Slim\Psr7\Response();
            
            // Check if it's an HTMX request
            $isHtmxRequest = $request->getHeaderLine('HX-Request') === 'true';
            
            if ($isHtmxRequest) {
                // For HTMX requests, send an HX-Trigger to show a toast message
                $response = $response->withHeader('HX-Trigger', json_encode([
                    'show-toast' => [
                        'title' => 'Błąd walidacji',
                        'message' => 'Token CSRF wygasł lub jest nieprawidłowy. Odśwież stronę i spróbuj ponownie.',
                        'type' => 'error',
                        'duration' => 5000
                    ]
                ]));
                
                // Also set the HX-Refresh header to suggest a page refresh
                $response = $response->withHeader('HX-Refresh', 'true');
                
                return $response->withStatus(403);
            }
            
            // For regular requests, respond with JSON error
            $response->getBody()->write(json_encode([
                'error' => 'CSRF token validation failed',
                'message' => 'Security token is invalid or expired. Please refresh the page and try again.'
            ]));
            
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(403);
        }
        
        // CSRF validation passed, continue processing
        return $handler->handle($request);
    }

    /**
     * Get CSRF token from request
     * 
     * @param Request $request
     * @return string|null
     */
    private function getCsrfTokenFromRequest(Request $request): ?string
    {
        // Get from header (typical AJAX/HTMX requests)
        $token = $request->getHeaderLine('X-CSRF-TOKEN');
        
        // If not in header, try form data (regular form submissions) or POST body
        if (empty($token)) {
            $params = $request->getParsedBody();
            
            if (is_array($params) && isset($params['_token'])) {
                $token = $params['_token'];
            }
        }
        
        // If still not found, check for a query parameter (less secure, but sometimes used)
        if (empty($token)) {
            $queryParams = $request->getQueryParams();
            if (isset($queryParams['_token'])) {
                $token = $queryParams['_token'];
            }
        }
        
        return $token;
    }
}
