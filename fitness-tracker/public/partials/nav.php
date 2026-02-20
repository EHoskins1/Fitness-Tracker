<?php
/**
 * Navigation Partial
 */

use App\Utils\Session;

$currentPage = basename($_SERVER['PHP_SELF']);
$username = Session::get('username', 'User');
?>
<nav class="navbar">
    <div class="navbar-brand">
        <a href="<?= BASE_URL ?>dashboard.php">🥊 <?= APP_NAME ?></a>
    </div>
    
    <div class="navbar-menu">
        <a href="<?= BASE_URL ?>dashboard.php" class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
            📊 Dashboard
        </a>
        <a href="<?= BASE_URL ?>calendar.php" class="nav-link <?= $currentPage === 'calendar.php' ? 'active' : '' ?>">
            📅 Calendar
        </a>
        <a href="<?= BASE_URL ?>progress.php" class="nav-link <?= $currentPage === 'progress.php' ? 'active' : '' ?>">
            📈 Progress
        </a>
    </div>
    
    <div class="navbar-actions">
        <div class="dropdown">
            <button class="btn btn-ghost dropdown-toggle">
                ➕ Log
            </button>
            <div class="dropdown-menu">
                <a href="<?= BASE_URL ?>log-session.php" class="dropdown-item">📝 Training Session</a>
                <a href="<?= BASE_URL ?>log-weight.php" class="dropdown-item">⚖️ Body Metrics</a>
            </div>
        </div>
        
        <div class="dropdown">
            <button class="btn btn-ghost dropdown-toggle">
                👤 <?= htmlspecialchars($username) ?>
            </button>
            <div class="dropdown-menu dropdown-menu-right">
                <a href="<?= BASE_URL ?>logout.php" class="dropdown-item">🚪 Logout</a>
            </div>
        </div>
    </div>
    
    <button class="navbar-toggle" id="navbarToggle">☰</button>
</nav>
