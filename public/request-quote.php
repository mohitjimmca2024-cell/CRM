<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();
$user = current_user();
log_access_if_possible($user['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service = trim($_POST['service'] ?? '');
    $details = trim($_POST['details'] ?? '');
    $budget = trim($_POST['budget'] ?? '');

    if ($service === '' || $details === '') {
        set_flash('error', 'Service and details are required.');
    } else {
        $stmt = db()->prepare('INSERT INTO quotes (user_id, service, details, budget) VALUES (?, ?, ?, ?)');
        $stmt->execute([$user['id'], $service, $details, $budget]);
        set_flash('success', 'Quote request submitted.');
        redirect('/request-quote.php');
    }
}

$quotes = db()->prepare('SELECT service, status, created_at FROM quotes WHERE user_id = ? ORDER BY created_at DESC');
$quotes->execute([$user['id']]);

render_header('Quotes');
?>

<div class="card">
  <h2>Request a quote</h2>
  <form method="post">
    <div class="field">
      <label for="service">Service</label>
      <input id="service" name="service" required>
    </div>
    <div class="field">
      <label for="details">Project details</label>
      <textarea id="details" name="details" rows="4" required></textarea>
    </div>
    <div class="field">
      <label for="budget">Budget (optional)</label>
      <input id="budget" name="budget">
    </div>
    <button type="submit">Submit request</button>
  </form>
</div>

<div class="card">
  <h3>Your quote requests</h3>
  <table class="table">
    <thead>
      <tr>
        <th>Service</th>
        <th>Status</th>
        <th>Submitted</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($quotes as $quote): ?>
        <tr>
          <td><?php echo e($quote['service']); ?></td>
          <td><span class="badge"><?php echo e($quote['status']); ?></span></td>
          <td><?php echo e($quote['created_at']); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
render_footer();
