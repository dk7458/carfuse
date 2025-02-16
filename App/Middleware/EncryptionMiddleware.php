<?php

namespace App\Middleware;

// Removed: use Illuminate\Http\Request;
// Removed: use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use App\Services\EncryptionService;

class EncryptionMiddleware
{
    private LoggerInterface $logger;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    
    // Modified handle() to use native PHP request handling without Closure
    public function handle(array $request)
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        if ($this->isSensitiveEndpoint($uri)) {
            $this->encryptRequestData($request);
        }

        // Process request (replace with your actual request processing)
        // ...existing code or processRequest($request)...
        $response = []; // Placeholder for processed response
        
        if ($this->isSensitiveEndpoint($uri)) {
            $response = $this->encryptResponseData(json_encode($response));
        }
        
        echo json_encode($response);
        exit;
    }
    
    // Modified to encrypt response data and return a string
    private function encryptResponseData(string $data): string
    {
        return EncryptionService::encrypt($data);
    }

    // Handle encryption on native request arrays (e.g., $_POST or $_GET)
    private function encryptRequestData(array &$request)
    {
        foreach ($request as $key => $value) {
            if ($this->isSensitiveField($key)) {
                $request[$key] = EncryptionService::encrypt($value);
            }
        }
    }

    // Load sensitive fields dynamically from configuration file
    private function isSensitiveField(string $field): bool
    {
        $configPath = __DIR__ . '/../../config/sensitive_fields.json';
        $config = file_exists($configPath) ? json_decode(file_get_contents($configPath), true) : [];
        $sensitiveFields = $config['sensitive_fields'] ?? ['password', 'email', 'phone'];
        return in_array($field, $sensitiveFields);
    }

    // Load sensitive endpoints dynamically from configuration file
    private function isSensitiveEndpoint(string $endpoint): bool
    {
        $configPath = __DIR__ . '/../../config/sensitive_endpoints.json';
        $config = file_exists($configPath) ? json_decode(file_get_contents($configPath), true) : [];
        $sensitiveEndpoints = $config['sensitive_endpoints'] ?? ['/user/profile-data'];
        return in_array($endpoint, $sensitiveEndpoints);
    }
    
    // Log events using injected LoggerInterface
    private function logEvent(string $message)
    {
        $this->logger->info("[EncryptionMiddleware] $message");
    }
}
