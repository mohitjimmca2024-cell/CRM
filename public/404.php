<?php

require_once __DIR__ . '/../includes/bootstrap.php';

render_header('Not found');
?>

<div class="card">
  <h2>Page not found</h2>
  <p>The page you are looking for does not exist.</p>
  <a class="button" href="/">Back to home</a>
</div>

<?php
render_footer();
