<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();
$user = current_user();
log_access_if_possible($user['id']);

$stmt = db()->prepare('SELECT users.name, users.email, profiles.company, profiles.phone, profiles.location, profiles.bio FROM users LEFT JOIN profiles ON profiles.user_id = users.id WHERE users.id = ?');
$stmt->execute([$user['id']]);
$profile = $stmt->fetch();

if (!$profile) {
    set_flash('error', 'Profile not found.');
    redirect('/dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $company = trim($_POST['company'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $bio = trim($_POST['bio'] ?? '');

    if ($name === '') {
        set_flash('error', 'Name is required.');
    } else {
        $updateUser = db()->prepare('UPDATE users SET name = ? WHERE id = ?');
        $updateUser->execute([$name, $user['id']]);

        $updateProfile = db()->prepare('UPDATE profiles SET company = ?, phone = ?, location = ?, bio = ?, updated_at = NOW() WHERE user_id = ?');
        $updateProfile->execute([$company, $phone, $location, $bio, $user['id']]);

        login_user([
            'id' => $user['id'],
            'name' => $name,
            'email' => $user['email'],
            'role' => $user['role']
        ]);

        set_flash('success', 'Profile updated.');
        redirect('/profile.php');
    }
}

render_header('Profile');
?>

<div class="card">
  <h2>Profile</h2>
  <form method="post">
    <div class="field">
      <label for="name">Full name</label>
      <input id="name" name="name" value="<?php echo e($profile['name']); ?>" required>
    </div>
    <div class="field">
      <label>Email</label>
      <input value="<?php echo e($profile['email']); ?>" disabled>
    </div>
    <div class="field">
      <label for="company">Company</label>
      <input id="company" name="company" value="<?php echo e($profile['company'] ?? ''); ?>">
    </div>
    <div class="field">
      <label for="phone">Phone</label>
      <input id="phone" name="phone" value="<?php echo e($profile['phone'] ?? ''); ?>">
    </div>
    <div class="field">
      <label for="location">Location</label>
      <input id="location" name="location" value="<?php echo e($profile['location'] ?? ''); ?>">
    </div>
    <div class="field">
      <label for="bio">Bio</label>
      <textarea id="bio" name="bio" rows="4"><?php echo e($profile['bio'] ?? ''); ?></textarea>
    </div>
    <button type="submit">Save profile</button>
  </form>
</div>

<div class="card">
  <h3>Security</h3>
  <p>Update your password from the security page.</p>
  <a class="button" href="/change-password.php">Change password</a>
</div>

<?php
render_footer();
