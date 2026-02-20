<?php
/**
 * Password Reset Page
 */

require_once __DIR__ . '/bootstrap.php';

use App\Utils\Session;
use App\Utils\Validator;
use App\Models\User;
use App\Middleware\Auth;
use App\Utils\Logger;

// Redirect if already logged in
Auth::requireGuest();

$step = 'request'; // request, verify, reset
$error = '';
$success = '';
$username = '';
$token = $_GET['token'] ?? '';

// If token provided, go to reset step
if (!empty($token)) {
    $step = 'reset';
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireCsrf();
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'request') {
        // Step 1: Request reset token
        $username = trim($_POST['username'] ?? '');
        
        if (empty($username)) {
            $error = 'Please enter your username.';
        } else {
            $userModel = new User();
            $user = $userModel->findByUsername($username);
            
            if ($user) {
                $token = $userModel->createPasswordResetToken($user['id']);
                
                if ($token) {
                    // In a real app, you'd email this token
                    // For now, we display it (since email is optional)
                    $step = 'verify';
                    $success = "Reset token generated. Token: <code>{$token}</code><br><small>In production, this would be emailed to you.</small>";
                } else {
                    $error = 'An error occurred. Please try again.';
                }
            } else {
                // Don't reveal if user exists
                $step = 'verify';
                $success = 'If an account with that username exists, a reset token has been generated.';
            }
        }
    } elseif ($action === 'reset') {
        // Step 2: Reset password with token
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        
        $validator = new Validator();
        $validator->password($password);
        $errors = $validator->getErrors();
        
        if ($password !== $passwordConfirm) {
            $errors['password_confirm'] = 'Passwords do not match.';
        }
        
        if (empty($token)) {
            $error = 'Invalid or missing reset token.';
        } elseif (!empty($errors)) {
            $error = implode(' ', $errors);
        } else {
            $userModel = new User();
            $userId = $userModel->verifyPasswordResetToken($token);
            
            if ($userId) {
                if ($userModel->updatePassword($userId, $password)) {
                    $userModel->deletePasswordResetTokenByUserId($userId);
                    Logger::info("Password reset completed", $userId);
                    Session::flash('success', 'Password has been reset. Please log in.');
                    header('Location: ' . BASE_URL . 'login.php');
                    exit;
                } else {
                    $error = 'An error occurred. Please try again.';
                }
            } else {
                $error = 'Invalid or expired reset token.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1 class="auth-title">🥊 <?= APP_NAME ?></h1>
            <h2>Reset Password</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <?php if ($step === 'request'): ?>
                <p class="auth-info">Enter your username to receive a password reset token.</p>
                
                <form method="POST" action="" class="auth-form">
                    <?= Session::csrfField() ?>
                    <input type="hidden" name="action" value="request">
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               value="<?= Validator::sanitizeOutput($username) ?>"
                               required 
                               autofocus>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Request Reset Token</button>
                </form>
            
            <?php elseif ($step === 'verify'): ?>
                <p class="auth-info">Enter your reset token and new password.</p>
                
                <form method="POST" action="" class="auth-form">
                    <?= Session::csrfField() ?>
                    <input type="hidden" name="action" value="reset">
                    
                    <div class="form-group">
                        <label for="token">Reset Token</label>
                        <input type="text" 
                               id="token" 
                               name="token" 
                               required 
                               autofocus
                               placeholder="Paste your reset token here">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required
                               minlength="<?= PASSWORD_MIN_LENGTH ?>">
                        <span class="help-text">Min 8 characters, must include a letter and a number</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="password_confirm">Confirm New Password</label>
                        <input type="password" 
                               id="password_confirm" 
                               name="password_confirm" 
                               required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
                </form>
            
            <?php elseif ($step === 'reset'): ?>
                <p class="auth-info">Enter your new password.</p>
                
                <form method="POST" action="" class="auth-form">
                    <?= Session::csrfField() ?>
                    <input type="hidden" name="action" value="reset">
                    <input type="hidden" name="token" value="<?= Validator::sanitizeOutput($token) ?>">
                    
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required
                               autofocus
                               minlength="<?= PASSWORD_MIN_LENGTH ?>">
                        <span class="help-text">Min 8 characters, must include a letter and a number</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="password_confirm">Confirm New Password</label>
                        <input type="password" 
                               id="password_confirm" 
                               name="password_confirm" 
                               required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
                </form>
            <?php endif; ?>
            
            <div class="auth-links">
                <a href="<?= BASE_URL ?>login.php">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
