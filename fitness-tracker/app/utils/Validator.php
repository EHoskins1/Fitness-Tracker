<?php
/**
 * Validator Utility Class
 * 
 * Input validation following the plan specifications.
 */

namespace App\Utils;

class Validator {
    private array $errors = [];

    /**
     * Get all validation errors
     */
    public function getErrors(): array {
        return $this->errors;
    }

    /**
     * Check if validation passed
     */
    public function isValid(): bool {
        return empty($this->errors);
    }

    /**
     * Add an error
     */
    private function addError(string $field, string $message): void {
        $this->errors[$field] = $message;
    }

    /**
     * Clear all errors
     */
    public function clearErrors(): void {
        $this->errors = [];
    }

    /**
     * Validate username
     * Rules: 3-50 chars, alphanumeric + underscore + hyphen
     */
    public function username(string $value, string $field = 'username'): bool {
        $value = trim($value);
        
        if (empty($value)) {
            $this->addError($field, 'Username is required');
            return false;
        }
        
        if (strlen($value) < USERNAME_MIN_LENGTH || strlen($value) > USERNAME_MAX_LENGTH) {
            $this->addError($field, 'Username must be between ' . USERNAME_MIN_LENGTH . ' and ' . USERNAME_MAX_LENGTH . ' characters');
            return false;
        }
        
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $value)) {
            $this->addError($field, 'Username can only contain letters, numbers, underscores, and hyphens');
            return false;
        }
        
        return true;
    }

    /**
     * Validate password
     * Rules: 8-128 chars, must contain 1 letter + 1 number
     */
    public function password(string $value, string $field = 'password'): bool {
        if (empty($value)) {
            $this->addError($field, 'Password is required');
            return false;
        }
        
        if (strlen($value) < PASSWORD_MIN_LENGTH || strlen($value) > PASSWORD_MAX_LENGTH) {
            $this->addError($field, 'Password must be between ' . PASSWORD_MIN_LENGTH . ' and ' . PASSWORD_MAX_LENGTH . ' characters');
            return false;
        }
        
        if (!preg_match('/[a-zA-Z]/', $value)) {
            $this->addError($field, 'Password must contain at least one letter');
            return false;
        }
        
        if (!preg_match('/[0-9]/', $value)) {
            $this->addError($field, 'Password must contain at least one number');
            return false;
        }
        
        return true;
    }

    /**
     * Validate email (optional field)
     */
    public function email(?string $value, string $field = 'email'): bool {
        if (empty($value)) {
            return true; // Email is optional
        }
        
        if (strlen($value) > 150) {
            $this->addError($field, 'Email must be less than 150 characters');
            return false;
        }
        
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, 'Invalid email format');
            return false;
        }
        
        return true;
    }

    /**
     * Validate session type
     * Rules: max 50 chars, letters/numbers/spaces/hyphens
     */
    public function sessionType(string $value, string $field = 'session_type'): bool {
        $value = trim($value);
        
        if (empty($value)) {
            $this->addError($field, 'Session type is required');
            return false;
        }
        
        if (strlen($value) > 50) {
            $this->addError($field, 'Session type must be less than 50 characters');
            return false;
        }
        
        if (!preg_match('/^[a-zA-Z0-9\s-]+$/', $value)) {
            $this->addError($field, 'Session type can only contain letters, numbers, spaces, and hyphens');
            return false;
        }
        
        return true;
    }

    /**
     * Validate notes/text field
     */
    public function notes(?string $value, string $field = 'notes'): bool {
        if (empty($value)) {
            return true; // Notes are optional
        }
        
        if (strlen($value) > NOTES_MAX_LENGTH) {
            $this->addError($field, 'Notes must be less than ' . NOTES_MAX_LENGTH . ' characters');
            return false;
        }
        
        return true;
    }

    /**
     * Validate weight in kg
     */
    public function weight(?float $value, string $field = 'weight_kg'): bool {
        if ($value === null) {
            return true; // Weight can be optional
        }
        
        if ($value < WEIGHT_MIN || $value > WEIGHT_MAX) {
            $this->addError($field, 'Weight must be between ' . WEIGHT_MIN . ' and ' . WEIGHT_MAX . ' kg');
            return false;
        }
        
        return true;
    }

    /**
     * Validate body fat percentage
     */
    public function bodyFat(?float $value, string $field = 'body_fat_percent'): bool {
        if ($value === null) {
            return true; // Body fat can be optional
        }
        
        if ($value < BODY_FAT_MIN || $value > BODY_FAT_MAX) {
            $this->addError($field, 'Body fat must be between ' . BODY_FAT_MIN . '% and ' . BODY_FAT_MAX . '%');
            return false;
        }
        
        return true;
    }

    /**
     * Validate duration in minutes
     */
    public function duration(?int $value, string $field = 'duration_minutes'): bool {
        if ($value === null) {
            return true; // Duration can be optional
        }
        
        if ($value < DURATION_MIN || $value > DURATION_MAX) {
            $this->addError($field, 'Duration must be between ' . DURATION_MIN . ' and ' . DURATION_MAX . ' minutes');
            return false;
        }
        
        return true;
    }

    /**
     * Validate intensity (1-10)
     */
    public function intensity(?int $value, string $field = 'intensity'): bool {
        if ($value === null) {
            return true; // Intensity can be optional
        }
        
        if ($value < INTENSITY_MIN || $value > INTENSITY_MAX) {
            $this->addError($field, 'Intensity must be between ' . INTENSITY_MIN . ' and ' . INTENSITY_MAX);
            return false;
        }
        
        return true;
    }

    /**
     * Validate date (YYYY-MM-DD, not in future for logs)
     */
    public function date(string $value, bool $allowFuture = false, string $field = 'date'): bool {
        if (empty($value)) {
            $this->addError($field, 'Date is required');
            return false;
        }
        
        // Check format
        $d = \DateTime::createFromFormat('Y-m-d', $value);
        if (!$d || $d->format('Y-m-d') !== $value) {
            $this->addError($field, 'Invalid date format. Use YYYY-MM-DD');
            return false;
        }
        
        // Check not in future
        if (!$allowFuture && $d > new \DateTime()) {
            $this->addError($field, 'Date cannot be in the future');
            return false;
        }
        
        return true;
    }

    /**
     * Validate time (HH:MM or HH:MM:SS)
     */
    public function time(?string $value, string $field = 'time'): bool {
        if (empty($value)) {
            return true; // Time can be optional
        }
        
        if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $value)) {
            $this->addError($field, 'Invalid time format. Use HH:MM or HH:MM:SS');
            return false;
        }
        
        return true;
    }

    /**
     * Sanitize string for output (XSS prevention)
     */
    public static function sanitizeOutput(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Sanitize array of values for output
     */
    public static function sanitizeArray(array $values): array {
        return array_map(function($value) {
            if (is_string($value)) {
                return self::sanitizeOutput($value);
            }
            return $value;
        }, $values);
    }
}
