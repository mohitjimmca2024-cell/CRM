<?php

require_once __DIR__ . '/../../includes/bootstrap.php';

require_admin();

$start = new DateTimeImmutable('-6 days');
$labels = [];
$values = [];

for ($i = 0; $i < 7; $i++) {
    $day = $start->modify('+' . $i . ' days');
    $labels[$day->format('Y-m-d')] = 0;
}

$stmt = db()->prepare('SELECT DATE(created_at) AS day, COUNT(*) AS total FROM access_logs WHERE created_at >= ? GROUP BY day ORDER BY day');
$stmt->execute([$start->format('Y-m-d 00:00:00')]);
$rows = $stmt->fetchAll();

foreach ($rows as $row) {
    $labels[$row['day']] = (int) $row['total'];
}

foreach ($labels as $day => $total) {
    $values[] = $total;
}

header('Content-Type: application/json');
echo json_encode([
    'labels' => array_keys($labels),
    'values' => $values
]);
