<?php
namespace App\Helpers;

use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use PDOException;
use Exception;
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
     * Handle exceptions centrally.
     */
    public function handleException(Exception $e): void
    {
        // Database-related exceptions
        if ($e instanceof UniqueConstraintViolationException || $e instanceof PDOException) {
            $this->dbLogger->error("❌ Database Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            ApiHelper::sendJsonResponse('error', 'Database error', ['error' => $e->getMessage()], 400);
        }
        // Query exceptions
        elseif ($e instanceof QueryException) {
            $this->dbLogger->error("❌ Query Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            ApiHelper::sendJsonResponse('error', 'Query error', ['error' => $e->getMessage()], 400);
        }
        // Authentication exceptions (if applicable)
        // elseif ($e instanceof AuthenticationException) {
        //     $this->authLogger->error("❌ Authentication Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        //     ApiHelper::sendJsonResponse('error', 'Authentication error', ['error' => $e->getMessage()], 401);
        // }
        // Other exceptions
        else {
            $this->systemLogger->error("❌ System Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            ApiHelper::sendJsonResponse('error', 'Unexpected error occurred', ['error' => $e->getMessage()], 500);
        }
        exit();
    }
}
?>