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
        $this->logger->debug("Validator initialized with database connection");
    }

    /**
     * Validate data against rules.
     */
    public function validate(array $data, array $rules): bool
    {
        $this->logger->debug("Starting validation with rules", ['rules' => $rules]);
        $this->errors = [];
        
        foreach ($rules as $field => $ruleSet) {
            $rulesArray = explode('|', $ruleSet);
            foreach ($rulesArray as $rule) {
                $this->applyRule($field, $data[$field] ?? null, $rule, $data);
            }
        }

        if (!empty($this->errors)) {
            if (self::DEBUG_MODE) {
                $this->logger->warning("Validation failed", ['errors' => $this->errors]);
            }

            // Throw an exception to prevent further execution
            throw new \InvalidArgumentException(json_encode(['errors' => $this->errors]));
        }

        $this->logger->debug("Validation successful");
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
                $this->errors[$field][] = "The {$field} field is required.";
            } elseif (strpos($rule, 'max:') === 0) {
                $maxLength = (int)explode(':', $rule)[1];
                if (!empty($value) && strlen($value) > $maxLength) {
                    $this->errors[$field][] = "The {$field} must not exceed {$maxLength} characters.";
                }
            } elseif (strpos($rule, 'min:') === 0) {
                $minLength = (int)explode(':', $rule)[1];
                if (!empty($value) && strlen($value) < $minLength) {
                    $this->errors[$field][] = "The {$field} must be at least {$minLength} characters.";
                }
            } elseif ($rule === 'email' && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field][] = "The {$field} must be a valid email address.";
            } elseif (strpos($rule, 'regex:') === 0) {
                $pattern = substr($rule, 6);
                if (!empty($value) && !preg_match($pattern, $value)) {
                    $this->errors[$field][] = "The {$field} format is invalid.";
                }
            } elseif (strpos($rule, 'same:') === 0) {
                $otherField = substr($rule, 5);
                if (!empty($value) && isset($data[$otherField]) && $value !== $data[$otherField]) {
                    $this->errors[$field][] = "The {$field} and {$otherField} must match.";
                }
            } elseif (strpos($rule, 'unique:') === 0) {
                [$table, $column] = explode(',', substr($rule, 7));
                
                $this->logger->debug("Checking uniqueness", [
                    'field' => $field,
                    'table' => $table,
                    'column' => $column,
                    'value' => $value
                ]);
                
                if (!empty($value)) {
                    $pdo = $this->db->getPdo(); // Get PDO instance from DatabaseHelper
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = ?");
                    $stmt->execute([$value]);
                    $count = (int)$stmt->fetchColumn();
                    
                    if ($count > 0) {
                        $this->errors[$field][] = "The {$field} has already been taken.";
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error("Validation error: " . $e->getMessage(), [
                'field' => $field,
                'rule' => $rule
            ]);
            
            // Add a generic error and continue validation
            $this->errors[$field][] = "An error occurred while validating {$field}.";
        }
    }
}