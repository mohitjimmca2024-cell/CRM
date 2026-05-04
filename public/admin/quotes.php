<?php

require_once __DIR__ . '/../../includes/bootstrap.php';

require_admin();
$admin = current_user();
log_access_if_possible($admin['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quoteId = (int) ($_POST['quote_id'] ?? 0);
    $status = $_POST['status'] ?? 'new';

    $update = db()->prepare('UPDATE quotes SET status = ?, updated_at = NOW() WHERE id = ?');
    $update->execute([$status, $quoteId]);

    set_flash('success', 'Quote updated.');
    redirect('/admin/quotes.php');
}

$quotes = db()->query('SELECT quotes.id, quotes.service, quotes.status, quotes.created_at, users.name FROM quotes JOIN users ON users.id = quotes.user_id ORDER BY quotes.created_at DESC');

render_header('Manage quotes', true);
?>

<div class="card">
  <h2>Quotes</h2>
  <table class="table">
    <thead>
      <tr>
        <th>User</th>
        <th>Service</th>
        <th>Status</th>
        <th>Created</th>
        <th>Update</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($quotes as $quote): ?>
        <tr>
          <td><?php echo e($quote['name']); ?></td>
          <td><?php echo e($quote['service']); ?></td>
          <td><span class="badge"><?php echo e($quote['status']); ?></span></td>
          <td><?php echo e($quote['created_at']); ?></td>
          <td>
            <form method="post">
              <input type="hidden" name="quote_id" value="<?php echo e((string) $quote['id']); ?>">
              <select name="status" onchange="this.form.submit()">
                <option value="new" <?php echo $quote['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                <option value="reviewing" <?php echo $quote['status'] === 'reviewing' ? 'selected' : ''; ?>>Reviewing</option>
                <option value="approved" <?php echo $quote['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                <option value="rejected" <?php echo $quote['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
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
