<?php
// Set UTF-8 encoding
if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
}

// Read from environment variables (Docker) or use defaults (Local)
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';
$db   = getenv('DB_NAME') ?: 'shoestore';

// Create connection with explicit UTF-8 charset
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Force UTF-8 immediately after connection
$conn->set_charset("utf8mb4");
$conn->query("SET NAMES utf8mb4");
$conn->query("SET CHARACTER SET utf8mb4");
$conn->query("SET SESSION collation_connection = 'utf8mb4_unicode_ci'");

// Verify connection charset
$charset_check = $conn->query("SELECT @@character_set_connection");
$row = $charset_check->fetch_assoc();
if ($row['@@character_set_connection'] !== 'utf8mb4') {
    error_log("WARNING: Connection charset is " . $row['@@character_set_connection'] . ", expected utf8mb4");
}
