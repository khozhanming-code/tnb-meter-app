<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
$user = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($page_title) ? h($page_title) . ' - ' : '' ?>TNB Meter Monitoring</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<?php if ($user): ?>
<nav class="navbar">
    <div class="nav-brand">⚡ TNB Meter Monitoring</div>
    <div class="nav-links">
        <?php if ($user['role'] === 'admin'): ?>
            <a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a>
            <a href="<?= BASE_URL ?>/meters.php">Meters</a>
            <a href="<?= BASE_URL ?>/users.php">Users</a>
            <a href="<?= BASE_URL ?>/readings.php">Readings</a>
            <a href="<?= BASE_URL ?>/settings.php">Settings</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/submit_reading.php">Submit Reading</a>
            <a href="<?= BASE_URL ?>/readings.php">My Readings</a>
        <?php endif; ?>
        <span class="nav-user"><?= h($user['name']) ?> (<?= h($user['role']) ?>)</span>
        <a href="<?= BASE_URL ?>/logout.php">Logout</a>
    </div>
</nav>
<?php endif; ?>
<main class="container">
