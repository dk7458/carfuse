<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Log\LoggerInterface;

/**
 * HTMX middleware for special handling of HTMX requests
 */
class HtmxMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
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
        // Check if the request is from HTMX
        $isHtmxRequest = $request->getHeaderLine('HX-Request') === 'true';
        
        // Add HTMX-related attributes to the request
        $request = $request->withAttribute('isHtmxRequest', $isHtmxRequest);
        
        if ($isHtmxRequest) {
            $this->logger->debug('Processing HTMX request', [
                'uri' => $request->getUri()->getPath(),
                'method' => $request->getMethod(),
                'htmx-target' => $request->getHeaderLine('HX-Target'),
                'htmx-trigger' => $request->getHeaderLine('HX-Trigger')
            ]);
            
            // Add additional HTMX-specific attributes
            $request = $request
                ->withAttribute('htmxTarget', $request->getHeaderLine('HX-Target'))
                ->withAttribute('htmxTrigger', $request->getHeaderLine('HX-Trigger'))
                ->withAttribute('htmxTriggerName', $request->getHeaderLine('HX-Trigger-Name'))
                ->withAttribute('htmxCurrentUrl', $request->getHeaderLine('HX-Current-URL'));
        }
        
        // Process the request
        $response = $handler->handle($request);
        
        // For HTMX requests, ensure we have proper headers
        if ($isHtmxRequest) {
            // If not already set by a controller, set some defaults
            if (!$response->hasHeader('Cache-Control')) {
                $response = $response->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
            }
            
            // Add a CSRF token header if we have a session CSRF token
            if (isset($_SESSION['csrf_token']) && !$response->hasHeader('X-CSRF-Token')) {
                $response = $response->withHeader('X-CSRF-Token', $_SESSION['csrf_token']);
            }
        }
        
        return $response;
    }
}
