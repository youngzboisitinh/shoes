<?php
// Set UTF-8 encoding
header('Content-Type: text/html; charset=utf-8');

// Read from environment variables (Docker) or use defaults (Local)
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASSWORD') ?: '';
$db   = getenv('DB_NAME') ?: 'shoestore';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Set MySQL connection charset to utf8mb4
$conn->set_charset("utf8mb4");
