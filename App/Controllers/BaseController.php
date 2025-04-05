<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;

abstract class BaseController 
{
    protected LoggerInterface $logger;
    protected ExceptionHandler $exceptionHandler;
    protected ServerRequestInterface $request;
    
    public function __construct(
        LoggerInterface $logger,
        ExceptionHandler $exceptionHandler
    ) {
        $this->logger = $logger;
        $this->exceptionHandler = $exceptionHandler;
    }
    
    /**
     * Set the current request
     *
     * @param ServerRequestInterface $request
     */
    public function setRequest(ServerRequestInterface $request)
    {
        $this->request = $request;
        
        // Store is HTMX request for use throughout the controller
        $this->isHtmxRequest = $request->getHeaderLine('HX-Request') === 'true';
    }
    
    /**
     * Check if the current request is from HTMX
     *
     * @return bool
     */
    protected function isHtmxRequest(): bool
    {
        return isset($this->request) && 
               $this->request->getHeaderLine('HX-Request') === 'true';
    }
    
    /**
     * Create a JSON response
     *
     * @param mixed $data The data to encode as JSON
     * @param int $status The response status code
     * @return ResponseInterface
     */
    protected function jsonResponse($data, $status = 200): ResponseInterface
    {
        $response = new \Slim\Psr7\Response($status);
        $response->getBody()->write(json_encode($data));
        
        return $response
            ->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * Add HTMX-specific headers to a response
     *
     * @param ResponseInterface $response The response to modify
     * @param array $headers HTMX-specific headers to add
     * @return ResponseInterface
     */
    protected function withHtmxHeaders(ResponseInterface $response, array $headers = []): ResponseInterface
    {
        if (!$this->isHtmxRequest()) {
            return $response;
        }
        
        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }
        
        return $response;
    }
    
    /**
     * Create a response for redirecting via HTMX
     *
     * @param string $url The URL to redirect to
     * @param int $status The response status code
     * @return ResponseInterface
     */
    protected function htmxRedirect(string $url, int $status = 200): ResponseInterface
    {
        $response = new \Slim\Psr7\Response($status);
        
        return $this->withHtmxHeaders($response, [
            'HX-Redirect' => $url
        ]);
    }
    
    /**
     * Create a response for triggering an HTMX event
     *
     * @param string $event The event name to trigger
     * @param mixed $data The event data
     * @param int $status The response status code
     * @return ResponseInterface
     */
    protected function htmxTrigger(string $event, $data = null, int $status = 200): ResponseInterface
    {
        $response = new \Slim\Psr7\Response($status);
        
        if ($data !== null) {
            $eventDetail = json_encode(['event' => $event, 'detail' => $data]);
            return $this->withHtmxHeaders($response, [
                'HX-Trigger' => $eventDetail
            ]);
        }
        
        return $this->withHtmxHeaders($response, [
            'HX-Trigger' => $event
        ]);
    }
    
    /**
     * Create a response for displaying a toast notification via HTMX
     *
     * @param string $title The toast title
     * @param string $message The toast message
     * @param string $type The toast type (success, error, warning, info)
     * @param int $duration The toast duration in milliseconds
     * @param int $status The response status code
     * @return ResponseInterface
     */
    protected function htmxToast(string $title, string $message, string $type = 'info', int $duration = 5000, int $status = 200): ResponseInterface
    {
        $response = new \Slim\Psr7\Response($status);
        
        $eventDetail = json_encode([
            'show-toast' => [
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'duration' => $duration
            ]
        ]);
        
        return $this->withHtmxHeaders($response, [
            'HX-Trigger' => $eventDetail
        ]);
    }
    
    /**
     * Return a view response, taking into account if it's an HTMX request
     *
     * @param string $view The view to render
     * @param array $data The data to pass to the view
     * @param int $status The response status code
     * @return ResponseInterface
     */
    protected function view(string $view, array $data = [], int $status = 200): ResponseInterface
    {
        $response = new \Slim\Psr7\Response($status);
        
        // If HTMX request, render the partial view only
        if ($this->isHtmxRequest()) {
            ob_start();
            extract($data);
            include BASE_PATH . "/public/views/partials/{$view}.php";
            $output = ob_get_clean();
            
            $response->getBody()->write($output);
            return $response;
        }
        
        // Otherwise, render with full layout
        $content = $this->renderView($view, $data);
        
        ob_start();
        extract($data);
        $content = ob_get_clean();
        
        ob_start();
        include BASE_PATH . "/public/views/layouts/base.php";
        $output = ob_get_clean();
        
        $response->getBody()->write($output);
        return $response;
    }
    
    /**
     * Render a view
     *
     * @param string $view The view to render
     * @param array $data The data to pass to the view
     * @return string The rendered view
     */
    private function renderView(string $view, array $data = []): string
    {
        ob_start();
        extract($data);
        include BASE_PATH . "/public/views/{$view}.php";
        return ob_get_clean();
    }
}
