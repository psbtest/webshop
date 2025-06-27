<?php
declare(strict_types=1);

namespace App\Helpers;

class StringHelper
{
    /**
     * Generate URL-friendly slug from string
     */
    public static function create_slug(string $text, string $divider = '-'): string
    {
        // Replace non-letter or non-digit characters with divider
        $text = preg_replace('~[^\pL\d]+~u', $divider, $text);
        
        // Transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        
        // Remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);
        
        // Trim dividers
        $text = trim($text, $divider);
        
        // Remove duplicate dividers
        $text = preg_replace('~-+~', $divider, $text);
        
        // Lowercase
        $text = strtolower($text);
        
        return empty($text) ? 'n-a' : $text;
    }

    /**
     * Sanitize string for output
     */
    public static function sanitize(string $input): string
    {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Truncate string to specified length
     */
    public static function truncate(string $text, int $length = 100, string $ending = '...'): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        
        return mb_substr($text, 0, $length - mb_strlen($ending)) . $ending;
    }

    /**
     * Extract plain text from HTML
     */
    public static function strip_html(string $html): string
    {
        return strip_tags($html);
    }

    /**
     * Convert string to title case
     */
    public static function title_case(string $text): string
    {
        return mb_convert_case($text, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Generate random string
     */
    public static function random(int $length = 10): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $result = '';
        
        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return $result;
    }

    /**
     * Check if string contains only alphanumeric characters
     */
    public static function is_alphanumeric(string $text): bool
    {
        return ctype_alnum($text);
    }

    /**
     * Generate excerpt from text
     */
    public static function excerpt(string $text, int $word_limit = 55): string
    {
        $words = explode(' ', $text);
        
        if (count($words) <= $word_limit) {
            return $text;
        }
        
        return implode(' ', array_slice($words, 0, $word_limit)) . '...';
    }

    /**
     * Convert camelCase to snake_case
     */
    public static function camel_to_snake(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }

    /**
     * Convert snake_case to camelCase
     */
    public static function snake_to_camel(string $input): string
    {
        return lcfirst(str_replace('_', '', ucwords($input, '_')));
    }

    /**
     * Mask sensitive information (like email, phone)
     */
    public static function mask_email(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return $email;
        }
        
        $username = $parts[0];
        $domain = $parts[1];
        
        $masked_username = substr($username, 0, 2) . str_repeat('*', strlen($username) - 2);
        
        return $masked_username . '@' . $domain;
    }

    /**
     * Mask phone number
     */
    public static function mask_phone(string $phone): string
    {
        $length = strlen($phone);
        if ($length <= 4) {
            return $phone;
        }
        
        return substr($phone, 0, 2) . str_repeat('*', $length - 4) . substr($phone, -2);
    }

    /**
     * Format file size in human readable format
     */
    public static function format_bytes(int $size, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }

    /**
     * Pluralize word based on count
     */
    public static function pluralize(int $count, string $singular, string $plural = ''): string
    {
        if (empty($plural)) {
            $plural = $singular . 's';
        }
        
        return $count . ' ' . ($count === 1 ? $singular : $plural);
    }

    /**
     * Convert text to URL-safe format
     */
    public static function url_safe(string $text): string
    {
        return urlencode($text);
    }

    /**
     * Check if string starts with given substring
     */
    public static function starts_with(string $haystack, string $needle): bool
    {
        return strpos($haystack, $needle) === 0;
    }

    /**
     * Check if string ends with given substring
     */
    public static function ends_with(string $haystack, string $needle): bool
    {
        $length = strlen($needle);
        if ($length === 0) {
            return true;
        }
        
        return substr($haystack, -$length) === $needle;
    }

    /**
     * Remove accents from string
     */
    public static function remove_accents(string $text): string
    {
        $transliteration = [
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A',
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'Ç' => 'C', 'ç' => 'c', 'Ñ' => 'N', 'ñ' => 'n',
        ];
        
        return strtr($text, $transliteration);
    }

    /**
     * Generate a secure hash
     */
    public static function secure_hash(string $data, string $salt = ''): string
    {
        return hash('sha256', $data . $salt);
    }

    /**
     * Format price with currency symbol
     */
    public static function format_price(float $price, string $currency = 'EUR'): string
    {
        $symbols = [
            'EUR' => '€',
            'USD' => ',
            'GBP' => '£',
        ];
        
        $symbol = $symbols[$currency] ?? $currency;
        
        return $symbol . number_format($price, 2, ',', '.');
    }

    /**
     * Clean phone number format
     */
    public static function clean_phone(string $phone): string
    {
        return preg_replace('/[^0-9+]/', '', $phone);
    }

    /**
     * Format Dutch phone number
     */
    public static function format_dutch_phone(string $phone): string
    {
        $cleaned = self::clean_phone($phone);
        
        // Remove country code if present
        if (self::starts_with($cleaned, '+31')) {
            $cleaned = '0' . substr($cleaned, 3);
        } elseif (self::starts_with($cleaned, '0031')) {
            $cleaned = '0' . substr($cleaned, 4);
        }
        
        // Format as 06-12345678 or 010-1234567
        if (strlen($cleaned) === 10) {
            if (self::starts_with($cleaned, '06')) {
                return substr($cleaned, 0, 2) . '-' . substr($cleaned, 2);
            } else {
                return substr($cleaned, 0, 3) . '-' . substr($cleaned, 3);
            }
        }
        
        return $phone; // Return original if can't format
    }

    /**
     * Convert markdown to HTML (basic)
     */
    public static function markdown_to_html(string $markdown): string
    {
        // Basic markdown conversion
        $html = $markdown;
        
        // Headers
        $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);
        $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
        
        // Bold and italic
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);
        
        // Links
        $html = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2">$1</a>', $html);
        
        // Line breaks
        $html = nl2br($html);
        
        return $html;
    }

    /**
     * Generate breadcrumb from URL path
     * 
     * @return array<int, array<string, string>>
     */
    public static function generate_breadcrumb(string $path, array $custom_names = []): array
    {
        $segments = explode('/', trim($path, '/'));
        $breadcrumb = [];
        $current_path = '';
        
        foreach ($segments as $segment) {
            if (empty($segment)) continue;
            
            $current_path .= '/' . $segment;
            
            $name = $custom_names[$segment] ?? self::title_case(str_replace(['-', '_'], ' ', $segment));
            
            $breadcrumb[] = [
                'name' => $name,
                'url' => $current_path,
            ];
        }
        
        return $breadcrumb;
    }

    /**
     * Extract domain from URL
     */
    public static function extract_domain(string $url): string
    {
        $parsed = parse_url($url);
        return $parsed['host'] ?? '';
    }

    /**
     * Check if string is valid JSON
     */
    public static function is_json(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Convert string to boolean
     */
    public static function to_boolean(string $value): bool
    {
        $value = strtolower(trim($value));
        return in_array($value, ['true', '1', 'yes', 'on', 'enabled']);
    }

    /**
     * Generate initials from name
     */
    public static function get_initials(string $name, int $max_initials = 2): string
    {
        $words = explode(' ', trim($name));
        $initials = '';
        
        for ($i = 0; $i < min(count($words), $max_initials); $i++) {
            if (!empty($words[$i])) {
                $initials .= strtoupper($words[$i][0]);
            }
        }
        
        return $initials;
    }

    /**
     * Calculate reading time in minutes
     */
    public static function reading_time(string $text, int $words_per_minute = 200): int
    {
        $word_count = str_word_count(strip_tags($text));
        return max(1, ceil($word_count / $words_per_minute));
    }

    /**
     * Highlight search terms in text
     */
    public static function highlight_search(string $text, string $search, string $highlight_class = 'highlight'): string
    {
        if (empty($search)) {
            return $text;
        }
        
        $search_terms = explode(' ', $search);
        
        foreach ($search_terms as $term) {
            $term = trim($term);
            if (strlen($term) > 2) {
                $text = preg_replace(
                    '/(' . preg_quote($term, '/') . ')/i',
                    '<span class="' . $highlight_class . '">$1</span>',
                    $text
                );
            }
        }
        
        return $text;
    }
}
