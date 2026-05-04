<?php

require_once __DIR__ . '/../../includes/bootstrap.php';

require_admin();
$admin = current_user();
log_access_if_possible($admin['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int) ($_POST['user_id'] ?? 0);
    $role = $_POST['role'] ?? 'user';

    if ($userId === $admin['id']) {
        set_flash('error', 'You cannot change your own role.');
    } else {
        $update = db()->prepare('UPDATE users SET role = ? WHERE id = ?');
        $update->execute([$role, $userId]);
        set_flash('success', 'User role updated.');
    }

    redirect('/admin/users.php');
}

$users = db()->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC');

render_header('Manage users', true);
?>

<div class="card">
  <h2>Users</h2>
  <table class="table">
    <thead>
      <tr>
        <th>Name</th>
        <th>Email</th>
        <th>Role</th>
        <th>Created</th>
        <th>Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $row): ?>
        <tr>
          <td><?php echo e($row['name']); ?></td>
          <td><?php echo e($row['email']); ?></td>
          <td><span class="badge"><?php echo e($row['role']); ?></span></td>
          <td><?php echo e($row['created_at']); ?></td>
          <td>
            <?php if ((int) $row['id'] !== $admin['id']): ?>
              <form method="post" style="display:inline-block;">
                <input type="hidden" name="user_id" value="<?php echo e((string) $row['id']); ?>">
                <select name="role" onchange="this.form.submit()">
                  <option value="user" <?php echo $row['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                  <option value="admin" <?php echo $row['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
              </form>
            <?php else: ?>
              <span class="badge">Current</span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
render_footer();
