<?php
/**
 * Validator Service Class
 * Provides input validation for forms and API requests
 */

class Validator
{
    private array $errors = [];
    private array $data = [];
    private array $validated = [];

    /**
     * Create a new validator instance
     *
     * @param array $data Data to validate
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Static factory method
     *
     * @param array $data Data to validate
     * @return self
     */
    public static function make(array $data): self
    {
        return new self($data);
    }

    /**
     * Validate a field with multiple rules
     *
     * @param string $field Field name
     * @param array $rules Array of rules (e.g., ['required', 'email', 'max:255'])
     * @param string|null $label Human-readable label for error messages
     * @return self
     */
    public function field(string $field, array $rules, ?string $label = null): self
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));
        $value = $this->data[$field] ?? null;

        foreach ($rules as $rule) {
            $params = [];
            if (strpos($rule, ':') !== false) {
                [$rule, $paramStr] = explode(':', $rule, 2);
                $params = explode(',', $paramStr);
            }

            $method = 'validate' . ucfirst($rule);
            if (method_exists($this, $method)) {
                $result = $this->$method($value, $params, $field, $label);
                if ($result !== true) {
                    $this->errors[$field] = $result;
                    break; // Stop on first error for this field
                }
            }
        }

        // Store validated value if no errors
        if (!isset($this->errors[$field])) {
            $this->validated[$field] = $value;
        }

        return $this;
    }

    /**
     * Check if validation passed
     *
     * @return bool
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     *
     * @return bool
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get all validation errors
     *
     * @return array
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error message
     *
     * @return string|null
     */
    public function firstError(): ?string
    {
        return reset($this->errors) ?: null;
    }

    /**
     * Get error for a specific field
     *
     * @param string $field
     * @return string|null
     */
    public function error(string $field): ?string
    {
        return $this->errors[$field] ?? null;
    }

    /**
     * Get validated data
     *
     * @return array
     */
    public function validated(): array
    {
        return $this->validated;
    }

    /**
     * Add a custom error
     *
     * @param string $field
     * @param string $message
     * @return self
     */
    public function addError(string $field, string $message): self
    {
        $this->errors[$field] = $message;
        return $this;
    }

    // ==================== Validation Rules ====================

    /**
     * Required field validation
     */
    private function validateRequired($value, array $params, string $field, string $label)
    {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            return "{$label} is required.";
        }
        return true;
    }

    /**
     * Email validation
     */
    private function validateEmail($value, array $params, string $field, string $label)
    {
        if ($value === null || $value === '') {
            return true; // Not required by this rule
        }
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "{$label} must be a valid email address.";
        }
        return true;
    }

    /**
     * Minimum length validation
     */
    private function validateMin($value, array $params, string $field, string $label)
    {
        if ($value === null || $value === '') {
            return true;
        }
        $min = (int)($params[0] ?? 0);
        if (is_string($value) && mb_strlen($value) < $min) {
            return "{$label} must be at least {$min} characters.";
        }
        if (is_numeric($value) && $value < $min) {
            return "{$label} must be at least {$min}.";
        }
        return true;
    }

    /**
     * Maximum length validation
     */
    private function validateMax($value, array $params, string $field, string $label)
    {
        if ($value === null || $value === '') {
            return true;
        }
        $max = (int)($params[0] ?? 255);
        if (is_string($value) && mb_strlen($value) > $max) {
            return "{$label} must not exceed {$max} characters.";
        }
        if (is_numeric($value) && $value > $max) {
            return "{$label} must not exceed {$max}.";
        }
        return true;
    }

    /**
     * Numeric validation
     */
    private function validateNumeric($value, array $params, string $field, string $label)
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (!is_numeric($value)) {
            return "{$label} must be a number.";
        }
        return true;
    }

    /**
     * Integer validation
     */
    private function validateInteger($value, array $params, string $field, string $label)
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            return "{$label} must be an integer.";
        }
        return true;
    }

    /**
     * Positive number validation
     */
    private function validatePositive($value, array $params, string $field, string $label)
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (!is_numeric($value) || $value <= 0) {
            return "{$label} must be a positive number.";
        }
        return true;
    }

    /**
     * Alpha-numeric validation
     */
    private function validateAlphanum($value, array $params, string $field, string $label)
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (!ctype_alnum($value)) {
            return "{$label} must only contain letters and numbers.";
        }
        return true;
    }

    /**
     * Alpha-numeric with underscores (for usernames)
     */
    private function validateUsername($value, array $params, string $field, string $label)
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $value)) {
            return "{$label} must only contain letters, numbers, and underscores.";
        }
        return true;
    }

    /**
     * Password strength validation
     */
    private function validatePassword($value, array $params, string $field, string $label)
    {
        if ($value === null || $value === '') {
            return true;
        }

        $errors = [];
        if (strlen($value) < 8) {
            $errors[] = 'at least 8 characters';
        }
        if (!preg_match('/[A-Z]/', $value)) {
            $errors[] = 'one uppercase letter';
        }
        if (!preg_match('/[a-z]/', $value)) {
            $errors[] = 'one lowercase letter';
        }
        if (!preg_match('/[0-9]/', $value)) {
            $errors[] = 'one number';
        }
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $value)) {
            $errors[] = 'one special character';
        }

        if (!empty($errors)) {
            return "{$label} must contain " . implode(', ', $errors) . '.';
        }
        return true;
    }

    /**
     * Confirmation field validation (e.g., password_confirmation)
     */
    private function validateConfirmed($value, array $params, string $field, string $label)
    {
        $confirmField = $field . '_confirmation';
        $confirmValue = $this->data[$confirmField] ?? null;

        if ($value !== $confirmValue) {
            return "{$label} confirmation does not match.";
        }
        return true;
    }

    /**
     * In array validation
     */
    private function validateIn($value, array $params, string $field, string $label)
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (!in_array($value, $params, true)) {
            return "{$label} must be one of: " . implode(', ', $params) . '.';
        }
        return true;
    }

    /**
     * Phone number validation (Ghana format)
     */
    private function validatePhone($value, array $params, string $field, string $label)
    {
        if ($value === null || $value === '') {
            return true;
        }
        // Allow formats: 0XX XXX XXXX, +233 XX XXX XXXX, etc.
        $cleaned = preg_replace('/[\s\-]/', '', $value);
        if (!preg_match('/^(\+233|0)[0-9]{9}$/', $cleaned)) {
            return "{$label} must be a valid phone number.";
        }
        return true;
    }

    /**
     * URL validation
     */
    private function validateUrl($value, array $params, string $field, string $label)
    {
        if ($value === null || $value === '') {
            return true;
        }
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return "{$label} must be a valid URL.";
        }
        return true;
    }

    /**
     * Date validation
     */
    private function validateDate($value, array $params, string $field, string $label)
    {
        if ($value === null || $value === '') {
            return true;
        }
        $format = $params[0] ?? 'Y-m-d';
        $date = \DateTime::createFromFormat($format, $value);
        if (!$date || $date->format($format) !== $value) {
            return "{$label} must be a valid date.";
        }
        return true;
    }

    /**
     * Year validation (reasonable range)
     */
    private function validateYear($value, array $params, string $field, string $label)
    {
        if ($value === null || $value === '') {
            return true;
        }
        $value = (int)$value;
        $currentYear = (int)date('Y');
        // Allow books from 1450 (printing press invention) to next year
        if ($value < 1450 || $value > $currentYear + 1) {
            return "{$label} must be a valid year between 1450 and " . ($currentYear + 1) . '.';
        }
        return true;
    }

    /**
     * Same as another field validation
     */
    private function validateSame($value, array $params, string $field, string $label)
    {
        $otherField = $params[0] ?? '';
        $otherValue = $this->data[$otherField] ?? null;
        $otherLabel = ucfirst(str_replace('_', ' ', $otherField));

        if ($value !== $otherValue) {
            return "{$label} must match {$otherLabel}.";
        }
        return true;
    }

    /**
     * Different from another field validation
     */
    private function validateDifferent($value, array $params, string $field, string $label)
    {
        $otherField = $params[0] ?? '';
        $otherValue = $this->data[$otherField] ?? null;
        $otherLabel = ucfirst(str_replace('_', ' ', $otherField));

        if ($value === $otherValue) {
            return "{$label} must be different from {$otherLabel}.";
        }
        return true;
    }

    /**
     * Regex pattern validation
     */
    private function validateRegex($value, array $params, string $field, string $label)
    {
        if ($value === null || $value === '') {
            return true;
        }
        $pattern = $params[0] ?? '';
        if (!preg_match($pattern, $value)) {
            return "{$label} format is invalid.";
        }
        return true;
    }

    /**
     * ISBN validation (basic)
     */
    private function validateIsbn($value, array $params, string $field, string $label)
    {
        if ($value === null || $value === '') {
            return true;
        }
        // Remove hyphens and spaces
        $cleaned = preg_replace('/[\s\-]/', '', $value);
        // ISBN-10 or ISBN-13
        if (!preg_match('/^(\d{10}|\d{13})$/', $cleaned)) {
            return "{$label} must be a valid ISBN (10 or 13 digits).";
        }
        return true;
    }

    // ==================== File Validation ====================

    /**
     * Validate uploaded file
     *
     * @param string $field Field name in $_FILES
     * @param array $rules Rules for file validation
     * @param string|null $label Human-readable label
     * @return self
     */
    public function file(string $field, array $rules, ?string $label = null): self
    {
        $label = $label ?? ucfirst(str_replace('_', ' ', $field));

        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
            if (in_array('required', $rules)) {
                $this->errors[$field] = "{$label} is required.";
            }
            return $this;
        }

        $file = $_FILES[$field];

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[$field] = $this->getUploadErrorMessage($file['error'], $label);
            return $this;
        }

        foreach ($rules as $rule) {
            $params = [];
            if (strpos($rule, ':') !== false) {
                [$rule, $paramStr] = explode(':', $rule, 2);
                $params = explode(',', $paramStr);
            }

            $method = 'validateFile' . ucfirst($rule);
            if (method_exists($this, $method)) {
                $result = $this->$method($file, $params, $field, $label);
                if ($result !== true) {
                    $this->errors[$field] = $result;
                    break;
                }
            }
        }

        return $this;
    }

    /**
     * Validate file MIME type
     */
    private function validateFileMimes(array $file, array $params, string $field, string $label)
    {
        $allowedMimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
        ];

        $allowed = [];
        foreach ($params as $ext) {
            if (isset($allowedMimes[$ext])) {
                $allowed[] = $allowedMimes[$ext];
            }
        }

        // Use finfo for reliable MIME detection
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $allowed, true)) {
            return "{$label} must be a file of type: " . implode(', ', $params) . '.';
        }

        return true;
    }

    /**
     * Validate file size (in KB)
     */
    private function validateFileMaxsize(array $file, array $params, string $field, string $label)
    {
        $maxKB = (int)($params[0] ?? 2048); // Default 2MB
        $sizeKB = $file['size'] / 1024;

        if ($sizeKB > $maxKB) {
            $maxMB = round($maxKB / 1024, 1);
            return "{$label} must not exceed {$maxMB}MB.";
        }

        return true;
    }

    /**
     * Validate image dimensions
     */
    private function validateFileImage(array $file, array $params, string $field, string $label)
    {
        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return "{$label} must be a valid image.";
        }

        // Optional dimension constraints
        if (!empty($params)) {
            [$minWidth, $minHeight] = array_pad($params, 2, 0);
            if ($imageInfo[0] < $minWidth || $imageInfo[1] < $minHeight) {
                return "{$label} must be at least {$minWidth}x{$minHeight} pixels.";
            }
        }

        return true;
    }

    /**
     * Get human-readable upload error message
     */
    private function getUploadErrorMessage(int $error, string $label): string
    {
        $messages = [
            UPLOAD_ERR_INI_SIZE => "{$label} exceeds the maximum upload size.",
            UPLOAD_ERR_FORM_SIZE => "{$label} exceeds the maximum upload size.",
            UPLOAD_ERR_PARTIAL => "{$label} was only partially uploaded.",
            UPLOAD_ERR_NO_FILE => "{$label} is required.",
            UPLOAD_ERR_NO_TMP_DIR => "Server error: Missing temporary folder.",
            UPLOAD_ERR_CANT_WRITE => "Server error: Failed to write file to disk.",
            UPLOAD_ERR_EXTENSION => "Server error: File upload stopped by extension.",
        ];

        return $messages[$error] ?? "{$label} failed to upload.";
    }
}
