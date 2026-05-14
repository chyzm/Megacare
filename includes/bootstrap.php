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

// Vaccination tracking. Keep this migration here so authenticated workflows
// share the same schema setup instead of relying on one page to add columns.
try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS vaccination_status (
            id INT AUTO_INCREMENT PRIMARY KEY,
            client_id VARCHAR(64) NOT NULL,
            vaccination_type VARCHAR(100) NULL,
            first_dose TINYINT(1) NOT NULL DEFAULT 0,
            first_dose_date_taken DATE NULL,
            first_dose_next_date DATE NULL,
            second_dose TINYINT(1) NOT NULL DEFAULT 0,
            second_dose_date_taken DATE NULL,
            second_dose_next_date DATE NULL,
            final_dose TINYINT(1) NOT NULL DEFAULT 0,
            final_dose_date_taken DATE NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_vaccination_client (client_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $colCheck = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vaccination_status' AND COLUMN_NAME = ?");
        $ensureVaccCol = function($name, $ddl) use ($pdo, $colCheck) {
                $colCheck->execute([$name]);
                if ((int)$colCheck->fetchColumn() === 0) {
                        $pdo->exec("ALTER TABLE vaccination_status ADD COLUMN " . $ddl);
                }
        };
        $ensureVaccCol('vaccination_type', "vaccination_type VARCHAR(100) NULL AFTER client_id");
        $ensureVaccCol('first_dose_date_taken', "first_dose_date_taken DATE NULL AFTER first_dose");
        $ensureVaccCol('first_dose_next_date', "first_dose_next_date DATE NULL AFTER first_dose_date_taken");
        $ensureVaccCol('second_dose_date_taken', "second_dose_date_taken DATE NULL AFTER second_dose");
        $ensureVaccCol('second_dose_next_date', "second_dose_next_date DATE NULL AFTER second_dose_date_taken");
        $ensureVaccCol('final_dose_date_taken', "final_dose_date_taken DATE NULL AFTER final_dose");

        $clientType = $pdo->prepare("SELECT DATA_TYPE, CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vaccination_status' AND COLUMN_NAME = 'client_id'");
        $clientType->execute();
        $clientMeta = $clientType->fetch(PDO::FETCH_ASSOC);
        if ($clientMeta && (strtolower($clientMeta['DATA_TYPE']) !== 'varchar' || (int)$clientMeta['CHARACTER_MAXIMUM_LENGTH'] < 64)) {
                $pdo->exec("ALTER TABLE vaccination_status MODIFY client_id VARCHAR(64) NOT NULL");
        }

        $pdo->exec("ALTER TABLE vaccination_status
            MODIFY first_dose TINYINT(1) NOT NULL DEFAULT 0,
            MODIFY second_dose TINYINT(1) NOT NULL DEFAULT 0,
            MODIFY final_dose TINYINT(1) NOT NULL DEFAULT 0");

        $idxStmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vaccination_status' AND INDEX_NAME = 'uniq_vacc_client'");
        $idxStmt->execute();
        if ((int)$idxStmt->fetchColumn() === 0) {
                $pdo->exec("DELETE v1 FROM vaccination_status v1 JOIN vaccination_status v2 ON v1.client_id = v2.client_id AND v1.id < v2.id");
                $pdo->exec("ALTER TABLE vaccination_status ADD UNIQUE KEY uniq_vacc_client (client_id)");
        }
} catch (Throwable $e) {
        error_log('Vaccination migration failed: ' . $e->getMessage());
}

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
