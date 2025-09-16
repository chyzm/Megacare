<?php
require_once __DIR__ . '/includes/bootstrap.php';

$error = '';
// Optional: show a friendly message if session expired
$expired = isset($_GET['expired']) ? 'Your session expired due to inactivity. Please sign in again.' : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verify_csrf($_POST['csrf'] ?? '')) {
        $error = 'Invalid CSRF token';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
    $auth = authenticate($pdo, $email, $password);
    if ($auth === true) {
            $next = $_GET['next'] ?? 'dashboard.php';
            header('Location: ' . $next);
            exit;
    } elseif ($auth === 'rate_limited') {
      $error = 'Too many failed attempts. Try again in a few minutes.';
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - MegaCare Pharmacy</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style> body{background:#f5f7fb;} .card{box-shadow:0 2px 10px rgba(0,0,0,.08);} </style>
  </head>
<body>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-md-5">
        <div class="card">
          <div class="card-body p-4">
            <div class="text-center mb-3">
              <a href="index.php" aria-label="Back to registration">
                <img src="assets/img/logo.png" alt="MegaCare Pharmacy" width="100" height="50">
              </a>
            </div>
            <h4 class="mb-3">Sign in</h4>
            <?php if ($expired): ?><div class="alert alert-warning"><?= htmlspecialchars($expired) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="post" autocomplete="off">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input class="form-control" type="email" name="email" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Password</label>
                <input class="form-control" type="password" name="password" required>
              </div>
              <button class="btn btn-primary w-100" type="submit">Login</button>
              <div class="mt-2">
                <span class="form-text">Password reset is handled by an administrator.</span>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
