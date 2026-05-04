<?php

require_once __DIR__ . '/../../includes/bootstrap.php';

require_admin();
$admin = current_user();
log_access_if_possible($admin['id']);

$logs = db()->query('SELECT access_logs.path, access_logs.ip_address, access_logs.user_agent, access_logs.created_at, users.name AS user_name FROM access_logs LEFT JOIN users ON users.id = access_logs.user_id ORDER BY access_logs.created_at DESC LIMIT 200');

render_header('Access logs', true);
?>

<div class="card">
  <h2>Access logs</h2>
  <table class="table">
    <thead>
      <tr>
        <th>User</th>
        <th>Path</th>
        <th>IP</th>
        <th>User agent</th>
        <th>Time</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($logs as $log): ?>
        <tr>
          <td><?php echo e($log['user_name'] ?? 'Guest'); ?></td>
          <td><?php echo e($log['path']); ?></td>
          <td><?php echo e($log['ip_address']); ?></td>
          <td><?php echo e($log['user_agent']); ?></td>
          <td><?php echo e($log['created_at']); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
render_footer();
