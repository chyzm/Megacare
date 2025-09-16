<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

// --- Migrations ---
// Users table (with soft-delete and timestamps)
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    deleted_by INT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Ensure soft-delete columns exist on legacy installs
try {
        $colCheck = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = ?");
        $ensureCol = function($name, $ddl) use ($pdo, $colCheck) {
                $colCheck->execute([$name]);
                if ((int)$colCheck->fetchColumn() === 0) {
                        $pdo->exec("ALTER TABLE users ADD COLUMN " . $ddl);
                }
        };
        $ensureCol('deleted_at', "deleted_at TIMESTAMP NULL DEFAULT NULL");
        $ensureCol('deleted_by', "deleted_by INT NULL");
} catch (Throwable $e) {
        // Swallow to avoid breaking app; will surface if queries reference missing columns
}

// Audit logs
$pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    target_user_id INT NULL,
    details TEXT NULL,
    ip VARCHAR(64) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Login attempts (rate limiting)
$pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NULL,
    ip VARCHAR(64) NOT NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(email), INDEX(ip), INDEX(created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Password resets
$pdo->exec("CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Helper: log audit
function log_audit($action, $target_user_id = null, $details = null) {
        global $pdo;
        $uid = $_SESSION['user']['id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, target_user_id, details, ip) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$uid, $action, $target_user_id, $details, $ip]);
}

// Rate limiting helpers
function record_login_attempt($email, $success) {
        global $pdo;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt = $pdo->prepare("INSERT INTO login_attempts (email, ip, success) VALUES (?, ?, ?)");
        $stmt->execute([$email, $ip, $success ? 1 : 0]);
}

function is_rate_limited($email): bool {
        global $pdo;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        // Limit: 5 failed attempts per 15 minutes per email or IP
        $window = 15; // minutes
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE created_at >= (NOW() - INTERVAL {$window} MINUTE) AND success = 0 AND (email = ? OR ip = ?)");
        $stmt->execute([$email, $ip]);
        $failed = (int)$stmt->fetchColumn();
        return $failed >= 5;
}

// Authentication
function authenticate($pdo, $email, $password) {
        if (is_rate_limited($email)) {
                return 'rate_limited';
        }
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password_hash'])) {
                unset($user['password_hash']);
                $_SESSION['user'] = $user;
                $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
                $_SESSION['last_activity'] = time();
                record_login_attempt($email, true);
                log_audit('login_success', $user['id']);
                return true;
        }
        record_login_attempt($email, false);
        log_audit('login_failure', null, json_encode(['email' => $email]));
        return false;
}

// First-run wizard: redirect to setup if no users exist
$count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$script = basename($_SERVER['SCRIPT_NAME'] ?? '');
if ($count === 0 && !in_array($script, ['setup.php','password_reset_request.php','reset_password.php'])) {
        header('Location: /megacare_phamacy/setup.php');
        exit;
}

?>