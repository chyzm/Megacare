<?php
require_once __DIR__ . '/includes/bootstrap.php';

$count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($count > 0) { header('Location: dashboard.php'); exit; }

$err = '';$msg='';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) {
        $err = 'Invalid CSRF token';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';
        if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($password) >= 10 && $password === $confirm) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
            $stmt->execute([$name, $email, $hash]);
            log_audit('setup_admin_created', null, json_encode(['email'=>$email]));
            $msg = 'Admin created. You can now log in.';
        } else {
            $err = 'Provide valid name, email, and strong matching passwords (min 10 chars).';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Initial Setup</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-6">
        <div class="card">
          <div class="card-body">
            <h4>Initial Admin Setup</h4>
            <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
            <?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>
            <form method="post">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
              <div class="mb-3"><label class="form-label">Full Name</label><input class="form-control" name="name" required></div>
              <div class="mb-3"><label class="form-label">Email</label><input class="form-control" type="email" name="email" required></div>
              <div class="mb-3"><label class="form-label">Password</label><input class="form-control" type="password" name="password" minlength="10" required></div>
              <div class="mb-3"><label class="form-label">Confirm Password</label><input class="form-control" type="password" name="confirm" minlength="10" required></div>
              <button class="btn btn-primary" type="submit">Create Admin</button>
              <a class="btn btn-link" href="login.php">Back to Login</a>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
