<?php

namespace App\Services;

use App\Helpers\DatabaseHelper;
use Psr\Log\LoggerInterface;
use App\Helpers\ExceptionHandler;

/**
 * Validator Service
 *
 * Validates input data against defined rules.
 */
class Validator
{
    public const DEBUG_MODE = true;
    private array $errors = [];
    private LoggerInterface $logger;
    private DatabaseHelper $db;
    private ExceptionHandler $exceptionHandler;

    // Updated constructor for Dependency Injection
    public function __construct(LoggerInterface $logger, DatabaseHelper $db, ExceptionHandler $exceptionHandler)
    {
        $this->logger = $logger;
        $this->db = $db;
        $this->exceptionHandler = $exceptionHandler;
    }

    /**
     * Validate data against rules.
     */
    public function validate(array $data, array $rules): bool
    {
        $this->errors = [];
        foreach ($rules as $field => $ruleSet) {
            $rulesArray = explode('|', $ruleSet);
            foreach ($rulesArray as $rule) {
                $this->applyRule($field, $data[$field] ?? null, $rule, $data);
            }
        }

        if (!empty($this->errors)) {
            if (self::DEBUG_MODE) {
                $this->logger->warning("[Validation] Validation failed", ['errors' => $this->errors]);
            }

            // **Throw an exception to prevent further execution**
            throw new \InvalidArgumentException(json_encode($this->errors));
        }

        return true;
    }

    /**
     * Get validation errors.
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Apply a validation rule to a field.
     */
    private function applyRule(string $field, $value, string $rule, array $data): void
    {
        try {
            if ($rule === 'required' && empty($value)) {
                $this->errors[$field][] = 'This field is required.';
            } elseif (strpos($rule, 'max:') === 0) {
                $maxLength = (int)explode(':', $rule)[1];
                if (!empty($value) && strlen($value) > $maxLength) {
                    $this->errors[$field][] = "Maximum length is $maxLength characters.";
                }
            } elseif (strpos($rule, 'min:') === 0) {
                $minLength = (int)explode(':', $rule)[1];
                if (empty($value) || strlen($value) < $minLength) {
                    $this->errors[$field][] = "Minimum length is $minLength characters.";
                }
            } elseif ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field][] = 'Invalid email address.';
            } elseif (strpos($rule, 'regex:') === 0) {
                $pattern = substr($rule, 6);
                if (!empty($value) && !preg_match($pattern, $value)) {
                    $this->errors[$field][] = 'Invalid format.';
                }
            } elseif (strpos($rule, 'same:') === 0) {
                $otherField = substr($rule, 5);
                if (isset($data[$otherField]) && ($value ?? '') !== $data[$otherField]) {
                    $this->errors[$field][] = "This field must match {$otherField}.";
                }
            } elseif (strpos($rule, 'unique:') === 0) {
                [$table, $column] = explode(',', substr($rule, 7));
                if ($this->db->table($table)->where($column, $value)->exists()) {
                    $this->errors[$field][] = "The {$field} must be unique.";
                }
            }
        } catch (\Exception $e) {
            $this->logger->error("[Validation] ❌ Validation error: " . $e->getMessage());
            $this->exceptionHandler->handleException($e);
        }
    }
}