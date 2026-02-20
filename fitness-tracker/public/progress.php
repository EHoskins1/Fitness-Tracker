<?php
/**
 * Progress Page
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

// Get data for charts
$sessionModel = new TrainingSession();
$bodyMetricModel = new BodyMetric();

// Weight history (last 90 days)
$weightHistory = $bodyMetricModel->getWeightHistory($userId, 90);

// Body fat history (last 90 days)
$bodyFatHistory = $bodyMetricModel->getBodyFatHistory($userId, 90);

// Monthly training volume (last 12 months)
$monthlyVolume = $sessionModel->getMonthlyVolume($userId, 12);

// Recent sessions for stats
$recentSessions = $sessionModel->getByUser($userId, 100);

// Calculate some stats
$totalSessions = count($recentSessions);
$totalDuration = array_sum(array_column($recentSessions, 'duration_minutes'));
$avgDuration = $totalSessions > 0 ? round($totalDuration / $totalSessions) : 0;

// Session type breakdown
$typeBreakdown = [];
foreach ($recentSessions as $session) {
    $type = $session['session_type'];
    if (!isset($typeBreakdown[$type])) {
        $typeBreakdown[$type] = 0;
    }
    $typeBreakdown[$type]++;
}
arsort($typeBreakdown);

// Format data for charts
$weightChartData = json_encode([
    'labels' => array_column($weightHistory, 'date'),
    'values' => array_column($weightHistory, 'weight_kg')
]);

$volumeChartData = json_encode([
    'labels' => array_column($monthlyVolume, 'month'),
    'sessions' => array_column($monthlyVolume, 'session_count'),
    'duration' => array_column($monthlyVolume, 'total_duration')
]);

$pageTitle = 'Progress';
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
            <h1>📈 Progress</h1>
        </div>
        
        <!-- Overall Stats -->
        <div class="card">
            <h2 class="card-title">Overall Statistics</h2>
            <div class="stats-grid stats-grid-4">
                <div class="stat">
                    <span class="stat-value"><?= $totalSessions ?></span>
                    <span class="stat-label">Total Sessions</span>
                </div>
                <div class="stat">
                    <span class="stat-value">
                        <?php 
                        $hours = floor($totalDuration / 60);
                        echo $hours;
                        ?>h
                    </span>
                    <span class="stat-label">Total Hours</span>
                </div>
                <div class="stat">
                    <span class="stat-value"><?= $avgDuration ?> min</span>
                    <span class="stat-label">Avg Duration</span>
                </div>
                <div class="stat">
                    <span class="stat-value"><?= count($weightHistory) ?></span>
                    <span class="stat-label">Weigh-ins</span>
                </div>
            </div>
        </div>
        
        <!-- Weight Trend -->
        <div class="card">
            <h2 class="card-title">⚖️ Weight Trend (Last 90 Days)</h2>
            <?php if (!empty($weightHistory)): ?>
                <div class="chart-container">
                    <canvas id="weightChart"></canvas>
                </div>
                <div class="chart-summary">
                    <?php
                    $firstWeight = reset($weightHistory)['weight_kg'];
                    $lastWeight = end($weightHistory)['weight_kg'];
                    $weightDiff = $lastWeight - $firstWeight;
                    ?>
                    <p>
                        Started at <strong><?= number_format($firstWeight, 1) ?> kg</strong>, 
                        currently <strong><?= number_format($lastWeight, 1) ?> kg</strong>
                        (<span class="<?= $weightDiff < 0 ? 'text-success' : ($weightDiff > 0 ? 'text-warning' : '') ?>">
                            <?= $weightDiff > 0 ? '+' : '' ?><?= number_format($weightDiff, 1) ?> kg
                        </span>)
                    </p>
                </div>
            <?php else: ?>
                <p class="card-empty">No weight data yet. Start logging to see your trend!</p>
            <?php endif; ?>
        </div>
        
        <!-- Monthly Volume -->
        <div class="card">
            <h2 class="card-title">📊 Monthly Training Volume</h2>
            <?php if (!empty($monthlyVolume)): ?>
                <div class="chart-container">
                    <canvas id="volumeChart"></canvas>
                </div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Sessions</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($monthlyVolume) as $month): ?>
                            <tr>
                                <td><?= $month['month'] ?></td>
                                <td><?= $month['session_count'] ?></td>
                                <td>
                                    <?php 
                                    $h = floor($month['total_duration'] / 60);
                                    $m = $month['total_duration'] % 60;
                                    echo $h > 0 ? "{$h}h {$m}m" : "{$m}m";
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="card-empty">No training data yet. Start logging sessions!</p>
            <?php endif; ?>
        </div>
        
        <!-- Session Type Breakdown -->
        <div class="card">
            <h2 class="card-title">🏷️ Session Type Breakdown</h2>
            <?php if (!empty($typeBreakdown)): ?>
                <div class="type-breakdown">
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
                    foreach ($typeBreakdown as $type => $count): 
                        $percentage = round(($count / $totalSessions) * 100);
                    ?>
                        <div class="type-item">
                            <div class="type-info">
                                <span class="type-icon"><?= $icons[$type] ?? '🏋️' ?></span>
                                <span class="type-name"><?= Validator::sanitizeOutput(ucfirst($type)) ?></span>
                                <span class="type-count"><?= $count ?> sessions</span>
                            </div>
                            <div class="type-bar">
                                <div class="type-bar-fill" style="width: <?= $percentage ?>%"></div>
                            </div>
                            <span class="type-percent"><?= $percentage ?>%</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="card-empty">No sessions logged yet.</p>
            <?php endif; ?>
        </div>
    </main>
    
    <script src="<?= BASE_URL ?>assets/js/app.js"></script>
    <script>
        // Chart data
        const weightData = <?= $weightChartData ?>;
        const volumeData = <?= $volumeChartData ?>;
        
        // Simple chart rendering (using canvas)
        document.addEventListener('DOMContentLoaded', function() {
            if (weightData.values.length > 0) {
                drawLineChart('weightChart', weightData.labels, weightData.values, 'Weight (kg)');
            }
            
            if (volumeData.sessions.length > 0) {
                drawBarChart('volumeChart', volumeData.labels, volumeData.sessions, 'Sessions');
            }
        });
        
        function drawLineChart(canvasId, labels, values, label) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            const padding = 40;
            const width = canvas.width = canvas.parentElement.offsetWidth;
            const height = canvas.height = 200;
            
            const chartWidth = width - padding * 2;
            const chartHeight = height - padding * 2;
            
            const minVal = Math.min(...values) - 1;
            const maxVal = Math.max(...values) + 1;
            const range = maxVal - minVal;
            
            // Clear
            ctx.fillStyle = '#1a1d24';
            ctx.fillRect(0, 0, width, height);
            
            // Grid lines
            ctx.strokeStyle = '#2a2f3a';
            ctx.lineWidth = 1;
            for (let i = 0; i <= 4; i++) {
                const y = padding + (chartHeight / 4) * i;
                ctx.beginPath();
                ctx.moveTo(padding, y);
                ctx.lineTo(width - padding, y);
                ctx.stroke();
                
                // Y-axis labels
                const val = maxVal - (range / 4) * i;
                ctx.fillStyle = '#8b9298';
                ctx.font = '12px sans-serif';
                ctx.textAlign = 'right';
                ctx.fillText(val.toFixed(1), padding - 5, y + 4);
            }
            
            // Draw line
            ctx.strokeStyle = '#4f9eff';
            ctx.lineWidth = 2;
            ctx.beginPath();
            
            values.forEach((val, i) => {
                const x = padding + (chartWidth / (values.length - 1)) * i;
                const y = padding + chartHeight - ((val - minVal) / range) * chartHeight;
                
                if (i === 0) {
                    ctx.moveTo(x, y);
                } else {
                    ctx.lineTo(x, y);
                }
            });
            
            ctx.stroke();
            
            // Draw points
            ctx.fillStyle = '#4f9eff';
            values.forEach((val, i) => {
                const x = padding + (chartWidth / (values.length - 1)) * i;
                const y = padding + chartHeight - ((val - minVal) / range) * chartHeight;
                
                ctx.beginPath();
                ctx.arc(x, y, 4, 0, Math.PI * 2);
                ctx.fill();
            });
        }
        
        function drawBarChart(canvasId, labels, values, label) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            const padding = 40;
            const width = canvas.width = canvas.parentElement.offsetWidth;
            const height = canvas.height = 200;
            
            const chartWidth = width - padding * 2;
            const chartHeight = height - padding * 2;
            
            const maxVal = Math.max(...values) * 1.2;
            const barWidth = chartWidth / values.length * 0.7;
            const barGap = chartWidth / values.length * 0.3;
            
            // Clear
            ctx.fillStyle = '#1a1d24';
            ctx.fillRect(0, 0, width, height);
            
            // Draw bars
            ctx.fillStyle = '#22c55e';
            values.forEach((val, i) => {
                const x = padding + (chartWidth / values.length) * i + barGap / 2;
                const barHeight = (val / maxVal) * chartHeight;
                const y = padding + chartHeight - barHeight;
                
                ctx.fillRect(x, y, barWidth, barHeight);
                
                // Value label
                ctx.fillStyle = '#e4e7eb';
                ctx.font = '12px sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText(val, x + barWidth / 2, y - 5);
                
                // X-axis label
                ctx.fillStyle = '#8b9298';
                ctx.fillText(labels[i].substring(5), x + barWidth / 2, height - 10);
                
                ctx.fillStyle = '#22c55e';
            });
        }
    </script>
</body>
</html>
