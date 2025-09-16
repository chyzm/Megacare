<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_admin();

$msg = '';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verify_csrf($_POST['csrf'] ?? '')) {
    $err = 'Invalid CSRF token';
  } else {
    $action = $_POST['action'] ?? 'create';
    if ($action === 'create') {
      $name = trim($_POST['name'] ?? '');
      $email = trim($_POST['email'] ?? '');
      $role = in_array($_POST['role'] ?? 'user', ['admin','user']) ? $_POST['role'] : 'user';
      $password = $_POST['password'] ?? '';
            if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($password) >= 10) {
        try {
          $hash = password_hash($password, PASSWORD_DEFAULT);
          $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
          $stmt->execute([$name, $email, $hash, $role]);
          $newId = (int)$pdo->lastInsertId();
          log_audit('user_created', $newId, json_encode(['email'=>$email,'role'=>$role]));
          $msg = 'User created.';
        } catch (PDOException $e) {
          $err = 'Error: ' . ($e->getCode() == 23000 ? 'Email already exists' : 'Could not create user');
        }
      } else {
        $err = 'Please provide valid name, email, and password (min 8 chars).';
      }
    } elseif ($action === 'edit') {
      $id = (int)($_POST['id'] ?? 0);
      $name = trim($_POST['name'] ?? '');
      $email = trim($_POST['email'] ?? '');
      $role = in_array($_POST['role'] ?? 'user', ['admin','user']) ? $_POST['role'] : 'user';
      if ($id && $name && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        try {
          $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=? AND deleted_at IS NULL");
          $stmt->execute([$name, $email, $role, $id]);
          log_audit('user_updated', $id, json_encode(['name'=>$name,'email'=>$email,'role'=>$role]));
          $msg = 'User updated.';
        } catch (PDOException $e) {
          $err = 'Error: ' . ($e->getCode() == 23000 ? 'Email already exists' : 'Could not update user');
        }
      } else {
        $err = 'Please provide valid name, email.';
      }
    } elseif ($action === 'delete') {
      $id = (int)($_POST['id'] ?? 0);
      $currentId = (int)(current_user()['id'] ?? 0);
      if ($id && $id !== $currentId) {
        $stmt = $pdo->prepare("UPDATE users SET deleted_at=NOW(), deleted_by=? WHERE id=? AND deleted_at IS NULL");
        $stmt->execute([$currentId, $id]);
        log_audit('user_soft_deleted', $id);
        $msg = 'User deleted.';
      } else {
        $err = 'Invalid user or cannot delete yourself.';
      }
    } elseif ($action === 'reset_password') {
      $id = (int)($_POST['id'] ?? 0);
      $new = $_POST['new_password'] ?? '';
      $confirm = $_POST['confirm_password'] ?? '';
      if (!$id) { $err = 'Invalid user.'; }
      elseif (strlen($new) < 10) { $err = 'Password must be at least 10 characters.'; }
      elseif ($new !== $confirm) { $err = 'Passwords do not match.'; }
      else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash=? WHERE id=? AND deleted_at IS NULL");
        $stmt->execute([$hash, $id]);
        log_audit('admin_password_reset', $id);
        $msg = 'Password reset successfully.';
      }
    }
  }
}

// Fallback in case migration hasn't added deleted_at yet
try {
  $users = $pdo->query("SELECT id, name, email, role, created_at FROM users WHERE deleted_at IS NULL ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $users = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Management</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3>User Management</h3>
      <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="dashboard.php"><i class="fas fa-home"></i> Back to Dashboard</a>
        <a class="btn btn-outline-danger btn-sm" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </div>
    </div>
    <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>
    <div class="card mb-4">
      <div class="card-body">
        <h5 class="card-title">Create User</h5>
        <form method="post" class="row g-3">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
          <input type="hidden" name="action" value="create">
          <div class="col-md-4">
            <label class="form-label">Name</label>
            <input class="form-control" name="name" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Email</label>
            <input class="form-control" type="email" name="email" required>
          </div>
          <div class="col-md-2">
            <label class="form-label">Role</label>
            <select class="form-select" name="role">
              <option value="user">User</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Password</label>
             <input class="form-control" type="password" name="password" minlength="10" required>
          </div>
          <div class="col-12">
            <button class="btn btn-primary" type="submit">Create</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Users</h5>
        <div class="table-responsive">
          <table class="table table-striped align-middle">
            <thead>
              <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Created</th><th style="width:240px">Actions</th></tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u): ?>
                <tr>
                  <td><?= htmlspecialchars($u['id']) ?></td>
                  <td><?= htmlspecialchars($u['name']) ?></td>
                  <td><?= htmlspecialchars($u['email']) ?></td>
                  <td><span class="badge bg-<?= $u['role']==='admin'?'danger':'secondary' ?>"><?= htmlspecialchars($u['role']) ?></span></td>
                  <td><?= htmlspecialchars($u['created_at']) ?></td>
                  <td>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUserModal" data-user='<?= json_encode($u, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>'>Edit</button>
                    <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#resetPwdModal" data-user='<?= json_encode($u, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>'>Reset Password</button>
                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this user?');">
                      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                      <button class="btn btn-sm btn-outline-danger" type="submit" <?= ((int)current_user()['id'] === (int)$u['id']) ? 'disabled title="Cannot delete yourself"' : '' ?>>Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Edit User Modal -->
  <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post">
          <div class="modal-header">
            <h5 class="modal-title">Edit User</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <div class="mb-3">
              <label class="form-label">Name</label>
              <input class="form-control" name="name" id="edit-name" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input class="form-control" type="email" name="email" id="edit-email" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Role</label>
              <select class="form-select" name="role" id="edit-role">
                <option value="user">User</option>
                <option value="admin">Admin</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Save changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Reset Password Modal -->
  <div class="modal fade" id="resetPwdModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="post">
          <div class="modal-header">
            <h5 class="modal-title">Reset Password</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="id" id="reset-id">
            <div class="mb-2"><small class="text-muted">User: <span id="reset-user-name"></span></small></div>
            <div class="mb-3">
              <label class="form-label">New Password</label>
              <input class="form-control" type="password" name="new_password" minlength="10" required>
              <div class="form-text">At least 10 characters.</div>
            </div>
            <div class="mb-3">
              <label class="form-label">Confirm Password</label>
              <input class="form-control" type="password" name="confirm_password" minlength="10" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-warning">Reset</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const editModal = document.getElementById('editUserModal');
    editModal.addEventListener('show.bs.modal', (event) => {
      const button = event.relatedTarget;
      const user = JSON.parse(button.getAttribute('data-user'));
      document.getElementById('edit-id').value = user.id;
      document.getElementById('edit-name').value = user.name;
      document.getElementById('edit-email').value = user.email;
      document.getElementById('edit-role').value = user.role;
    });

    const resetModal = document.getElementById('resetPwdModal');
    resetModal.addEventListener('show.bs.modal', (event) => {
      const button = event.relatedTarget;
      const user = JSON.parse(button.getAttribute('data-user'));
      document.getElementById('reset-id').value = user.id;
      document.getElementById('reset-user-name').textContent = `${user.name} (#${user.id})`;
    });
  </script>
</body>
</html>
