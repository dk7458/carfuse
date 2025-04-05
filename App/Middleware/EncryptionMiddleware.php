<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Log\LoggerInterface;
use App\Services\EncryptionService;

class EncryptionMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;
    private EncryptionService $encryptionService;
    
    public function __construct(LoggerInterface $logger, EncryptionService $encryptionService)
    {
        $this->logger = $logger;
        $this->encryptionService = $encryptionService;
    }
    
    public function process(Request $request, RequestHandler $handler): Response
    {
        $uri = $request->getUri()->getPath();
        
        // Process request data if it's a sensitive endpoint
        if ($this->isSensitiveEndpoint($uri)) {
            $parsedBody = $request->getParsedBody();
            if (is_array($parsedBody)) {
                $encryptedData = $this->encryptRequestData($parsedBody);
                $request = $request->withParsedBody($encryptedData);
            }
        }
        
        // Pass request to next middleware/handler
        $response = $handler->handle($request);
        
        // Process response data if needed
        if ($this->isSensitiveEndpoint($uri)) {
            $responseBody = (string) $response->getBody();
            if (!empty($responseBody)) {
                $encryptedResponse = $this->encryptResponseData($responseBody);
                $response = $this->createJsonResponse($response, $encryptedResponse);
            }
        }
        
        return $response;
    }
    
    private function encryptResponseData(string $data): string
    {
        return $this->encryptionService->encrypt($data);
    }

    private function encryptRequestData(array $request): array
    {
        foreach ($request as $key => $value) {
            if ($this->isSensitiveField($key)) {
                $request[$key] = $this->encryptionService->encrypt($value);
            }
        }
        return $request;
    }

    private function isSensitiveField(string $field): bool
    {
        $configPath = __DIR__ . '/../../config/sensitive_fields.json';
        $config = file_exists($configPath) ? json_decode(file_get_contents($configPath), true) : [];
        $sensitiveFields = $config['sensitive_fields'] ?? ['password', 'email', 'phone'];
        return in_array($field, $sensitiveFields);
    }

    private function isSensitiveEndpoint(string $endpoint): bool
    {
        $configPath = __DIR__ . '/../../config/sensitive_endpoints.json';
        $config = file_exists($configPath) ? json_decode(file_get_contents($configPath), true) : [];
        $sensitiveEndpoints = $config['sensitive_endpoints'] ?? ['/user/profile-data'];
        return in_array($endpoint, $sensitiveEndpoints);
    }
    
    private function createJsonResponse(Response $response, string $data): Response
    {
        $response = $response->withHeader('Content-Type', 'application/json');
        $body = $response->getBody();
        $body->rewind();
        $body->write($data);
        return $response;
    }
    
    private function logEvent(string $message): void
    {
        $this->logger->info("[EncryptionMiddleware] $message");
    }
}
