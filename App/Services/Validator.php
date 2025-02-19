<?php

namespace App\Services;

use App\Helpers\DatabaseHelper;
use function getLogger;

/**
 * Validator Service
 *
 * Validates input data against defined rules.
 */
class Validator
{
    private array $errors = [];
    
    // Removed constructor with LoggerInterface dependency.

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
            getLogger('validation')->warning("[Validation] Failed validation", ['errors' => $this->errors]);
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
    private function applyRule(string $field, $value, string $rule, array $data): void
    {
        if ($rule === 'required' && empty($value)) {
            $this->errors[$field][] = 'This field is required.';
        } elseif (str_starts_with($rule, 'max:')) {
            $maxLength = (int)explode(':', $rule)[1];
            if (!empty($value) && strlen($value) > $maxLength) {
                $this->errors[$field][] = "Maximum length is $maxLength characters.";
            }
        } elseif (str_starts_with($rule, 'min:')) {
            $minLength = (int)explode(':', $rule)[1];
            if (empty($value) || strlen($value) < $minLength) {
                $this->errors[$field][] = "Minimum length is $minLength characters.";
            }
        } elseif ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = 'Invalid email address.';
        } elseif (str_starts_with($rule, 'regex:')) {
            $pattern = substr($rule, 6);
            if (!preg_match($pattern, $value)) {
                $this->errors[$field][] = 'Invalid format.';
            }
        } elseif (str_starts_with($rule, 'same:')) {
            $otherField = substr($rule, 5);
            if (($value ?? '') !== ($data[$otherField] ?? '')) {
                $this->errors[$field][] = "This field must match {$otherField}.";
            }
        } elseif (str_starts_with($rule, 'unique:')) {
            [$table, $column] = explode(',', substr($rule, 7));
            $db = DatabaseHelper::getInstance();
            // Assuming DatabaseHelper supports a where()->exists() query.
            if ($db->table($table)->where($column, $value)->exists()) {
                $this->errors[$field][] = "The {$field} must be unique.";
            }
        }
    }
}
