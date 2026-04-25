<?php
// api/get_videos.php - Obtener videos activos
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$db = getDB();

// Obtener videos activos ordenados por id descendente (más recientes primero)
$stmt = $db->query("SELECT * FROM videos WHERE active = 1 ORDER BY id DESC LIMIT 8");
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($videos);
?>