<?php
/**
 * Log Training Session Page
 */

require_once __DIR__ . '/bootstrap.php';

use App\Utils\Session;
use App\Utils\Validator;
use App\Models\TrainingSession;
use App\Models\SessionType;
use App\Middleware\Auth;

// Require login
Auth::requireLogin();

$userId = Session::getUserId();
$errors = [];
$success = '';

// Get session types
$sessionTypeModel = new SessionType();
$sessionTypes = $sessionTypeModel->getAll($userId);

// Default form values
$formData = [
    'date' => date('Y-m-d'),
    'start_time' => '',
    'duration_minutes' => '',
    'session_type' => '',
    'intensity' => '',
    'location' => '',
    'notes' => '',
    // Extra details
    'distance_km' => '',
    'rounds' => '',
    'sets' => '',
    'reps' => '',
    'exercise' => ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::requireCsrf();
    
    // Get form data
    $formData = [
        'date' => trim($_POST['date'] ?? ''),
        'start_time' => trim($_POST['start_time'] ?? '') ?: null,
        'duration_minutes' => !empty($_POST['duration_minutes']) ? (int) $_POST['duration_minutes'] : null,
        'session_type' => trim($_POST['session_type'] ?? ''),
        'intensity' => !empty($_POST['intensity']) ? (int) $_POST['intensity'] : null,
        'location' => trim($_POST['location'] ?? '') ?: null,
        'notes' => trim($_POST['notes'] ?? '') ?: null,
        // Extra details
        'distance_km' => trim($_POST['distance_km'] ?? ''),
        'rounds' => trim($_POST['rounds'] ?? ''),
        'sets' => trim($_POST['sets'] ?? ''),
        'reps' => trim($_POST['reps'] ?? ''),
        'exercise' => trim($_POST['exercise'] ?? '')
    ];
    
    // Validate
    $validator = new Validator();
    $validator->date($formData['date']);
    $validator->sessionType($formData['session_type']);
    $validator->time($formData['start_time'], 'start_time');
    $validator->duration($formData['duration_minutes']);
    $validator->intensity($formData['intensity']);
    $validator->notes($formData['notes']);
    
    $errors = $validator->getErrors();
    
    if (empty($errors)) {
        $sessionModel = new TrainingSession();
        
        $sessionData = [
            'user_id' => $userId,
            'date' => $formData['date'],
            'start_time' => $formData['start_time'],
            'duration_minutes' => $formData['duration_minutes'],
            'session_type' => $formData['session_type'],
            'intensity' => $formData['intensity'],
            'location' => $formData['location'],
            'notes' => $formData['notes']
        ];
        
        $sessionId = $sessionModel->create($sessionData);
        
        if ($sessionId) {
            // Add extra details
            $details = [];
            if (!empty($formData['distance_km'])) $details['distance_km'] = $formData['distance_km'];
            if (!empty($formData['rounds'])) $details['rounds'] = $formData['rounds'];
            if (!empty($formData['sets'])) $details['sets'] = $formData['sets'];
            if (!empty($formData['reps'])) $details['reps'] = $formData['reps'];
            if (!empty($formData['exercise'])) $details['exercise'] = $formData['exercise'];
            
            if (!empty($details)) {
                $sessionModel->addDetails($sessionId, $details);
            }
            
            Session::flash('success', 'Training session logged successfully!');
            header('Location: ' . BASE_URL . 'dashboard.php');
            exit;
        } else {
            $errors['general'] = 'An error occurred. Please try again.';
        }
    }
}

// Include header
$pageTitle = 'Log Session';
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
            <h1>📝 Log Training Session</h1>
        </div>
        
        <?php if (isset($errors['general'])): ?>
            <div class="alert alert-error"><?= Validator::sanitizeOutput($errors['general']) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <form method="POST" action="" class="form">
                <?= Session::csrfField() ?>
                
                <div class="form-row">
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
                    
                    <div class="form-group <?= isset($errors['start_time']) ? 'has-error' : '' ?>">
                        <label for="start_time">Start Time</label>
                        <input type="time" 
                               id="start_time" 
                               name="start_time" 
                               value="<?= Validator::sanitizeOutput($formData['start_time'] ?? '') ?>">
                        <?php if (isset($errors['start_time'])): ?>
                            <span class="error-text"><?= Validator::sanitizeOutput($errors['start_time']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group <?= isset($errors['session_type']) ? 'has-error' : '' ?>">
                        <label for="session_type">Session Type *</label>
                        <select id="session_type" name="session_type" required>
                            <option value="">Select type...</option>
                            <?php foreach ($sessionTypes as $type): ?>
                                <option value="<?= Validator::sanitizeOutput($type['name']) ?>"
                                        <?= $formData['session_type'] === $type['name'] ? 'selected' : '' ?>>
                                    <?= $type['icon'] ?? '' ?> <?= Validator::sanitizeOutput($type['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['session_type'])): ?>
                            <span class="error-text"><?= Validator::sanitizeOutput($errors['session_type']) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group <?= isset($errors['duration_minutes']) ? 'has-error' : '' ?>">
                        <label for="duration_minutes">Duration (minutes)</label>
                        <input type="number" 
                               id="duration_minutes" 
                               name="duration_minutes" 
                               value="<?= Validator::sanitizeOutput($formData['duration_minutes'] ?? '') ?>"
                               min="<?= DURATION_MIN ?>"
                               max="<?= DURATION_MAX ?>">
                        <?php if (isset($errors['duration_minutes'])): ?>
                            <span class="error-text"><?= Validator::sanitizeOutput($errors['duration_minutes']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group <?= isset($errors['intensity']) ? 'has-error' : '' ?>">
                        <label for="intensity">Intensity (1-10)</label>
                        <input type="range" 
                               id="intensity" 
                               name="intensity" 
                               value="<?= Validator::sanitizeOutput($formData['intensity'] ?? '5') ?>"
                               min="1"
                               max="10"
                               oninput="document.getElementById('intensity_value').textContent = this.value">
                        <span class="intensity-display">Level: <strong id="intensity_value"><?= $formData['intensity'] ?? '5' ?></strong></span>
                        <?php if (isset($errors['intensity'])): ?>
                            <span class="error-text"><?= Validator::sanitizeOutput($errors['intensity']) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" 
                               id="location" 
                               name="location" 
                               value="<?= Validator::sanitizeOutput($formData['location'] ?? '') ?>"
                               maxlength="255"
                               placeholder="e.g., Gym, Park, Home">
                    </div>
                </div>
                
                <fieldset class="form-fieldset">
                    <legend>Extra Details (Optional)</legend>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="distance_km">Distance (km)</label>
                            <input type="number" 
                                   id="distance_km" 
                                   name="distance_km" 
                                   value="<?= Validator::sanitizeOutput($formData['distance_km']) ?>"
                                   step="0.01"
                                   min="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="rounds">Rounds</label>
                            <input type="number" 
                                   id="rounds" 
                                   name="rounds" 
                                   value="<?= Validator::sanitizeOutput($formData['rounds']) ?>"
                                   min="0">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="exercise">Exercise</label>
                            <input type="text" 
                                   id="exercise" 
                                   name="exercise" 
                                   value="<?= Validator::sanitizeOutput($formData['exercise']) ?>"
                                   placeholder="e.g., Squat, Deadlift">
                        </div>
                        
                        <div class="form-group">
                            <label for="sets">Sets</label>
                            <input type="number" 
                                   id="sets" 
                                   name="sets" 
                                   value="<?= Validator::sanitizeOutput($formData['sets']) ?>"
                                   min="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="reps">Reps</label>
                            <input type="number" 
                                   id="reps" 
                                   name="reps" 
                                   value="<?= Validator::sanitizeOutput($formData['reps']) ?>"
                                   min="0">
                        </div>
                    </div>
                </fieldset>
                
                <div class="form-group <?= isset($errors['notes']) ? 'has-error' : '' ?>">
                    <label for="notes">Notes</label>
                    <textarea id="notes" 
                              name="notes" 
                              rows="3"
                              maxlength="<?= NOTES_MAX_LENGTH ?>"
                              placeholder="How did it go? Any observations?"><?= Validator::sanitizeOutput($formData['notes'] ?? '') ?></textarea>
                    <?php if (isset($errors['notes'])): ?>
                        <span class="error-text"><?= Validator::sanitizeOutput($errors['notes']) ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Session</button>
                    <a href="<?= BASE_URL ?>dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </main>
    
    <script src="<?= BASE_URL ?>assets/js/app.js"></script>
</body>
</html>
