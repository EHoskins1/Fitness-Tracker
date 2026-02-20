<?php
/**
 * Logout Handler
 */

require_once __DIR__ . '/bootstrap.php';

use App\Utils\Session;
use App\Utils\Logger;

$userId = Session::getUserId();

if ($userId) {
    Logger::logout($userId);
}

Session::destroy();
Session::start();
Session::flash('success', 'You have been logged out.');

header('Location: ' . BASE_URL . 'login.php');
exit;
