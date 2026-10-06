<?php
// Start Output Buffering to prevent "headers already sent" errors on redirects
if (ob_get_level() === 0) {
    ob_start();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Configuration
$host     = 'localhost';
$dbname   = 'rtrw_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die("Koneksi Database Gagal: " . $e->getMessage());
}

// Load auth helpers so require_login() and require_admin() are always available
require_once __DIR__ . '/../includes/auth.php';
