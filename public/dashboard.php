<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();
$user = current_user();
log_access_if_possible($user['id']);

$ticketStmt = db()->prepare('SELECT status, COUNT(*) AS total FROM tickets WHERE user_id = ? GROUP BY status');
$ticketStmt->execute([$user['id']]);
$ticketStats = $ticketStmt->fetchAll();

$quoteStmt = db()->prepare('SELECT status, COUNT(*) AS total FROM quotes WHERE user_id = ? GROUP BY status');
$quoteStmt->execute([$user['id']]);
$quoteStats = $quoteStmt->fetchAll();

$latestTickets = db()->prepare('SELECT subject, status, created_at FROM tickets WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$latestTickets->execute([$user['id']]);

$latestQuotes = db()->prepare('SELECT service, status, created_at FROM quotes WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$latestQuotes->execute([$user['id']]);

render_header('Dashboard');
?>

<div class="grid">
  <div class="card">
    <h3>Ticket status</h3>
    <?php if (!$ticketStats): ?>
      <p>No tickets yet.</p>
    <?php else: ?>
      <ul>
        <?php foreach ($ticketStats as $row): ?>
          <li><?php echo e($row['status']); ?>: <?php echo e((string) $row['total']); ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
  <div class="card">
    <h3>Quote status</h3>
    <?php if (!$quoteStats): ?>
      <p>No quotes yet.</p>
    <?php else: ?>
      <ul>
        <?php foreach ($quoteStats as $row): ?>
          <li><?php echo e($row['status']); ?>: <?php echo e((string) $row['total']); ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</div>

<div class="grid">
  <div class="card">
    <h3>Recent tickets</h3>
    <ul>
      <?php foreach ($latestTickets as $ticket): ?>
        <li><?php echo e($ticket['subject']); ?> · <?php echo e($ticket['status']); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <div class="card">
    <h3>Recent quotes</h3>
    <ul>
      <?php foreach ($latestQuotes as $quote): ?>
        <li><?php echo e($quote['service']); ?> · <?php echo e($quote['status']); ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>

<?php
render_footer();
