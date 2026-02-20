<?php
/**
 * Calendar Page
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

// Get month/year from query params or use current
$year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
$month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('m');

// Validate month/year
if ($month < 1) { $month = 12; $year--; }
if ($month > 12) { $month = 1; $year++; }
if ($year < 2000) $year = 2000;
if ($year > 2100) $year = 2100;

// Calculate calendar data
$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDayOfMonth);
$dayOfWeekFirst = date('w', $firstDayOfMonth); // 0 = Sunday
$monthName = date('F', $firstDayOfMonth);

// Get date range for the month
$startDate = date('Y-m-d', $firstDayOfMonth);
$endDate = date('Y-m-t', $firstDayOfMonth);

// Get sessions for the month
$sessionModel = new TrainingSession();
$sessions = $sessionModel->getByDateRange($userId, $startDate, $endDate);

// Get body metrics for the month
$bodyMetricModel = new BodyMetric();
$metrics = $bodyMetricModel->getByDateRange($userId, $startDate, $endDate);

// Group by date
$sessionsByDate = [];
foreach ($sessions as $session) {
    $date = $session['date'];
    if (!isset($sessionsByDate[$date])) {
        $sessionsByDate[$date] = [];
    }
    $sessionsByDate[$date][] = $session;
}

$metricsByDate = [];
foreach ($metrics as $metric) {
    $metricsByDate[$metric['date']] = $metric;
}

// Navigation URLs
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }
$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

$pageTitle = 'Calendar';

// Session type icons
$sessionIcons = [
    'training' => '🥊',
    'run' => '🏃',
    'workout' => '💪',
    'swimming' => '🏊',
    'cycling' => '🚴',
    'hiking' => '🥾',
    'stretching' => '🧘'
];
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
        <div class="page-header calendar-header">
            <h1>📅 <?= $monthName ?> <?= $year ?></h1>
            <div class="calendar-nav">
                <a href="?year=<?= $prevYear ?>&month=<?= $prevMonth ?>" class="btn btn-secondary">← Prev</a>
                <a href="?year=<?= date('Y') ?>&month=<?= date('m') ?>" class="btn btn-ghost">Today</a>
                <a href="?year=<?= $nextYear ?>&month=<?= $nextMonth ?>" class="btn btn-secondary">Next →</a>
            </div>
        </div>
        
        <div class="card">
            <div class="calendar">
                <!-- Day headers -->
                <div class="calendar-header-row">
                    <div class="calendar-day-header">Sun</div>
                    <div class="calendar-day-header">Mon</div>
                    <div class="calendar-day-header">Tue</div>
                    <div class="calendar-day-header">Wed</div>
                    <div class="calendar-day-header">Thu</div>
                    <div class="calendar-day-header">Fri</div>
                    <div class="calendar-day-header">Sat</div>
                </div>
                
                <!-- Calendar grid -->
                <div class="calendar-grid">
                    <?php
                    // Empty cells before first day
                    for ($i = 0; $i < $dayOfWeekFirst; $i++):
                    ?>
                        <div class="calendar-cell calendar-cell-empty"></div>
                    <?php endfor; ?>
                    
                    <?php
                    // Days of the month
                    for ($day = 1; $day <= $daysInMonth; $day++):
                        $currentDate = sprintf('%04d-%02d-%02d', $year, $month, $day);
                        $isToday = $currentDate === date('Y-m-d');
                        $daySessions = $sessionsByDate[$currentDate] ?? [];
                        $dayMetric = $metricsByDate[$currentDate] ?? null;
                        $hasEntries = !empty($daySessions) || $dayMetric;
                    ?>
                        <div class="calendar-cell <?= $isToday ? 'calendar-cell-today' : '' ?> <?= $hasEntries ? 'calendar-cell-has-entries' : '' ?>">
                            <div class="calendar-day-number"><?= $day ?></div>
                            
                            <?php if ($hasEntries): ?>
                                <div class="calendar-entries">
                                    <?php foreach ($daySessions as $session): ?>
                                        <div class="calendar-entry calendar-entry-session" title="<?= Validator::sanitizeOutput($session['session_type']) ?>">
                                            <?= $sessionIcons[$session['session_type']] ?? '🏋️' ?>
                                        </div>
                                    <?php endforeach; ?>
                                    
                                    <?php if ($dayMetric): ?>
                                        <div class="calendar-entry calendar-entry-metric" title="Weight: <?= $dayMetric['weight_kg'] ?? '-' ?> kg">
                                            ⚖️
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                    
                    <?php
                    // Empty cells after last day
                    $totalCells = $dayOfWeekFirst + $daysInMonth;
                    $remainingCells = (7 - ($totalCells % 7)) % 7;
                    for ($i = 0; $i < $remainingCells; $i++):
                    ?>
                        <div class="calendar-cell calendar-cell-empty"></div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
        
        <!-- Legend -->
        <div class="calendar-legend">
            <span class="legend-item"><span class="legend-icon">🥊</span> Training</span>
            <span class="legend-item"><span class="legend-icon">🏃</span> Run</span>
            <span class="legend-item"><span class="legend-icon">💪</span> Workout</span>
            <span class="legend-item"><span class="legend-icon">⚖️</span> Weigh-in</span>
        </div>
        
        <!-- Monthly Summary -->
        <div class="card">
            <h2 class="card-title">Monthly Summary</h2>
            <div class="stats-grid">
                <div class="stat">
                    <span class="stat-value"><?= count($sessions) ?></span>
                    <span class="stat-label">Sessions</span>
                </div>
                <div class="stat">
                    <span class="stat-value">
                        <?php
                        $totalDuration = array_sum(array_column($sessions, 'duration_minutes'));
                        $hours = floor($totalDuration / 60);
                        $mins = $totalDuration % 60;
                        echo $hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m";
                        ?>
                    </span>
                    <span class="stat-label">Total Time</span>
                </div>
                <div class="stat">
                    <span class="stat-value"><?= count($metrics) ?></span>
                    <span class="stat-label">Weigh-ins</span>
                </div>
                <div class="stat">
                    <?php
                    $activeDays = count($sessionsByDate);
                    ?>
                    <span class="stat-value"><?= $activeDays ?></span>
                    <span class="stat-label">Active Days</span>
                </div>
            </div>
        </div>
    </main>
    
    <script src="<?= BASE_URL ?>assets/js/app.js"></script>
</body>
</html>
