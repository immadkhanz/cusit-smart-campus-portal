<?php
/**
 * Database Configuration — CUSIT Smart Campus Portal
 */
$host     = 'localhost';
$dbname   = 'cusit_portal';
$username = 'root';
$password = '';

// Dynamically determine the base URL based on folder name
define('BASE_URL', '/' . basename(dirname(__DIR__)) . '/');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
