<?php
/**
 * Application Configuration
 * 
 * Central configuration file for the Fitness Tracker application.
 */

// Prevent direct access
if (!defined('APP_ROOT')) {
    die('Direct access not permitted');
}

// =============================================================================
// APPLICATION SETTINGS
// =============================================================================

define('APP_NAME', 'Fitness Tracker');
define('APP_VERSION', '1.0.0');
define('APP_ENV', 'development'); // 'development' or 'production'

// =============================================================================
// PATH SETTINGS
// =============================================================================

define('BASE_URL', '/'); // Change this if app is in subdirectory
define('PUBLIC_PATH', APP_ROOT . '/public');
define('CONFIG_PATH', APP_ROOT . '/config');
define('APP_PATH', APP_ROOT . '/app');
define('LOGS_PATH', APP_ROOT . '/logs');

// =============================================================================
// SESSION SETTINGS
// =============================================================================

define('SESSION_LIFETIME', 7200); // 2 hours in seconds
define('SESSION_NAME', 'fitness_tracker_session');
define('SESSION_SECURE', true); // Set to true for HTTPS
define('SESSION_HTTPONLY', true);

// =============================================================================
// SECURITY SETTINGS
// =============================================================================

define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_MAX_LENGTH', 128);
define('LOGIN_RATE_LIMIT', 5); // Max attempts
define('LOGIN_RATE_WINDOW', 900); // 15 minutes in seconds
define('PASSWORD_RESET_EXPIRY', 3600); // 1 hour in seconds

// =============================================================================
// LOGGING SETTINGS
// =============================================================================

define('LOG_LEVEL', APP_ENV === 'development' ? 'DEBUG' : 'WARNING');
define('LOG_ERRORS', true);
define('LOG_ACCESS', true);
define('LOG_MAX_SIZE', 10 * 1024 * 1024); // 10MB

// =============================================================================
// VALIDATION LIMITS
// =============================================================================

define('USERNAME_MIN_LENGTH', 3);
define('USERNAME_MAX_LENGTH', 50);
define('NOTES_MAX_LENGTH', 10000);
define('WEIGHT_MIN', 0.01);
define('WEIGHT_MAX', 999.99);
define('BODY_FAT_MIN', 0.01);
define('BODY_FAT_MAX', 99.99);
define('DURATION_MIN', 1);
define('DURATION_MAX', 1440);
define('INTENSITY_MIN', 1);
define('INTENSITY_MAX', 10);

// =============================================================================
// ERROR HANDLING
// =============================================================================

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', LOGS_PATH . '/php_errors.log');
}

// =============================================================================
// TIMEZONE
// =============================================================================

date_default_timezone_set('UTC');
