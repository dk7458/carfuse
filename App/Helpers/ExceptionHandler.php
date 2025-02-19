<?php
namespace App\Helpers;

use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use PDOException;
use Exception;
use Psr\Log\LoggerInterface;

class ExceptionHandler
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Handle exceptions centrally.
     */
    public function handleException(Exception $e): void
    {
        // Handle database-related errors
        if ($e instanceof UniqueConstraintViolationException || $e instanceof PDOException) {
            $this->logger->error("❌ Database Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => 'A database error occurred.',
                'error'   => $e->getMessage()
            ]);
            exit();
        }

        // Handle general query exceptions
        if ($e instanceof QueryException) {
            $this->logger->error("❌ Query Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            http_response_code(400);
            echo json_encode([
                'status'  => 'error',
                'message' => 'A query error occurred.',
                'error'   => $e->getMessage()
            ]);
            exit();
        }

        // Handle all other errors
        $this->logger->error("❌ Application Error: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        http_response_code(500);
        echo json_encode([
            'status'  => 'error',
            'message' => 'An unexpected error occurred.',
            'error'   => $e->getMessage()
        ]);
        exit();
    }
}
?>