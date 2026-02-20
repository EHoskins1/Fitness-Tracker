<?php
/**
 * Auth Middleware
 * 
 * Handles authentication checks and rate limiting.
 */

namespace App\Middleware;

use App\Utils\Session;
use App\Utils\Logger;

class Auth {
    /**
     * Require user to be logged in
     */
    public static function requireLogin(): void {
        Session::start();
        
        if (!Session::isLoggedIn()) {
            Session::flash('error', 'Please log in to access this page.');
            header('Location: ' . BASE_URL . 'login.php');
            exit;
        }
    }

    /**
     * Require user to be a guest (not logged in)
     */
    public static function requireGuest(): void {
        Session::start();
        
        if (Session::isLoggedIn()) {
            header('Location: ' . BASE_URL . 'dashboard.php');
            exit;
        }
    }

    /**
     * Check and enforce login rate limiting
     */
    public static function checkRateLimit(string $identifier): bool {
        Session::start();
        
        $key = 'login_attempts_' . md5($identifier);
        $attempts = Session::get($key, ['count' => 0, 'first_attempt' => 0]);
        
        // Reset if window has passed
        if (time() - $attempts['first_attempt'] > LOGIN_RATE_WINDOW) {
            $attempts = ['count' => 0, 'first_attempt' => time()];
        }
        
        if ($attempts['count'] >= LOGIN_RATE_LIMIT) {
            $remaining = LOGIN_RATE_WINDOW - (time() - $attempts['first_attempt']);
            Logger::warning("Rate limit hit for identifier: {$identifier}");
            return false;
        }
        
        return true;
    }

    /**
     * Record a failed login attempt
     */
    public static function recordFailedAttempt(string $identifier): void {
        Session::start();
        
        $key = 'login_attempts_' . md5($identifier);
        $attempts = Session::get($key, ['count' => 0, 'first_attempt' => time()]);
        
        if ($attempts['count'] === 0) {
            $attempts['first_attempt'] = time();
        }
        
        $attempts['count']++;
        Session::set($key, $attempts);
    }

    /**
     * Clear login attempts after successful login
     */
    public static function clearAttempts(string $identifier): void {
        Session::start();
        $key = 'login_attempts_' . md5($identifier);
        Session::remove($key);
    }

    /**
     * Validate CSRF token from request
     */
    public static function validateCsrf(): bool {
        $token = $_POST[CSRF_TOKEN_NAME] ?? '';
        
        if (!Session::validateCsrfToken($token)) {
            Logger::warning("CSRF validation failed");
            return false;
        }
        
        return true;
    }

    /**
     * Require valid CSRF token
     */
    public static function requireCsrf(): void {
        if (!self::validateCsrf()) {
            http_response_code(403);
            die('Invalid security token. Please refresh the page and try again.');
        }
    }

    /**
     * Get remaining rate limit time
     */
    public static function getRateLimitRemaining(string $identifier): int {
        Session::start();
        
        $key = 'login_attempts_' . md5($identifier);
        $attempts = Session::get($key, ['count' => 0, 'first_attempt' => 0]);
        
        if ($attempts['count'] < LOGIN_RATE_LIMIT) {
            return 0;
        }
        
        $remaining = LOGIN_RATE_WINDOW - (time() - $attempts['first_attempt']);
        return max(0, $remaining);
    }
}
