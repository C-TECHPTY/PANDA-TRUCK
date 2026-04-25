<?php
// api/get_album.php - Obtener álbum por ID con sus canciones
require_once '../includes/config.php';
require_once '../includes/auth.php';

global $auth;

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

$db = getDB();

// Obtener álbum
$stmt = $db->prepare("SELECT * FROM albumes WHERE id = :id");
$stmt->bindValue(':id', $id);
$stmt->execute();
$album = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$album) {
    echo json_encode(['error' => 'Álbum no encontrado']);
    exit;
}

// Obtener canciones
$stmt = $db->prepare("SELECT * FROM canciones WHERE album_id = :id ORDER BY track_number ASC");
$stmt->bindValue(':id', $id);
$stmt->execute();
$canciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'album' => $album,
    'canciones' => $canciones
]);
?>