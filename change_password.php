<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();
$user = current_user();
$msg = '';$err='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!verify_csrf($_POST['csrf'] ?? '')) { $err='Invalid CSRF token'; }
  else {
    $current = $_POST['current'] ?? '';
    $new = $_POST['new'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    if (strlen($new) < 10 || $new !== $confirm) { $err='Password must be at least 10 characters and match confirmation.'; }
    else {
      $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id=?');
      $stmt->execute([$user['id']]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$row || !password_verify($current, $row['password_hash'])) { $err='Current password is incorrect.'; }
      else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $upd = $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?');
        $upd->execute([$hash, $user['id']]);
        log_audit('password_changed', $user['id']);
        $msg='Password changed successfully.';
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Change Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="container py-4">
    <h3>Change Password</h3>
    <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>
    <form method="post" class="row g-3">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <div class="col-md-4"><label class="form-label">Current Password</label><input class="form-control" type="password" name="current" required></div>
      <div class="col-md-4"><label class="form-label">New Password</label><input class="form-control" type="password" name="new" minlength="10" required></div>
      <div class="col-md-4"><label class="form-label">Confirm New Password</label><input class="form-control" type="password" name="confirm" minlength="10" required></div>
      <div class="col-12"><button class="btn btn-primary" type="submit">Update Password</button> <a class="btn btn-secondary" href="dashboard.php">Back</a></div>
    </form>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
