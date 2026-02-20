<?php
/**
 * Registration Page
 */

require_once __DIR__ . '/bootstrap.php';

use App\Utils\Session;
use App\Utils\Validator;
use App\Models\User;
use App\Middleware\Auth;
use App\Utils\Logger;

// Redirect if already logged in
Auth::requireGuest();

$errors = [];
$username = '';
$email = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    Auth::requireCsrf();
    
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';
    
    $validator = new Validator();
    
    // Validate username
    $validator->username($username);
    
    // Validate email (optional)
    $validator->email($email);
    
    // Validate password
    $validator->password($password);
    
    // Check password confirmation
    if ($password !== $passwordConfirm) {
        $errors['password_confirm'] = 'Passwords do not match.';
    }
    
    // Check for validation errors
    $errors = array_merge($errors, $validator->getErrors());
    
    // Check if username already exists
    if (empty($errors['username'])) {
        $userModel = new User();
        if ($userModel->usernameExists($username)) {
            $errors['username'] = 'Username is already taken.';
        }
    }
    
    // Create user if no errors
    if (empty($errors)) {
        $userModel = new User();
        $userId = $userModel->create($username, $password, $email ?: null);
        
        if ($userId) {
            Logger::info("New user registered: {$username}", $userId);
            Session::flash('success', 'Account created successfully! Please log in.');
            header('Location: ' . BASE_URL . 'login.php');
            exit;
        } else {
            $errors['general'] = 'An error occurred. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h1 class="auth-title">🥊 <?= APP_NAME ?></h1>
            <h2>Create Account</h2>
            
            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-error"><?= Validator::sanitizeOutput($errors['general']) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" class="auth-form">
                <?= Session::csrfField() ?>
                
                <div class="form-group <?= isset($errors['username']) ? 'has-error' : '' ?>">
                    <label for="username">Username *</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           value="<?= Validator::sanitizeOutput($username) ?>"
                           required 
                           autofocus
                           autocomplete="username"
                           minlength="<?= USERNAME_MIN_LENGTH ?>"
                           maxlength="<?= USERNAME_MAX_LENGTH ?>"
                           pattern="[a-zA-Z0-9_-]+">
                    <?php if (isset($errors['username'])): ?>
                        <span class="error-text"><?= Validator::sanitizeOutput($errors['username']) ?></span>
                    <?php endif; ?>
                    <span class="help-text">3-50 characters, letters, numbers, underscore, hyphen</span>
                </div>
                
                <div class="form-group <?= isset($errors['email']) ? 'has-error' : '' ?>">
                    <label for="email">Email (optional)</label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="<?= Validator::sanitizeOutput($email) ?>"
                           autocomplete="email"
                           maxlength="150">
                    <?php if (isset($errors['email'])): ?>
                        <span class="error-text"><?= Validator::sanitizeOutput($errors['email']) ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group <?= isset($errors['password']) ? 'has-error' : '' ?>">
                    <label for="password">Password *</label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required
                           autocomplete="new-password"
                           minlength="<?= PASSWORD_MIN_LENGTH ?>">
                    <?php if (isset($errors['password'])): ?>
                        <span class="error-text"><?= Validator::sanitizeOutput($errors['password']) ?></span>
                    <?php endif; ?>
                    <span class="help-text">Min 8 characters, must include a letter and a number</span>
                </div>
                
                <div class="form-group <?= isset($errors['password_confirm']) ? 'has-error' : '' ?>">
                    <label for="password_confirm">Confirm Password *</label>
                    <input type="password" 
                           id="password_confirm" 
                           name="password_confirm" 
                           required
                           autocomplete="new-password">
                    <?php if (isset($errors['password_confirm'])): ?>
                        <span class="error-text"><?= Validator::sanitizeOutput($errors['password_confirm']) ?></span>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Create Account</button>
            </form>
            
            <div class="auth-links">
                <a href="<?= BASE_URL ?>login.php">Already have an account? Login</a>
            </div>
        </div>
    </div>
</body>
</html>
