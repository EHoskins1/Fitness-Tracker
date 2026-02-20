<?php
/**
 * Login Page
 */

require_once __DIR__ . '/bootstrap.php';

use App\Utils\Session;
use App\Utils\Validator;
use App\Models\User;
use App\Middleware\Auth;
use App\Utils\Logger;

// Redirect if already logged in
Auth::requireGuest();

$error = '';
$username = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    Auth::requireCsrf();
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Check rate limit
    if (!Auth::checkRateLimit($username)) {
        $remaining = Auth::getRateLimitRemaining($username);
        $minutes = ceil($remaining / 60);
        $error = "Too many login attempts. Please try again in {$minutes} minute(s).";
    } else {
        $validator = new Validator();
        
        if (empty($username) || empty($password)) {
            $error = 'Please enter your username and password.';
        } else {
            $userModel = new User();
            $user = $userModel->findByUsername($username);
            
            if ($user && $userModel->verifyPassword($password, $user['password_hash'])) {
                // Successful login
                Auth::clearAttempts($username);
                Session::login($user['id'], $user['name']);
                Logger::loginAttempt($username, true);
                
                header('Location: ' . BASE_URL . 'dashboard.php');
                exit;
            } else {
                // Failed login
                Auth::recordFailedAttempt($username);
                Logger::loginAttempt($username, false);
                $error = 'Invalid username or password.';
            }
        }
    }
}

// Get flash messages
$flashError = Session::getFlash('error');
$flashSuccess = Session::getFlash('success');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1 class="auth-title">🥊 <?= APP_NAME ?></h1>
            <h2>Login</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= Validator::sanitizeOutput($error) ?></div>
            <?php endif; ?>
            
            <?php if ($flashError): ?>
                <div class="alert alert-error"><?= Validator::sanitizeOutput($flashError) ?></div>
            <?php endif; ?>
            
            <?php if ($flashSuccess): ?>
                <div class="alert alert-success"><?= Validator::sanitizeOutput($flashSuccess) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" class="auth-form">
                <?= Session::csrfField() ?>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           value="<?= Validator::sanitizeOutput($username) ?>"
                           required 
                           autofocus
                           autocomplete="username">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required
                           autocomplete="current-password">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Login</button>
            </form>
            
            <div class="auth-links">
                <a href="<?= BASE_URL ?>register.php">Create an account</a>
                <a href="<?= BASE_URL ?>reset-password.php">Forgot password?</a>
            </div>
        </div>
    </div>
</body>
</html>
