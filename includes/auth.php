<?php
// =============================================
// Authentication Helpers
// =============================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin() {
    if (!isLoggedIn() || !isAdmin()) {
        header('Location: ../login.php');
        exit;
    }
}

function currentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function currentUserName() {
    return $_SESSION['full_name'] ?? 'User';
}

function flash($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function showFlash() {
    if (!isset($_SESSION['flash'])) return;
    $f = $_SESSION['flash'];
    $cls = $f['type'] === 'success' ? 'alert-success' : 'alert-danger';
    echo '<div class="alert ' . $cls . ' alert-dismissible fade show" role="alert">'
       . htmlspecialchars($f['msg'])
       . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    unset($_SESSION['flash']);
}
