<?php

namespace App\Controllers;

use Exception;
use Psr\Log\LoggerInterface;

/**
 * Base Controller - Provides shared methods for all controllers.
 */
class Controller
{
    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        // Ensure session has started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * ✅ Standard JSON Response
     */
    protected function jsonResponse(array $data, int $statusCode = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    /**
     * ✅ Handle Exceptions & Log Errors
     */
    protected function handleException(Exception $e, string $context = 'General Error'): void
    {
        $this->logger->error("{$context}: " . $e->getMessage());
        $this->jsonResponse(['status' => 'error', 'message' => 'An error occurred.'], 500);
    }

    /**
     * ✅ Authorization Check (Example: Admin Access)
     */
    protected function checkAuth(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? null) !== 'admin') {
            $this->jsonResponse(['status' => 'error', 'message' => 'Unauthorized'], 403);
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
            $this->jsonResponse(['status' => 'error', 'message' => 'Validation failed', 'errors' => $errors], 422);
        }

        return $data;
    }
}
