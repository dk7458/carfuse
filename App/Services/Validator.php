<?php

namespace App\Services;

use Psr\Log\LoggerInterface;

/**
 * Validator Service
 *
 * Validates input data against defined rules.
 */
class Validator
{
    private array $errors = [];
    private LoggerInterface $logger;
    
    // Constructor for dependency injection
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
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
                $this->applyRule($field, $data[$field] ?? null, $rule);
            }
        }

        return empty($this->errors);
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
    private function applyRule(string $field, $value, string $rule): void
    {
        if ($rule === 'required' && empty($value)) {
            $this->errors[$field][] = 'This field is required.';
        } elseif (str_starts_with($rule, 'max:')) {
            $maxLength = (int)explode(':', $rule)[1];
            if (strlen($value) > $maxLength) {
                $this->errors[$field][] = "Maximum length is $maxLength characters.";
            }
        } elseif ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = 'Invalid email address.';
        } elseif (str_starts_with($rule, 'regex:')) {
            $pattern = substr($rule, 6);
            if (!preg_match($pattern, $value)) {
                $this->errors[$field][] = 'Invalid format.';
            }
        }
    }
}
