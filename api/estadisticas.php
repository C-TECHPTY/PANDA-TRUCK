<?php
// api/estadisticas.php
header('Content-Type: application/json');

require_once '../includes/config.php';

$stats = getStatistics();
echo json_encode($stats);
?>