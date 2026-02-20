<?php
/**
 * Index / Entry Point
 * 
 * Redirects to dashboard or login page.
 */

require_once __DIR__ . '/bootstrap.php';

use App\Utils\Session;

if (Session::isLoggedIn()) {
    header('Location: ' . BASE_URL . 'dashboard.php');
} else {
    header('Location: ' . BASE_URL . 'login.php');
}
exit;
