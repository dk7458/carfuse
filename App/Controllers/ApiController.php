<?php

namespace App\Controllers;

use App\Helpers\ExceptionHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use App\Services\AuditService;

/**
 * Base API Controller
 * 
 * Provides common functionality for all API controllers including
 * standardized JSON responses, error handling, and shared utilities.
 */
class ApiController extends Controller
{
    private ResponseFactoryInterface $responseFactory;
    protected ExceptionHandler $exceptionHandler;
    protected AuditService $auditService;
    
    /**
     * Constructor
     */
    public function __construct(
        LoggerInterface $logger,
        ResponseFactoryInterface $responseFactory,
        ExceptionHandler $exceptionHandler,
        AuditService $auditService
    ) {
        parent::__construct($logger, $exceptionHandler);
        $this->responseFactory = $responseFactory;
        $this->exceptionHandler = $exceptionHandler;
        $this->auditService = $auditService;
    }

    /**
     * Create standardized PSR-7 JSON response
     */
    protected function jsonResponse(array $data, int $status = 200): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($status);
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }

    /**
     * Create a success response
     */
    protected function success($message, $data = [], int $status = 200): ResponseInterface
    {
        return $this->jsonResponse([
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ], $status);
    }

    /**
     * Create an error response
     */
    protected function error($message, $errors = [], int $status = 400): ResponseInterface
    {
        return $this->jsonResponse([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors
        ], $status);
    }

    /**
     * Safe handling of exceptions in API controllers
     */
    protected function safeExecute(callable $action): ResponseInterface
    {
        try {
            return $action();
        } catch (\Exception $e) {
            $this->exceptionHandler->handleException($e);
            // This line won't be reached if the exception handler exits
            return $this->error('An unexpected error occurred', [], 500);
        }
    }

    /**
     * Log audit event with proper context
     */
    protected function logAuditEvent(
        string $eventType,
        string $message,
        array $context = [],
        ?int $userId = null,
        ?int $resourceId = null,
        string $category = 'api'
    ): void {
        $this->auditService->logEvent(
            $eventType,
            $message,
            $context,
            $userId,
            $resourceId,
            $category
        );
    }
}
