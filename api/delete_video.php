<?php
// api/delete_video.php - Eliminar video
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$auth->requireAdmin();

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? 0;

if ($id > 0) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE videos SET active = 0 WHERE id = :id");
    $stmt->bindValue(':id', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al eliminar']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
}
?>