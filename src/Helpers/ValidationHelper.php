<?php
declare(strict_types=1);

namespace App\Helpers;

class ValidationHelper
{
    /**
     * Validate email address
     */
    public static function is_valid_email(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate URL
     */
    public static function is_valid_url(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate Dutch postal code
     */
    public static function is_valid_dutch_postal_code(string $postal_code): bool
    {
        $pattern = '/^[1-9][0-9]{3}\s?[A-Z]{2}$/i';
        return preg_match($pattern, trim($postal_code)) === 1;
    }

    /**
     * Validate Dutch phone number
     */
    public static function is_valid_dutch_phone(string $phone): bool
    {
        $cleaned = StringHelper::clean_phone($phone);
        
        // Remove country code
        if (StringHelper::starts_with($cleaned, '+31')) {
            $cleaned = '0' . substr($cleaned, 3);
        } elseif (StringHelper::starts_with($cleaned, '0031')) {
            $cleaned = '0' . substr($cleaned, 4);
        }
        
        // Check if it's a valid Dutch number format
        return preg_match('/^0[1-9][0-9]{8}$/', $cleaned) === 1;
    }

    /**
     * Validate price (positive number with max 2 decimals)
     */
    public static function is_valid_price(mixed $price): bool
    {
        if (!is_numeric($price)) {
            return false;
        }
        
        $price = (float) $price;
        
        if ($price < 0) {
            return false;
        }
        
        // Check max 2 decimal places
        return round($price, 2) == $price;
    }

    /**
     * Validate password strength
     */
    public static function is_strong_password(string $password): array
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Wachtwoord moet minimaal 8 karakters lang zijn';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Wachtwoord moet minimaal één hoofdletter bevatten';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Wachtwoord moet minimaal één kleine letter bevatten';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Wachtwoord moet minimaal één cijfer bevatten';
        }
        
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = 'Wachtwoord moet minimaal één speciaal teken bevatten';
        }
        
        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate required fields
     * 
     * @param array<string, mixed> $data
     * @param array<int, string> $required_fields
     * @return array<int, string>
     */
    public static function validate_required_fields(array $data, array $required_fields): array
    {
        $errors = [];
        
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
                $errors[] = "Het veld '{$field}' is verplicht";
            }
        }
        
        return $errors;
    }

    /**
     * Validate file upload
     * 
     * @param array<string, mixed> $file
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public static function validate_file_upload(array $file, array $options = []): array
    {
        $max_size = $options['max_size'] ?? 5 * 1024 * 1024; // 5MB default
        $allowed_types = $options['allowed_types'] ?? ['image/jpeg', 'image/png', 'image/gif'];
        
        $errors = [];
        
        // Check upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            switch ($file['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errors[] = 'Bestand is te groot';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errors[] = 'Bestand is slechts gedeeltelijk geüpload';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errors[] = 'Geen bestand geselecteerd';
                    break;
                default:
                    $errors[] = 'Upload fout opgetreden';
            }
        }
        
        // Check file size
        if ($file['size'] > $max_size) {
            $errors[] = 'Bestand is te groot (max ' . StringHelper::format_bytes($max_size) . ')';
        }
        
        // Check file type
        if (!in_array($file['type'], $allowed_types)) {
            $errors[] = 'Bestandstype niet toegestaan';
        }
        
        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate date format
     */
    public static function is_valid_date(string $date, string $format = 'Y-m-d'): bool
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Validate age (must be 18 or older)
     */
    public static function is_valid_age(string $birth_date): bool
    {
        if (!self::is_valid_date($birth_date)) {
            return false;
        }
        
        $birth = new \DateTime($birth_date);
        $today = new \DateTime();
        $age = $today->diff($birth)->y;
        
        return $age >= 18;
    }

    /**
     * Validate credit card number (basic Luhn algorithm)
     */
    public static function is_valid_credit_card(string $number): bool
    {
        $number = preg_replace('/\D/', '', $number);
        
        if (strlen($number) < 13 || strlen($number) > 19) {
            return false;
        }
        
        // Luhn algorithm
        $sum = 0;
        $alternate = false;
        
        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $digit = (int) $number[$i];
            
            if ($alternate) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit = ($digit % 10) + 1;
                }
            }
            
            $sum += $digit;
            $alternate = !$alternate;
        }
        
        return $sum % 10 === 0;
    }

    /**
     * Validate IBAN
     */
    public static function is_valid_iban(string $iban): bool
    {
        $iban = strtoupper(str_replace(' ', '', $iban));
        
        if (strlen($iban) < 15 || strlen($iban) > 34) {
            return false;
        }
        
        if (!preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]+$/', $iban)) {
            return false;
        }
        
        // Move first 4 characters to end
        $rearranged = substr($iban, 4) . substr($iban, 0, 4);
        
        // Convert letters to numbers
        $numeric = '';
        for ($i = 0; $i < strlen($rearranged); $i++) {
            $char = $rearranged[$i];
            if (ctype_alpha($char)) {
                $numeric .= (ord($char) - ord('A') + 10);
            } else {
                $numeric .= $char;
            }
        }
        
        // Check modulo 97
        return bcmod($numeric, '97') === '1';
    }

    /**
     * Validate Dutch BSN (Burgerservicenummer)
     */
    public static function is_valid_dutch_bsn(string $bsn): bool
    {
        $bsn = preg_replace('/\D/', '', $bsn);
        
        if (strlen($bsn) !== 9) {
            return false;
        }
        
        // 11-proof test
        $sum = 0;
        for ($i = 0; $i < 8; $i++) {
            $sum += (int) $bsn[$i] * (9 - $i);
        }
        
        $remainder = $sum % 11;
        $checkDigit = (int) $bsn[8];
        
        if ($remainder < 2) {
            return $checkDigit === $remainder;
        } else {
            return $checkDigit === (11 - $remainder);
        }
    }

    /**
     * Validate VAT number (basic EU format)
     */
    public static function is_valid_vat_number(string $vat): bool
    {
        $vat = strtoupper(str_replace([' ', '.', '-'], '', $vat));
        
        // Basic EU VAT format: 2 letter country code + digits
        return preg_match('/^[A-Z]{2}[0-9A-Z]{2,12}$/', $vat) === 1;
    }

    /**
     * Validate IPv4 address
     */
    public static function is_valid_ipv4(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * Validate hex color code
     */
    public static function is_valid_hex_color(string $color): bool
    {
        return preg_match('/^#[a-fA-F0-9]{6}$/', $color) === 1;
    }

    /**
     * Validate username format
     */
    public static function is_valid_username(string $username): array
    {
        $errors = [];
        
        if (strlen($username) < 3) {
            $errors[] = 'Gebruikersnaam moet minimaal 3 karakters lang zijn';
        }
        
        if (strlen($username) > 20) {
            $errors[] = 'Gebruikersnaam mag maximaal 20 karakters lang zijn';
        }
        
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $username)) {
            $errors[] = 'Gebruikersnaam mag alleen letters, cijfers, underscore en streepjes bevatten';
        }
        
        if (preg_match('/^[0-9]/', $username)) {
            $errors[] = 'Gebruikersnaam mag niet beginnen met een cijfer';
        }
        
        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate stock quantity
     */
    public static function is_valid_stock_quantity(mixed $quantity): bool
    {
        if (!is_numeric($quantity)) {
            return false;
        }
        
        $quantity = (int) $quantity;
        return $quantity >= 0;
    }

    /**
     * Validate discount percentage
     */
    public static function is_valid_discount_percentage(mixed $percentage): bool
    {
        if (!is_numeric($percentage)) {
            return false;
        }
        
        $percentage = (float) $percentage;
        return $percentage >= 0 && $percentage <= 100;
    }

    /**
     * Sanitize and validate input data
     * 
     * @param array<string, mixed> $data
     * @param array<string, array<string, mixed>> $rules
     * @return array<string, mixed>
     */
    public static function validate_input(array $data, array $rules): array
    {
        $errors = [];
        $sanitized = [];
        
        foreach ($rules as $field => $field_rules) {
            $value = $data[$field] ?? null;
            
            // Required validation
            if (isset($field_rules['required']) && $field_rules['required']) {
                if ($value === null || trim((string) $value) === '') {
                    $errors[$field][] = "Het veld '{$field}' is verplicht";
                    continue;
                }
            }
            
            // Skip other validations if field is empty and not required
            if ($value === null || trim((string) $value) === '') {
                $sanitized[$field] = $value;
                continue;
            }
            
            // Type validation
            if (isset($field_rules['type'])) {
                switch ($field_rules['type']) {
                    case 'email':
                        if (!self::is_valid_email($value)) {
                            $errors[$field][] = "'{$field}' moet een geldig e-mailadres zijn";
                        }
                        break;
                    case 'url':
                        if (!self::is_valid_url($value)) {
                            $errors[$field][] = "'{$field}' moet een geldige URL zijn";
                        }
                        break;
                    case 'integer':
                        if (!filter_var($value, FILTER_VALIDATE_INT)) {
                            $errors[$field][] = "'{$field}' moet een geheel getal zijn";
                        }
                        break;
                    case 'float':
                        if (!filter_var($value, FILTER_VALIDATE_FLOAT)) {
                            $errors[$field][] = "'{$field}' moet een getal zijn";
                        }
                        break;
                    case 'price':
                        if (!self::is_valid_price($value)) {
                            $errors[$field][] = "'{$field}' moet een geldige prijs zijn";
                        }
                        break;
                    case 'dutch_phone':
                        if (!self::is_valid_dutch_phone($value)) {
                            $errors[$field][] = "'{$field}' moet een geldig Nederlands telefoonnummer zijn";
                        }
                        break;
                    case 'dutch_postal_code':
                        if (!self::is_valid_dutch_postal_code($value)) {
                            $errors[$field][] = "'{$field}' moet een geldige Nederlandse postcode zijn";
                        }
                        break;
                }
            }
            
            // Length validation
            if (isset($field_rules['min_length'])) {
                if (strlen($value) < $field_rules['min_length']) {
                    $errors[$field][] = "'{$field}' moet minimaal {$field_rules['min_length']} karakters lang zijn";
                }
            }
            
            if (isset($field_rules['max_length'])) {
                if (strlen($value) > $field_rules['max_length']) {
                    $errors[$field][] = "'{$field}' mag maximaal {$field_rules['max_length']} karakters lang zijn";
                }
            }
            
            // Value range validation
            if (isset($field_rules['min_value'])) {
                if (is_numeric($value) && (float) $value < $field_rules['min_value']) {
                    $errors[$field][] = "'{$field}' moet minimaal {$field_rules['min_value']} zijn";
                }
            }
            
            if (isset($field_rules['max_value'])) {
                if (is_numeric($value) && (float) $value > $field_rules['max_value']) {
                    $errors[$field][] = "'{$field}' mag maximaal {$field_rules['max_value']} zijn";
                }
            }
            
            // Pattern validation
            if (isset($field_rules['pattern'])) {
                if (!preg_match($field_rules['pattern'], $value)) {
                    $errors[$field][] = "'{$field}' heeft geen geldig formaat";
                }
            }
            
            // Custom validation
            if (isset($field_rules['custom']) && is_callable($field_rules['custom'])) {
                $custom_result = $field_rules['custom']($value);
                if ($custom_result !== true) {
                    $errors[$field][] = $custom_result;
                }
            }
            
            // Sanitize value
            if (isset($field_rules['sanitize']) && $field_rules['sanitize']) {
                $sanitized[$field] = StringHelper::sanitize($value);
            } else {
                $sanitized[$field] = $value;
            }
        }
        
        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'data' => $sanitized,
        ];
    }

    /**
     * Validate array of data against schema
     * 
     * @param array<string, mixed> $data
     * @param array<string, mixed> $schema
     * @return array<string, mixed>
     */
    public static function validate_schema(array $data, array $schema): array
    {
        $errors = [];
        $valid_data = [];
        
        foreach ($schema as $field => $rules) {
            $value = $data[$field] ?? null;
            $field_errors = [];
            
            // Check if required
            if (isset($rules['required']) && $rules['required'] && ($value === null || $value === '')) {
                $field_errors[] = "Field '{$field}' is required";
                continue;
            }
            
            // Skip validation if value is null/empty and not required
            if ($value === null || $value === '') {
                $valid_data[$field] = $value;
                continue;
            }
            
            // Validate type
            if (isset($rules['type'])) {
                switch ($rules['type']) {
                    case 'string':
                        if (!is_string($value)) {
                            $field_errors[] = "Field '{$field}' must be a string";
                        }
                        break;
                    case 'integer':
                        if (!is_int($value) && !ctype_digit((string) $value)) {
                            $field_errors[] = "Field '{$field}' must be an integer";
                        } else {
                            $value = (int) $value;
                        }
                        break;
                    case 'float':
                        if (!is_numeric($value)) {
                            $field_errors[] = "Field '{$field}' must be a number";
                        } else {
                            $value = (float) $value;
                        }
                        break;
                    case 'boolean':
                        if (!is_bool($value)) {
                            $value = StringHelper::to_boolean((string) $value);
                        }
                        break;
                    case 'array':
                        if (!is_array($value)) {
                            $field_errors[] = "Field '{$field}' must be an array";
                        }
                        break;
                }
            }
            
            // Additional validations can be added here
            
            if (empty($field_errors)) {
                $valid_data[$field] = $value;
            } else {
                $errors[$field] = $field_errors;
            }
        }
        
        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'data' => $valid_data,
        ];
    }

    /**
     * Check if string contains potentially dangerous content
     */
    public static function contains_malicious_content(string $input): bool
    {
        $dangerous_patterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload\s*=/i',
            '/onerror\s*=/i',
            '/onclick\s*=/i',
            '/eval\s*\(/i',
            '/expression\s*\(/i',
        ];
        
        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Validate SQL injection patterns
     */
    public static function contains_sql_injection(string $input): bool
    {
        $sql_patterns = [
            '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|EXEC|UNION)\b)/i',
            '/(\b(OR|AND)\s+\w+\s*=\s*\w+)/i',
            '/(\'|\")(\s*)(OR|AND)(\s*)(\'|\")/i',
            '/\b(EXEC|EXECUTE)\s*\(/i',
            '/\b(SP_|XP_)\w+/i',
        ];
        
        foreach ($sql_patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Validate file extension
     * 
     * @param array<int, string> $allowed_extensions
     */
    public static function is_valid_file_extension(string $filename, array $allowed_extensions): bool
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        return in_array($extension, array_map('strtolower', $allowed_extensions));
    }

    /**
     * Check password against common passwords list
     */
    public static function is_common_password(string $password): bool
    {
        $common_passwords = [
            'password', '123456', 'password123', 'admin', 'qwerty',
            'letmein', 'welcome', 'monkey', '1234567890', 'abc123',
            'password1', '123456789', 'welcome123', 'admin123',
            'root', 'toor', 'pass', 'test', 'guest', 'info'
        ];
        
        return in_array(strtolower($password), $common_passwords);
    }

    /**
     * Validate product SKU format
     */
    public static function is_valid_sku(string $sku): bool
    {
        // SKU should be alphanumeric with optional hyphens and underscores
        // Length between 3-20 characters
        return preg_match('/^[A-Z0-9_-]{3,20}$/i', $sku) === 1;
    }

    /**
     * Validate order number format
     */
    public static function is_valid_order_number(string $order_number): bool
    {
        // Order number should be numeric or alphanumeric
        // Typically 6-12 characters
        return preg_match('/^[A-Z0-9]{6,12}$/i', $order_number) === 1;
    }

    /**
     * Validate coupon code format
     */
    public static function is_valid_coupon_code(string $code): bool
    {
        // Coupon codes are typically uppercase letters and numbers
        // Length between 4-15 characters
        return preg_match('/^[A-Z0-9]{4,15}$/', strtoupper($code)) === 1;
    }

    /**
     * Check if IP address is in allowed range
     * 
     * @param array<int, string> $allowed_ranges
     */
    public static function is_ip_allowed(string $ip, array $allowed_ranges): bool
    {
        foreach ($allowed_ranges as $range) {
            if (self::ip_in_range($ip, $range)) {
                return true;
            }
        }
        
        return false;
    }

    private static function ip_in_range(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }
        
        list($subnet, $bits) = explode('/', $range);
        $ip_long = ip2long($ip);
        $subnet_long = ip2long($subnet);
        $mask = -1 << (32 - (int) $bits);
        $subnet_long &= $mask;
        
        return ($ip_long & $mask) === $subnet_long;
    }
}
