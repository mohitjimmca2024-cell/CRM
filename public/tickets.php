<?php

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();
$user = current_user();
log_access_if_possible($user['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($subject === '' || $message === '') {
        set_flash('error', 'Subject and message are required.');
    } else {
        $stmt = db()->prepare('INSERT INTO tickets (user_id, subject, message) VALUES (?, ?, ?)');
        $stmt->execute([$user['id'], $subject, $message]);
        set_flash('success', 'Ticket submitted.');
        redirect('/tickets.php');
    }
}

$tickets = db()->prepare('SELECT subject, status, created_at FROM tickets WHERE user_id = ? ORDER BY created_at DESC');
$tickets->execute([$user['id']]);

render_header('Tickets');
?>

<div class="card">
  <h2>Create a ticket</h2>
  <form method="post">
    <div class="field">
      <label for="subject">Subject</label>
      <input id="subject" name="subject" required>
    </div>
    <div class="field">
      <label for="message">Message</label>
      <textarea id="message" name="message" rows="4" required></textarea>
    </div>
    <button type="submit">Submit ticket</button>
  </form>
</div>

<div class="card">
  <h3>Your tickets</h3>
  <table class="table">
    <thead>
      <tr>
        <th>Subject</th>
        <th>Status</th>
        <th>Submitted</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($tickets as $ticket): ?>
        <tr>
          <td><?php echo e($ticket['subject']); ?></td>
          <td><span class="badge"><?php echo e($ticket['status']); ?></span></td>
          <td><?php echo e($ticket['created_at']); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
render_footer();
