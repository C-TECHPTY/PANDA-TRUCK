<?php
// api/get_video.php - Obtener video por ID
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$id = $_GET['id'] ?? 0;
if ($id > 0) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM videos WHERE id = :id");
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
} else {
    echo json_encode(null);
}
?>