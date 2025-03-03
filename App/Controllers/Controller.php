<?php

namespace App\Controllers;

use Exception;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Base Controller - Provides shared methods for all controllers.
 */
class Controller
{
    protected LoggerInterface $logger;
    protected ExceptionHandler $exceptionHandler;

    public function __construct(LoggerInterface $logger, ExceptionHandler $exceptionHandler)
    {
        $this->logger = $logger;
        $this->exceptionHandler = $exceptionHandler;
    }

    /**
     * ✅ Standard JSON Response
     */
    protected function jsonResponse(Response $response, $data, $status = 200)
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    /**
     * ✅ Error Response
     */
    protected function errorResponse(Response $response, $message, $status = 400)
    {
        return $this->jsonResponse($response, ['error' => $message], $status);
    }

    /**
     * ✅ Handle Exceptions & Log Errors
     * This method is for backward compatibility, new controllers should use ExceptionHandler
     */
    protected function handleException(Exception $e, string $context = 'General Error'): void
    {
        if ($this->exceptionHandler) {
            $this->exceptionHandler->handleException($e);
        } else {
            // Legacy fallback behavior
            $this->logger->error("{$context}: " . $e->getMessage());
            $this->jsonResponse(['status' => 'error', 'message' => 'An error occurred.'], 500);
        }
    }

    /**
     * ✅ Input Validation Helper
     */
    protected function validateRequest(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;

            if (strpos($rule, 'required') !== false && empty($value)) {
                $errors[$field] = "{$field} is required.";
            }

            if (strpos($rule, 'integer') !== false && !filter_var($value, FILTER_VALIDATE_INT)) {
                $errors[$field] = "{$field} must be an integer.";
            }

            if (strpos($rule, 'email') !== false && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field] = "{$field} must be a valid email.";
            }
        }

        if (!empty($errors)) {
            if ($this->exceptionHandler) {
                throw new \InvalidArgumentException(json_encode(['validation' => $errors]));
            } else {
                // Legacy fallback behavior
                $this->jsonResponse(['status' => 'error', 'message' => 'Validation failed', 'errors' => $errors], 422);
            }
        }

        return $data;
    }
}
