<?php
/**
 * Log Body Metrics Page
 */

require_once __DIR__ . '/bootstrap.php';

use App\Utils\Session;
use App\Utils\Validator;
use App\Models\BodyMetric;
use App\Middleware\Auth;

// Require login
Auth::requireLogin();

$userId = Session::getUserId();
$errors = [];

// Default form values
$formData = [
    'date' => date('Y-m-d'),
    'weight_kg' => '',
    'body_fat_percent' => '',
    'notes' => ''
];

// Get latest metrics for reference
$bodyMetricModel = new BodyMetric();
$latestMetric = $bodyMetricModel->getLatest($userId);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireCsrf();
    
    // Get form data
    $formData = [
        'date' => trim($_POST['date'] ?? ''),
        'weight_kg' => !empty($_POST['weight_kg']) ? (float) $_POST['weight_kg'] : null,
        'body_fat_percent' => !empty($_POST['body_fat_percent']) ? (float) $_POST['body_fat_percent'] : null,
        'notes' => trim($_POST['notes'] ?? '') ?: null
    ];
    
    // Validate
    $validator = new Validator();
    $validator->date($formData['date']);
    $validator->weight($formData['weight_kg']);
    $validator->bodyFat($formData['body_fat_percent']);
    $validator->notes($formData['notes']);
    
    // At least one metric required
    if ($formData['weight_kg'] === null && $formData['body_fat_percent'] === null) {
        $errors['general'] = 'Please enter at least weight or body fat percentage.';
    }
    
    $errors = array_merge($errors, $validator->getErrors());
    
    if (empty($errors)) {
        $metricData = [
            'user_id' => $userId,
            'date' => $formData['date'],
            'weight_kg' => $formData['weight_kg'],
            'body_fat_percent' => $formData['body_fat_percent'],
            'notes' => $formData['notes']
        ];
        
        $metricId = $bodyMetricModel->create($metricData);
        
        if ($metricId) {
            Session::flash('success', 'Body metrics logged successfully!');
            header('Location: ' . BASE_URL . 'dashboard.php');
            exit;
        } else {
            $errors['general'] = 'An error occurred. Please try again.';
        }
    }
}

$pageTitle = 'Log Body Metrics';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/partials/nav.php'; ?>
    
    <main class="container">
        <div class="page-header">
            <h1>⚖️ Log Body Metrics</h1>
        </div>
        
        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-error"><?= Validator::sanitizeOutput($errors['general']) ?></div>
        <?php endif; ?>
        
        <?php if ($latestMetric): ?>
            <div class="card card-info">
                <h3>Latest Entry (<?= $latestMetric['date'] ?>)</h3>
                <div class="metric-display">
                    <?php if ($latestMetric['weight_kg']): ?>
                        <span class="metric">
                            <strong>Weight:</strong> <?= number_format($latestMetric['weight_kg'], 1) ?> kg
                        </span>
                    <?php endif; ?>
                    <?php if ($latestMetric['body_fat_percent']): ?>
                        <span class="metric">
                            <strong>Body Fat:</strong> <?= number_format($latestMetric['body_fat_percent'], 1) ?>%
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <form method="POST" action="" class="form">
                <?= Session::csrfField() ?>
                
                <div class="form-group <?= isset($errors['date']) ? 'has-error' : '' ?>">
                    <label for="date">Date *</label>
                    <input type="date" 
                           id="date" 
                           name="date" 
                           value="<?= Validator::sanitizeOutput($formData['date']) ?>"
                           required
                           max="<?= date('Y-m-d') ?>">
                    <?php if (isset($errors['date'])): ?>
                        <span class="error-text"><?= Validator::sanitizeOutput($errors['date']) ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-row">
                    <div class="form-group <?= isset($errors['weight_kg']) ? 'has-error' : '' ?>">
                        <label for="weight_kg">Weight (kg)</label>
                        <input type="number" 
                               id="weight_kg" 
                               name="weight_kg" 
                               value="<?= Validator::sanitizeOutput($formData['weight_kg'] ?? '') ?>"
                               step="0.1"
                               min="<?= WEIGHT_MIN ?>"
                               max="<?= WEIGHT_MAX ?>"
                               placeholder="e.g., 75.5">
                        <?php if (isset($errors['weight_kg'])): ?>
                            <span class="error-text"><?= Validator::sanitizeOutput($errors['weight_kg']) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group <?= isset($errors['body_fat_percent']) ? 'has-error' : '' ?>">
                        <label for="body_fat_percent">Body Fat (%)</label>
                        <input type="number" 
                               id="body_fat_percent" 
                               name="body_fat_percent" 
                               value="<?= Validator::sanitizeOutput($formData['body_fat_percent'] ?? '') ?>"
                               step="0.1"
                               min="<?= BODY_FAT_MIN ?>"
                               max="<?= BODY_FAT_MAX ?>"
                               placeholder="e.g., 15.0">
                        <?php if (isset($errors['body_fat_percent'])): ?>
                            <span class="error-text"><?= Validator::sanitizeOutput($errors['body_fat_percent']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-group <?= isset($errors['notes']) ? 'has-error' : '' ?>">
                    <label for="notes">Notes</label>
                    <textarea id="notes" 
                              name="notes" 
                              rows="3"
                              maxlength="<?= NOTES_MAX_LENGTH ?>"
                              placeholder="Any observations? Morning/evening weigh-in?"><?= Validator::sanitizeOutput($formData['notes'] ?? '') ?></textarea>
                    <?php if (isset($errors['notes'])): ?>
                        <span class="error-text"><?= Validator::sanitizeOutput($errors['notes']) ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Metrics</button>
                    <a href="<?= BASE_URL ?>dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </main>
    
    <script src="<?= BASE_URL ?>assets/js/app.js"></script>
</body>
</html>
