<?php
/**
 * AJAX API — Returns notification badge counts as JSON
 * Used by sidebar to show real-time pending items
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'unauthorized']);
    exit();
}

$role = $_SESSION['role'];
$uid  = $_SESSION['user_id'];

$badges = [];

if ($role === 'admin') {
    $badges['complaints'] = (int) $pdo->query("SELECT COUNT(*) FROM complaints WHERE status = 'pending'")->fetchColumn();
    $badges['fyp']        = (int) $pdo->query("SELECT COUNT(*) FROM fyp_groups WHERE status = 'pending'")->fetchColumn();
    $badges['events']     = (int) $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'upcoming'")->fetchColumn();
} else {
    // Count recent announcements from last 7 days
    $badges['announcements'] = (int) $pdo->query("SELECT COUNT(*) FROM announcements WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
    // Count complaints with updates
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM complaints WHERE student_id = ? AND status IN ('in_progress','resolved')");
    $stmt->execute([$uid]);
    $badges['complaints'] = (int) $stmt->fetchColumn();
}

echo json_encode($badges);
