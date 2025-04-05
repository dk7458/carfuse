<?php

namespace App\Helpers;

use Psr\Log\LoggerInterface;

/**
 * API Helper Functions
 */
class ApiHelper
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;
    
    /**
     * @var string
     */
    private string $logFile;
    
    /**
     * Constructor with dependency injection
     * 
     * @param LoggerInterface $logger Logger instance
     * @param string|null $logFile Path to API log file (optional)
     */
    public function __construct(LoggerInterface $logger, ?string $logFile = null)
    {
        $this->logger = $logger;
        $this->logFile = $logFile ?? __DIR__ . '/../../logs/api.log';
    }
    
    /**
     * ✅ Log API Events for Debugging
     */
    public function logApiEvent($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $this->logger->info("[API] {$message}");
        file_put_contents($this->logFile, "{$timestamp} - {$message}\n", FILE_APPEND);
    }

    /**
     * ✅ Standardized JSON Response Function
     */
    public function sendJsonResponse($status, $message, $data = [], $httpCode = 200)
    {
        http_response_code($httpCode);
        header('Content-Type: application/json');
        echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
        exit();
    }

    /**
     * ✅ Extract JWT from Authorization Header or Cookie
     */
    public function getJWT()
    {
        $headers = getallheaders();
        if (isset($headers['Authorization']) && preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
        return $_COOKIE['jwt'] ?? null;
    }
}
