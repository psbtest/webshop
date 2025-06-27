<?php
declare(strict_types=1);

namespace App\Middleware;

class AdminMiddleware
{
    public static function handle(): void
    {
        // Allow login page without authentication
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        
        if (strpos($request_uri, '/admin/login') !== false) {
            return;
        }
        
        // Check if admin is logged in
        if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
            header('Location: /admin/login');
            exit;
        }
        
        // Optional: Check for admin session timeout
        $session_timeout = 3600; // 1 hour
        if (isset($_SESSION['admin_last_activity'])) {
            if (time() - $_SESSION['admin_last_activity'] > $session_timeout) {
                // Session expired
                unset($_SESSION['admin_logged_in'], $_SESSION['admin_id'], $_SESSION['admin_username']);
                session_destroy();
                header('Location: /admin/login?expired=1');
                exit;
            }
        }
        
        // Update last activity time
        $_SESSION['admin_last_activity'] = time();
    }
}
