<?php
/**
 * Database Configuration
 * 
 * Database connection settings for MariaDB.
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// =============================================================================
// DATABASE SETTINGS
// =============================================================================

define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'fitness_tracker');
define('DB_USER', 'fitness_user');
define('DB_PASS', 'your_secure_password_here'); // CHANGE THIS!
define('DB_CHARSET', 'utf8mb4');

// =============================================================================
// PDO OPTIONS
// =============================================================================

define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
]);

// =============================================================================
// DATABASE CONNECTION CLASS
// =============================================================================

class Database {
    private static ?PDO $instance = null;

    /**
     * Get database connection instance (singleton)
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = sprintf(
                    "mysql:host=%s;port=%d;dbname=%s;charset=%s",
                    DB_HOST,
                    DB_PORT,
                    DB_NAME,
                    DB_CHARSET
                );

                self::$instance = new PDO($dsn, DB_USER, DB_PASS, DB_OPTIONS);
            } catch (PDOException $e) {
                // Log the error but don't expose details
                if (defined('LOGS_PATH')) {
                    error_log(
                        "[" . date('Y-m-d H:i:s') . "] [ERROR] Database connection failed: " . $e->getMessage() . "\n",
                        3,
                        LOGS_PATH . '/error.log'
                    );
                }
                
                if (APP_ENV === 'development') {
                    die("Database connection failed: " . $e->getMessage());
                } else {
                    die("Database connection failed. Please try again later.");
                }
            }
        }

        return self::$instance;
    }

    /**
     * Close database connection
     */
    public static function closeConnection(): void {
        self::$instance = null;
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
