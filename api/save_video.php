<?php
// api/save_video.php - Guardar/Editar video
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$auth->requireAdmin();

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

$db = getDB();

try {
    if (isset($data['id']) && $data['id'] > 0) {
        $sql = "UPDATE videos SET title = :title, dj = :dj, type = :type, url = :url, 
                cover = :cover, duration = :duration, active = :active WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $data['id']);
    } else {
        $sql = "INSERT INTO videos (title, dj, type, url, cover, duration, active) 
                VALUES (:title, :dj, :type, :url, :cover, :duration, :active)";
        $stmt = $db->prepare($sql);
    }

    $stmt->bindValue(':title', $data['title']);
    $stmt->bindValue(':dj', $data['dj']);
    $stmt->bindValue(':type', $data['type']);
    $stmt->bindValue(':url', $data['url']);
    $stmt->bindValue(':cover', $data['cover'] ?? '');
    $stmt->bindValue(':duration', $data['duration'] ?? '');
    $stmt->bindValue(':active', $data['active'] ?? 1);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al guardar']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>