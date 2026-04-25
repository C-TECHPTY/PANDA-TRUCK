<?php
// api/delete_cancion.php - Eliminar canción
require_once '../includes/config.php';
require_once '../includes/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if (!in_array($_SESSION['user_role'], ['superadmin', 'admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Permiso denegado']);
    exit;
}

header('Content-Type: application/json');

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
    exit;
}

$db = getDB();

try {
    $stmt = $db->prepare("DELETE FROM canciones WHERE id = :id");
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error al eliminar: ' . $e->getMessage()]);
}
?>