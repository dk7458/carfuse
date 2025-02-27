<?php
namespace App\Helpers;

use PDOException;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

class ExceptionHandler
{
    private LoggerInterface $dbLogger;
    private LoggerInterface $authLogger;
    private LoggerInterface $systemLogger;

    public function __construct(
        LoggerInterface $dbLogger,
        LoggerInterface $authLogger,
        LoggerInterface $systemLogger
    ) {
        $this->dbLogger = $dbLogger;
        $this->authLogger = $authLogger;
        $this->systemLogger = $systemLogger;
    }

    /**
     * Handle exceptions centrally with consistent logging and JSON responses.
     */
    public function handleException(Exception $e): void
    {
        // Extract status code if available or use default
        $statusCode = method_exists($e, 'getCode') && is_numeric($e->getCode()) && $e->getCode() >= 400 && $e->getCode() < 600 
            ? $e->getCode() 
            : 500;
            
        // Database-related exceptions
        if ($e instanceof PDOException) {
            $errorCode = $e->getCode();
            $this->dbLogger->error("❌ Database Error: " . $e->getMessage(), [
                'code' => $errorCode,
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Handle specific database errors
            if ($errorCode == '23000') { // Integrity constraint violation
                ApiHelper::sendJsonResponse('error', 'Duplicate entry or constraint violation', ['error' => $this->sanitizeErrorMessage($e->getMessage())], 400);
            } elseif ($errorCode == '42S02') { // Table not found
                ApiHelper::sendJsonResponse('error', 'Database table error', ['error' => 'Requested table not found'], 500);
            } else {
                ApiHelper::sendJsonResponse('error', 'Database error', ['error' => $this->sanitizeErrorMessage($e->getMessage())], 500);
            }
        }
        // Validation exceptions
        elseif ($e instanceof InvalidArgumentException) {
            $this->systemLogger->warning("⚠️ Validation Error: " . $e->getMessage());
            ApiHelper::sendJsonResponse('error', 'Validation error', ['errors' => json_decode($e->getMessage(), true) ?? ['validation' => $e->getMessage()]], 400);
        }
        // Authentication exceptions - We'll uncomment and implement when needed
        elseif (strpos($e->getMessage(), 'Invalid credentials') !== false || strpos($e->getMessage(), 'Unauthorized') !== false) {
            $this->authLogger->error("🔒 Authentication Error: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            ApiHelper::sendJsonResponse('error', 'Authentication error', ['error' => $e->getMessage()], 401);
        }
        // Other exceptions
        else {
            $this->systemLogger->error("❌ System Error: " . $e->getMessage(), [
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // Use provided status code or default to 500
            ApiHelper::sendJsonResponse(
                'error', 
                'Unexpected error occurred', 
                ['error' => $this->sanitizeErrorMessage($e->getMessage())], 
                $statusCode
            );
        }
        exit();
    }
    
    /**
     * Sanitize error messages to remove sensitive information
     */
    private function sanitizeErrorMessage(string $message): string
    {
        // Remove potentially sensitive information (like SQL queries, passwords, etc)
        $message = preg_replace('/password\s*=\s*[^\s,)]+/i', 'password=*****', $message);
        
        // For production, you might want to return generic messages instead of actual DB errors
        if (getenv('APP_ENV') === 'production') {
            if (strpos($message, 'SQL') !== false) {
                return 'A database error occurred';
            }
        }
        
        return $message;
    }
}
?>