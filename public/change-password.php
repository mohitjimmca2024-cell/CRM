<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();
$user = current_user();
log_access_if_possible($user['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($new === '' || $new !== $confirm) {
        set_flash('error', 'New passwords do not match.');
    } else {
        $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$user['id']]);
        $record = $stmt->fetch();

        if (!$record || !password_verify($current, $record['password_hash'])) {
            set_flash('error', 'Current password is incorrect.');
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $update = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $update->execute([$hash, $user['id']]);
            set_flash('success', 'Password updated.');
            redirect('/change-password.php');
        }
    }
}

render_header('Change password');
?>

<div class="card">
  <h2>Change password</h2>
  <form method="post">
    <div class="field">
      <label for="current_password">Current password</label>
      <input id="current_password" name="current_password" type="password" required>
    </div>
    <div class="field">
      <label for="new_password">New password</label>
      <input id="new_password" name="new_password" type="password" required>
    </div>
    <div class="field">
      <label for="confirm_password">Confirm new password</label>
      <input id="confirm_password" name="confirm_password" type="password" required>
    </div>
    <button type="submit">Update password</button>
  </form>
</div>

<?php
render_footer();
