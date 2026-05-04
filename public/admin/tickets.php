<?php

require_once __DIR__ . '/../../includes/bootstrap.php';

require_admin();
$admin = current_user();
log_access_if_possible($admin['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketId = (int) ($_POST['ticket_id'] ?? 0);
    $status = $_POST['status'] ?? 'open';

    $update = db()->prepare('UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?');
    $update->execute([$status, $ticketId]);

    set_flash('success', 'Ticket updated.');
    redirect('/admin/tickets.php');
}

$tickets = db()->query('SELECT tickets.id, tickets.subject, tickets.status, tickets.created_at, users.name FROM tickets JOIN users ON users.id = tickets.user_id ORDER BY tickets.created_at DESC');

render_header('Manage tickets', true);
?>

<div class="card">
  <h2>Tickets</h2>
  <table class="table">
    <thead>
      <tr>
        <th>User</th>
        <th>Subject</th>
        <th>Status</th>
        <th>Created</th>
        <th>Update</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($tickets as $ticket): ?>
        <tr>
          <td><?php echo e($ticket['name']); ?></td>
          <td><?php echo e($ticket['subject']); ?></td>
          <td><span class="badge"><?php echo e($ticket['status']); ?></span></td>
          <td><?php echo e($ticket['created_at']); ?></td>
          <td>
            <form method="post">
              <input type="hidden" name="ticket_id" value="<?php echo e((string) $ticket['id']); ?>">
              <select name="status" onchange="this.form.submit()">
                <option value="open" <?php echo $ticket['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                <option value="in_progress" <?php echo $ticket['status'] === 'in_progress' ? 'selected' : ''; ?>>In progress</option>
                <option value="closed" <?php echo $ticket['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
              </select>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
render_footer();
