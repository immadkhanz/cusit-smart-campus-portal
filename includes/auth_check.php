<?php
/**
 * Auth Guard — include at the top of every protected page
 * Features: Session start, timeout (30 min), CSRF, flash messages
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// ── Session Timeout (30 minutes) ──
$SESSION_TIMEOUT = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $SESSION_TIMEOUT) {
    session_unset();
    session_destroy();
    header('Location: /' . basename(dirname(__DIR__)) . '/login.php?timeout=1');
    exit();
}
$_SESSION['last_activity'] = time();

// ── Auth Check ──
if (!isset($_SESSION['user_id'])) {
    header('Location: /' . basename(dirname(__DIR__)) . '/login.php');
    exit();
}

// ── Role Helpers ──
function isAdmin()   { return ($_SESSION['role'] ?? '') === 'admin'; }
function isStudent() { return ($_SESSION['role'] ?? '') === 'student'; }

function requireAdmin() {
    if (!isAdmin()) { header('Location: /' . basename(dirname(__DIR__)) . '/login.php'); exit(); }
}
function requireStudent() {
    if (!isStudent()) { header('Location: /' . basename(dirname(__DIR__)) . '/login.php'); exit(); }
}

// ── CSRF Token Helpers ──
function generateCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRF() . '">';
}

function verifyCSRF() {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        http_response_code(403);
        die('Invalid request. Please go back and try again.');
    }
}

// ── Flash Message Helpers ──
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function renderFlash() {
    $flash = getFlash();
    if ($flash) {
        $bg    = $flash['type'] === 'success' ? 'rgba(16,185,129,0.1)' : 'rgba(239,68,68,0.1)';
        $color = $flash['type'] === 'success' ? '#6ee7b7' : '#fca5a5';
        $border = $flash['type'] === 'success' ? 'rgba(16,185,129,0.2)' : 'rgba(239,68,68,0.2)';
        echo '<div style="background:'.$bg.'; color:'.$color.'; padding:1rem; border-radius:12px; margin-bottom:1.5rem; border:1px solid '.$border.'; font-size:0.9rem; text-align:center;">' . htmlspecialchars($flash['message']) . '</div>';
    }
}
