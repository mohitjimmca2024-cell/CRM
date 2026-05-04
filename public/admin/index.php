<?php

require_once __DIR__ . '/../../includes/bootstrap.php';

require_admin();
$user = current_user();
log_access_if_possible($user['id']);

$totalUsers = db()->query('SELECT COUNT(*) AS total FROM users')->fetch();
$totalTickets = db()->query('SELECT COUNT(*) AS total FROM tickets')->fetch();
$totalQuotes = db()->query('SELECT COUNT(*) AS total FROM quotes')->fetch();

render_header('Admin dashboard', true);
?>

<div class="grid">
  <div class="card">
    <h3>Total users</h3>
    <p class="badge"><?php echo e((string) $totalUsers['total']); ?></p>
  </div>
  <div class="card">
    <h3>Total tickets</h3>
    <p class="badge"><?php echo e((string) $totalTickets['total']); ?></p>
  </div>
  <div class="card">
    <h3>Total quotes</h3>
    <p class="badge"><?php echo e((string) $totalQuotes['total']); ?></p>
  </div>
</div>

<div class="card">
  <h3>User visit graph</h3>
  <canvas id="visitsChart" height="90"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
  $(function () {
    $.getJSON('/api/visits.php', function (data) {
      const ctx = document.getElementById('visitsChart').getContext('2d');
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: data.labels,
          datasets: [{
            label: 'Visits',
            data: data.values,
            borderColor: '#0f172a',
            backgroundColor: 'rgba(15, 23, 42, 0.1)',
            tension: 0.3,
            fill: true
          }]
        },
        options: {
          scales: {
            y: { beginAtZero: true }
          }
        }
      });
    });
  });
</script>

<?php
render_footer();
