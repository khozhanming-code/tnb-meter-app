<?php
require_once __DIR__ . '/includes/auth.php';

if (!is_logged_in()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

if ($_SESSION['user_role'] === 'admin') {
    header('Location: ' . BASE_URL . '/dashboard.php');
} else {
    header('Location: ' . BASE_URL . '/submit_reading.php');
}
exit;
