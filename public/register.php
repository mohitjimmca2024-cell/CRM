<?php

require_once __DIR__ . '/../includes/bootstrap.php';

$user = current_user();
if ($user) {
    redirect('/dashboard.php');
}

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($name === '') {
        $errors[] = 'Name is required.';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email already registered.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)');
        $stmt->execute([$name, $email, $hash]);
        $userId = (int) db()->lastInsertId();

        $profileStmt = db()->prepare('INSERT INTO profiles (user_id) VALUES (?)');
        $profileStmt->execute([$userId]);

        login_user([
            'id' => $userId,
            'name' => $name,
            'email' => $email,
            'role' => 'user'
        ]);

        set_flash('success', 'Welcome! Your account is ready.');
        redirect('/dashboard.php');
    }
}

log_access_if_possible(null);
render_header('Register');
?>

<div class="card">
  <h2>Create account</h2>
  <?php if ($errors): ?>
    <div class="flash error">
      <?php echo e(implode(' ', $errors)); ?>
    </div>
  <?php endif; ?>
  <form method="post">
    <div class="field">
      <label for="name">Full name</label>
      <input id="name" name="name" value="<?php echo e($name); ?>" required>
    </div>
    <div class="field">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" value="<?php echo e($email); ?>" required>
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input id="password" name="password" type="password" required>
    </div>
    <button type="submit">Register</button>
  </form>
  <p style="margin-top: 12px;">Already have an account? <a href="/login.php">Login</a></p>
</div>

<?php
render_footer();
