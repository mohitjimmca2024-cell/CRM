<?php

require_once __DIR__ . '/../includes/bootstrap.php';

$user = current_user();
log_access_if_possible($user['id'] ?? null);

render_header('Welcome');
?>

<section class="card">
  <h1>Small CRM System</h1>
  <p>Manage user interactions, track quotes, and handle support tickets from one simple dashboard.</p>
  <div style="margin-top: 16px;">
    <?php if ($user): ?>
      <a class="button" href="/dashboard.php">Go to Dashboard</a>
    <?php else: ?>
      <a class="button" href="/register.php">Create an account</a>
      <a class="button" href="/login.php" style="margin-left: 8px;">Login</a>
    <?php endif; ?>
  </div>
</section>

<section class="grid">
  <div class="card">
    <h3>User Module</h3>
    <ul>
      <li>Registration and login</li>
      <li>Profile management</li>
      <li>Quote requests</li>
      <li>Ticketing system</li>
    </ul>
  </div>
  <div class="card">
    <h3>Admin Panel</h3>
    <ul>
      <li>Dashboard overview</li>
      <li>Manage users</li>
      <li>Manage tickets and quotes</li>
      <li>User access logs</li>
    </ul>
  </div>
</section>

<?php
render_footer();
