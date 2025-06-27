<?php
declare(strict_types=1);

namespace App\Middleware;

class AuthMiddleware
{
    public static function handle(): void
    {
        // General authentication middleware
        // Can be used for customer authentication if needed
        
        // Example: Rate limiting
        self::rate_limit();
        
        // Example: CSRF protection for forms
        self::csrf_protection();
    }
    
    private static function rate_limit(): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $current_time = time();
        $window = 60; // 1 minute window
        $max_requests = 100; // Max requests per window
        
        if (!isset($_SESSION['rate_limit'])) {
            $_SESSION['rate_limit'] = [];
        }
        
        // Clean old entries
        $_SESSION['rate_limit'] = array_filter($_SESSION['rate_limit'], function($timestamp) use ($current_time, $window) {
            return ($current_time - $timestamp) < $window;
        });
        
        // Count requests from this IP
        $requests_from_ip = array_filter($_SESSION['rate_limit'], function($entry) use ($ip) {
            return $entry['ip'] === $ip;
        });
        
        if (count($requests_from_ip) >= $max_requests) {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Rate limit exceeded']);
            exit;
        }
        
        // Add current request
        $_SESSION['rate_limit'][] = [
            'ip' => $ip,
            'timestamp' => $current_time
        ];
    }
    
    private static function csrf_protection(): void
    {
        // Only for POST, PUT, DELETE requests
        if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'])) {
            return;
        }
        
        // Skip for API endpoints
        if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
            return;
        }
        
        // Generate CSRF token if not exists
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        // Check CSRF token for form submissions
        $submitted_token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        if (!hash_equals($_SESSION['csrf_token'], $submitted_token)) {
            http_response_code(403);
            echo 'CSRF token mismatch';
            exit;
        }
    }
    
    public static function get_csrf_token(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
}
