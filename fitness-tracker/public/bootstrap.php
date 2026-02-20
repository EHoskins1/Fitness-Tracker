<?php
/**
 * Bootstrap File
 * 
 * Initialize application, load configs, set up autoloading.
 */

// Define application root
define('APP_ROOT', dirname(__DIR__));

// Load configuration
require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/config/database.php';

// Simple autoloader
spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $prefix = 'App\\';
    
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    
    $relativeClass = substr($class, strlen($prefix));
    $file = APP_ROOT . '/app/' . str_replace('\\', '/', $relativeClass) . '.php';
    
    // Handle case sensitivity
    $file = strtolower(dirname($file)) . '/' . basename($file);
    
    if (file_exists($file)) {
        require_once $file;
    }
});

// Initialize logger level
\App\Utils\Logger::setLevel(LOG_LEVEL);

// Start session for all requests
\App\Utils\Session::start();
