<?php

$env_file = __DIR__ . '/.env';
if (!file_exists($env_file)) {
    $env_file = __DIR__ . '/../.env';
}

if (file_exists($env_file)) {
    $env = parse_ini_file($env_file);
    $DB_HOST = $env['DB_HOST'] ?? 'localhost';
    $DB_NAME = $env['DB_NAME'] ?? 'tnb_meter_app';
    $DB_USER = $env['DB_USER'] ?? 'root';
    $DB_PASS = $env['DB_PASS'] ?? '';
} else {
    die('Error: .env file not found. Please create a .env file in the project root.');
}

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage() .
        '<br>Make sure your .env credentials are correct and XAMPP MySQL is running.');
}