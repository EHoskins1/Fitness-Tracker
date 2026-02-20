<?php
/**
 * Dashboard Page
 */

require_once __DIR__ . '/bootstrap.php';

use App\Utils\Session;
use App\Utils\Validator;
use App\Models\TrainingSession;
use App\Models\BodyMetric;
use App\Middleware\Auth;

// Require login
Auth::requireLogin();

$userId = Session::getUserId();
$username = Session::get('username');

// Get weekly stats
$sessionModel = new TrainingSession();
$weeklyStats = $sessionModel->getWeeklyStats($userId);
$recentSessions = $sessionModel->getByUser($userId, 5);

// Get body metrics
$bodyMetricModel = new BodyMetric();
$latestMetric = $bodyMetricModel->getLatest($userId);
$weekAgoMetric = $bodyMetricModel->getWeekAgo($userId);

// Calculate changes
$weightChange = null;
$bodyFatChange = null;
if ($latestMetric && $weekAgoMetric) {
    if ($latestMetric['weight_kg'] && $weekAgoMetric['weight_kg']) {
        $weightChange = $latestMetric['weight_kg'] - $weekAgoMetric['weight_kg'];
    }
    if ($latestMetric['body_fat_percent'] && $weekAgoMetric['body_fat_percent']) {
        $bodyFatChange = $latestMetric['body_fat_percent'] - $weekAgoMetric['body_fat_percent'];
    }
}

// Flash messages
$flashSuccess = Session::getFlash('success');
$flashError = Session::getFlash('error');

$pageTitle = 'Dashboard';
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
        <?php if ($flashSuccess): ?>
            <div class="alert alert-success"><?= Validator::sanitizeOutput($flashSuccess) ?></div>
        <?php endif; ?>
        
        <?php if ($flashError): ?>
            <div class="alert alert-error"><?= Validator::sanitizeOutput($flashError) ?></div>
        <?php endif; ?>
        
        <div class="page-header">
            <h1>Welcome back, <?= Validator::sanitizeOutput($username) ?>! 👋</h1>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="<?= BASE_URL ?>log-session.php" class="quick-action-btn">
                <span class="icon">📝</span>
                <span>Log Session</span>
            </a>
            <a href="<?= BASE_URL ?>log-weight.php" class="quick-action-btn">
                <span class="icon">⚖️</span>
                <span>Log Weight</span>
            </a>
            <a href="<?= BASE_URL ?>calendar.php" class="quick-action-btn">
                <span class="icon">📅</span>
                <span>Calendar</span>
            </a>
            <a href="<?= BASE_URL ?>progress.php" class="quick-action-btn">
                <span class="icon">📈</span>
                <span>Progress</span>
            </a>
        </div>
        
        <!-- Stats Grid -->
        <div class="dashboard-grid">
            <!-- Weekly Stats Card -->
            <div class="card">
                <h2 class="card-title">📊 This Week</h2>
                <div class="stats-grid">
                    <div class="stat">
                        <span class="stat-value"><?= (int) $weeklyStats['session_count'] ?></span>
                        <span class="stat-label">Sessions</span>
                    </div>
                    <div class="stat">
                        <span class="stat-value">
                            <?php 
                            $hours = floor($weeklyStats['total_duration'] / 60);
                            $mins = $weeklyStats['total_duration'] % 60;
                            echo $hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m";
                            ?>
                        </span>
                        <span class="stat-label">Total Time</span>
                    </div>
                    <div class="stat">
                        <span class="stat-value">
                            <?= $weeklyStats['total_distance_km'] > 0 ? number_format($weeklyStats['total_distance_km'], 1) : '0' ?> km
                        </span>
                        <span class="stat-label">Distance</span>
                    </div>
                    <div class="stat">
                        <span class="stat-value">
                            <?= $weeklyStats['avg_intensity'] > 0 ? number_format($weeklyStats['avg_intensity'], 1) : '-' ?>
                        </span>
                        <span class="stat-label">Avg Intensity</span>
                    </div>
                </div>
            </div>
            
            <!-- Body Status Card -->
            <div class="card">
                <h2 class="card-title">⚖️ Body Status</h2>
                <?php if ($latestMetric): ?>
                    <div class="stats-grid">
                        <?php if ($latestMetric['weight_kg']): ?>
                            <div class="stat">
                                <span class="stat-value"><?= number_format($latestMetric['weight_kg'], 1) ?> kg</span>
                                <span class="stat-label">Weight</span>
                                <?php if ($weightChange !== null): ?>
                                    <span class="stat-change <?= $weightChange < 0 ? 'positive' : ($weightChange > 0 ? 'negative' : '') ?>">
                                        <?= $weightChange > 0 ? '+' : '' ?><?= number_format($weightChange, 1) ?> kg
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($latestMetric['body_fat_percent']): ?>
                            <div class="stat">
                                <span class="stat-value"><?= number_format($latestMetric['body_fat_percent'], 1) ?>%</span>
                                <span class="stat-label">Body Fat</span>
                                <?php if ($bodyFatChange !== null): ?>
                                    <span class="stat-change <?= $bodyFatChange < 0 ? 'positive' : ($bodyFatChange > 0 ? 'negative' : '') ?>">
                                        <?= $bodyFatChange > 0 ? '+' : '' ?><?= number_format($bodyFatChange, 1) ?>%
                                    </span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <p class="card-meta">Last updated: <?= $latestMetric['date'] ?></p>
                <?php else: ?>
                    <p class="card-empty">No body metrics logged yet.</p>
                    <a href="<?= BASE_URL ?>log-weight.php" class="btn btn-secondary btn-small">Log your first entry</a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="card">
            <h2 class="card-title">🏃 Recent Activity</h2>
            <?php if (!empty($recentSessions)): ?>
                <div class="activity-list">
                    <?php foreach ($recentSessions as $session): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <?php
                                $icons = [
                                    'training' => '🥊',
                                    'run' => '🏃',
                                    'workout' => '💪',
                                    'swimming' => '🏊',
                                    'cycling' => '🚴',
                                    'hiking' => '🥾',
                                    'stretching' => '🧘'
                                ];
                                echo $icons[$session['session_type']] ?? '🏋️';
                                ?>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title">
                                    <?= Validator::sanitizeOutput(ucfirst($session['session_type'])) ?>
                                    <?php if ($session['intensity']): ?>
                                        <span class="intensity-badge intensity-<?= $session['intensity'] <= 3 ? 'low' : ($session['intensity'] <= 6 ? 'medium' : 'high') ?>">
                                            <?= $session['intensity'] ?>/10
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="activity-meta">
                                    <span><?= $session['date'] ?></span>
                                    <?php if ($session['duration_minutes']): ?>
                                        <span>• <?= $session['duration_minutes'] ?> min</span>
                                    <?php endif; ?>
                                    <?php if ($session['location']): ?>
                                        <span>• <?= Validator::sanitizeOutput($session['location']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="card-empty">No training sessions logged yet.</p>
                <a href="<?= BASE_URL ?>log-session.php" class="btn btn-secondary btn-small">Log your first session</a>
            <?php endif; ?>
        </div>
    </main>
    
    <script src="<?= BASE_URL ?>assets/js/app.js"></script>
</body>
</html>
