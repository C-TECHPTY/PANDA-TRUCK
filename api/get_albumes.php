<?php
// api/get_albumes.php - Obtener todos los álbumes
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Usar la instancia global de Auth
global $auth;

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');

$db = getDB();

// Obtener todos los álbumes
$stmt = $db->query("SELECT * FROM albumes ORDER BY year DESC, id DESC");
$albumes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Para cada álbum, obtener el número de canciones
foreach ($albumes as &$album) {
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM canciones WHERE album_id = :id");
    $stmt->bindValue(':id', $album['id']);
    $stmt->execute();
    $album['total_canciones'] = $stmt->fetch()['total'];
}

echo json_encode($albumes);
?>