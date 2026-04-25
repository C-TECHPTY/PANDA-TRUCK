<?php
// api/delete_album.php - Eliminar álbum y sus canciones
require_once '../includes/config.php';
require_once '../includes/auth.php';

global $auth;

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

if (!$auth->isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Permiso denegado']);
    exit;
}

header('Content-Type: application/json');

// Obtener ID tanto de POST como de JSON
$id = 0;
if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
} else {
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input && isset($input['id'])) {
        $id = intval($input['id']);
    }
}

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
    exit;
}

$db = getDB();

try {
    // Iniciar transacción
    $db->beginTransaction();
    
    // 1. Eliminar canciones del álbum primero
    $stmt1 = $db->prepare("DELETE FROM canciones WHERE album_id = :id");
    $stmt1->bindValue(':id', $id);
    $stmt1->execute();
    
    // 2. Eliminar el álbum
    $stmt2 = $db->prepare("DELETE FROM albumes WHERE id = :id");
    $stmt2->bindValue(':id', $id);
    $stmt2->execute();
    
    // Confirmar transacción
    $db->commit();
    
    echo json_encode(['success' => true, 'message' => 'Álbum y canciones eliminados']);
    
} catch (PDOException $e) {
    // Revertir cambios si algo falla
    $db->rollBack();
    echo json_encode(['success' => false, 'error' => 'Error al eliminar: ' . $e->getMessage()]);
}
?>