<?php
// api/save_cancion.php - Guardar/Editar canción
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

// Obtener datos
$input = json_decode(file_get_contents('php://input'), true);

if ($input) {
    $id = isset($input['id']) ? intval($input['id']) : 0;
    $album_id = intval($input['album_id'] ?? 0);
    $track_number = intval($input['track_number'] ?? 0);
    $title = trim($input['title'] ?? '');
    $duration = trim($input['duration'] ?? '');
    $url = trim($input['url'] ?? '');
    $sizeMB = intval($input['sizeMB'] ?? 0);
} else {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $album_id = intval($_POST['album_id'] ?? 0);
    $track_number = intval($_POST['track_number'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $sizeMB = intval($_POST['sizeMB'] ?? 0);
}

if ($album_id <= 0 || empty($title) || empty($url)) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos: album_id, título y URL son requeridos']);
    exit;
}

$db = getDB();

try {
    if ($id > 0) {
        $stmt = $db->prepare("UPDATE canciones SET 
            track_number = :track_number, 
            title = :title, 
            duration = :duration, 
            url = :url, 
            sizeMB = :sizeMB 
            WHERE id = :id AND album_id = :album_id");
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':album_id', $album_id);
        $stmt->bindValue(':track_number', $track_number);
        $stmt->bindValue(':title', $title);
        $stmt->bindValue(':duration', $duration);
        $stmt->bindValue(':url', $url);
        $stmt->bindValue(':sizeMB', $sizeMB);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'id' => $id]);
    } else {
        $stmt = $db->prepare("INSERT INTO canciones (album_id, track_number, title, duration, url, sizeMB) 
                              VALUES (:album_id, :track_number, :title, :duration, :url, :sizeMB)");
        $stmt->bindValue(':album_id', $album_id);
        $stmt->bindValue(':track_number', $track_number);
        $stmt->bindValue(':title', $title);
        $stmt->bindValue(':duration', $duration);
        $stmt->bindValue(':url', $url);
        $stmt->bindValue(':sizeMB', $sizeMB);
        $stmt->execute();
        
        $newId = $db->lastInsertId();
        echo json_encode(['success' => true, 'id' => $newId]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error al guardar: ' . $e->getMessage()]);
}
?>