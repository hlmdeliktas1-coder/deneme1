<?php
/**
 * src/auth.php
 * Simple auth helpers.
 */

require_once __DIR__ . '/config.php';

function ensure_session(): void {
    $cfg = getConfig();
    $name = $cfg['app']['session_name'] ?? 'app_sess';
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_name($name);
        session_start();
    }
}

function require_login(): void {
    ensure_session();
    if (empty($_SESSION['user_id'])) {
        $base = getConfig()['app']['base_path'] ?? '/';
        // assume login is at public/login.php relative to project root
        header('Location: ' . $base . 'public/login.php');
        exit;
    }
}

function current_user_id(): ?int {
    ensure_session();
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}
