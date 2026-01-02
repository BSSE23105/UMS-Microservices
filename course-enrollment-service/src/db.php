<?php
declare(strict_types=1);

// Remove mysqli_report(...) to avoid undefined function

$DB_HOST = $_ENV['DB_HOST'] ?? 'mysql';
$DB_NAME = $_ENV['DB_NAME'] ?? 'ums';
$DB_USER = $_ENV['DB_USER'] ?? 'umsuser';
$DB_PASS = $_ENV['DB_PASS'] ?? 'umspassword';
$DB_PORT = (int)($_ENV['DB_PORT'] ?? 3306);

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');
