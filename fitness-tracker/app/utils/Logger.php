<?php
/**
 * Logger Utility Class
 * 
 * Handles application logging with different levels.
 */

namespace App\Utils;

class Logger {
    private const LEVELS = [
        'DEBUG'   => 0,
        'INFO'    => 1,
        'WARNING' => 2,
        'ERROR'   => 3
    ];

    private static string $currentLevel = 'DEBUG';

    /**
     * Set the minimum log level
     */
    public static function setLevel(string $level): void {
        if (isset(self::LEVELS[strtoupper($level)])) {
            self::$currentLevel = strtoupper($level);
        }
    }

    /**
     * Check if a level should be logged
     */
    private static function shouldLog(string $level): bool {
        return self::LEVELS[$level] >= self::LEVELS[self::$currentLevel];
    }

    /**
     * Format a log message
     */
    private static function formatMessage(string $level, string $message, ?int $userId = null): string {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $userStr = $userId !== null ? $userId : '-';
        
        return sprintf("[%s] [%s] [%s] [%s] %s\n", $timestamp, $level, $userStr, $ip, $message);
    }

    /**
     * Write to log file
     */
    private static function write(string $filename, string $message): void {
        $logPath = defined('LOGS_PATH') ? LOGS_PATH : __DIR__ . '/../../logs';
        $filePath = $logPath . '/' . $filename;
        
        // Check log rotation
        if (file_exists($filePath) && filesize($filePath) > LOG_MAX_SIZE) {
            self::rotate($filePath);
        }
        
        error_log($message, 3, $filePath);
    }

    /**
     * Rotate log file
     */
    private static function rotate(string $filePath): void {
        $rotatedPath = $filePath . '.' . date('Y-m-d-His') . '.old';
        rename($filePath, $rotatedPath);
        
        // Compress old log
        if (function_exists('gzopen')) {
            $gz = gzopen($rotatedPath . '.gz', 'w9');
            gzwrite($gz, file_get_contents($rotatedPath));
            gzclose($gz);
            unlink($rotatedPath);
        }
    }

    /**
     * Mask sensitive data
     */
    private static function maskSensitive(string $message): string {
        // Mask email addresses
        $message = preg_replace(
            '/([a-zA-Z0-9._%+-]+)@([a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/',
            '***@$2',
            $message
        );
        
        // Mask potential passwords (in case they slip through)
        $message = preg_replace(
            '/password["\']?\s*[:=]\s*["\']?[^"\'}\s]+/i',
            'password=***MASKED***',
            $message
        );
        
        return $message;
    }

    /**
     * Log debug message
     */
    public static function debug(string $message, ?int $userId = null): void {
        if (!self::shouldLog('DEBUG')) return;
        
        $formatted = self::formatMessage('DEBUG', self::maskSensitive($message), $userId);
        self::write('debug.log', $formatted);
    }

    /**
     * Log info message (audit trail)
     */
    public static function info(string $message, ?int $userId = null): void {
        if (!self::shouldLog('INFO')) return;
        
        $formatted = self::formatMessage('INFO', self::maskSensitive($message), $userId);
        self::write('access.log', $formatted);
    }

    /**
     * Log warning message
     */
    public static function warning(string $message, ?int $userId = null): void {
        if (!self::shouldLog('WARNING')) return;
        
        $formatted = self::formatMessage('WARNING', self::maskSensitive($message), $userId);
        self::write('error.log', $formatted);
    }

    /**
     * Log error message
     */
    public static function error(string $message, ?int $userId = null): void {
        if (!self::shouldLog('ERROR')) return;
        
        $formatted = self::formatMessage('ERROR', self::maskSensitive($message), $userId);
        self::write('error.log', $formatted);
    }

    /**
     * Log an exception
     */
    public static function exception(\Throwable $e, ?int $userId = null): void {
        $message = sprintf(
            "%s: %s in %s:%d\nStack trace:\n%s",
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        
        self::error($message, $userId);
    }

    /**
     * Log login attempt
     */
    public static function loginAttempt(string $username, bool $success): void {
        $status = $success ? 'SUCCESS' : 'FAILED';
        self::info("Login attempt for user '{$username}': {$status}");
    }

    /**
     * Log logout
     */
    public static function logout(int $userId): void {
        self::info("User logged out", $userId);
    }

    /**
     * Log data change
     */
    public static function dataChange(string $table, string $action, int $recordId, ?int $userId = null): void {
        self::info("Data change: {$action} on {$table} (ID: {$recordId})", $userId);
    }
}
