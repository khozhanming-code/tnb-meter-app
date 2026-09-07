<?php
// ==========================================================
// TNB Electricity Meter Monitoring App - Global Config
// ==========================================================

// Malaysia timezone - every date()/time() call in the app uses this
date_default_timezone_set('Asia/Kuala_Lumpur');

// Adjust BASE_URL if your XAMPP folder name is different
define('BASE_URL', 'http://localhost/tnb-meter-app');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', BASE_URL . '/uploads/');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
