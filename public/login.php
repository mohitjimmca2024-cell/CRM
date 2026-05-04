<?php

require_once __DIR__ . '/../includes/bootstrap.php';

if (current_user()) {
    redirect('/dashboard.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        $error = 'Invalid email or password.';
    } else {
        login_user($user);
        set_flash('success', 'Welcome back.');
        redirect('/dashboard.php');
    }
}

log_access_if_possible(null);
render_header('Login');
?>

<div class="card">
  <h2>Login</h2>
  <?php if ($error): ?>
    <div class="flash error"><?php echo e($error); ?></div>
  <?php endif; ?>
  <form method="post">
    <div class="field">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" value="<?php echo e($email); ?>" required>
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input id="password" name="password" type="password" required>
    </div>
    <button type="submit">Login</button>
  </form>
  <p style="margin-top: 12px;">No account yet? <a href="/register.php">Register</a></p>
</div>

<?php
render_footer();
