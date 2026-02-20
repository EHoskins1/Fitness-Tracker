<?php
/**
 * Session Helper Class
 * 
 * Manages PHP sessions securely.
 */

namespace App\Utils;

class Session {
    private static bool $started = false;

    /**
     * Start the session
     */
    public static function start(): void {
        if (self::$started) {
            return;
        }

        // Configure session before starting
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        
        if (defined('SESSION_SECURE') && SESSION_SECURE) {
            ini_set('session.cookie_secure', '1');
        }
        
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
        
        session_name(SESSION_NAME);
        session_start();
        
        self::$started = true;
        
        // Check session expiry
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
            self::destroy();
            self::start();
        }
        
        $_SESSION['last_activity'] = time();
    }

    /**
     * Regenerate session ID (call after login)
     */
    public static function regenerate(): void {
        if (self::$started) {
            session_regenerate_id(true);
        }
    }

    /**
     * Destroy the session
     */
    public static function destroy(): void {
        if (self::$started) {
            $_SESSION = [];
            
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
            
            session_destroy();
            self::$started = false;
        }
    }

    /**
     * Set a session value
     */
    public static function set(string $key, mixed $value): void {
        self::start();
        $_SESSION[$key] = $value;
    }

    /**
     * Get a session value
     */
    public static function get(string $key, mixed $default = null): mixed {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if session has a key
     */
    public static function has(string $key): bool {
        self::start();
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a session value
     */
    public static function remove(string $key): void {
        self::start();
        unset($_SESSION[$key]);
    }

    /**
     * Set flash message (one-time display)
     */
    public static function flash(string $type, string $message): void {
        self::set('flash_' . $type, $message);
    }

    /**
     * Get and clear flash message
     */
    public static function getFlash(string $type): ?string {
        $message = self::get('flash_' . $type);
        self::remove('flash_' . $type);
        return $message;
    }

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn(): bool {
        return self::has('user_id');
    }

    /**
     * Get current user ID
     */
    public static function getUserId(): ?int {
        return self::get('user_id');
    }

    /**
     * Set user login
     */
    public static function login(int $userId, string $username): void {
        self::regenerate();
        self::set('user_id', $userId);
        self::set('username', $username);
        self::set('login_time', time());
    }

    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken(): string {
        if (!self::has(CSRF_TOKEN_NAME)) {
            self::set(CSRF_TOKEN_NAME, bin2hex(random_bytes(32)));
        }
        return self::get(CSRF_TOKEN_NAME);
    }

    /**
     * Validate CSRF token
     */
    public static function validateCsrfToken(string $token): bool {
        $sessionToken = self::get(CSRF_TOKEN_NAME);
        if ($sessionToken === null) {
            return false;
        }
        return hash_equals($sessionToken, $token);
    }

    /**
     * Get CSRF hidden input field
     */
    public static function csrfField(): string {
        $token = self::generateCsrfToken();
        return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($token) . '">';
    }
}
