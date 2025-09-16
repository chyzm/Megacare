<?php
// Authentication helpers

// Secure session cookie params
if (session_status() === PHP_SESSION_NONE) {
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

function is_logged_in(): bool {
    return isset($_SESSION['user']) && isset($_SESSION['user']['id']);
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

function require_login() {
    // Enforce inactivity timeout (30 minutes) and IP pinning
    if (is_logged_in()) {
        $now = time();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $last = $_SESSION['last_activity'] ?? 0;
        $boundIp = $_SESSION['ip'] ?? '';
        // If last activity older than 30 min, expire
        if ($last && ($now - (int)$last) > 30 * 60) {
            session_unset();
            session_destroy();
            $next = urlencode($_SERVER['REQUEST_URI'] ?? '/');
            header("Location: /megacare_phamacy/login.php?expired=1&next={$next}");
            exit;
        }
        // If IP changed, force re-auth
        if ($boundIp && $boundIp !== $ip) {
            session_unset();
            session_destroy();
            $next = urlencode($_SERVER['REQUEST_URI'] ?? '/');
            header("Location: /megacare_phamacy/login.php?next={$next}");
            exit;
        }
        // Refresh activity timestamp
        $_SESSION['last_activity'] = $now;
    }
    if (!is_logged_in()) {
        $next = urlencode($_SERVER['REQUEST_URI'] ?? '/');
    header("Location: /megacare_phamacy/login.php?next={$next}");
        exit;
    }
}

function require_admin() {
    require_login();
    if (($_SESSION['user']['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo 'Forbidden: Admins only';
        exit;
    }
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf($token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

?>
