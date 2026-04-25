<?php
// api/save_album.php - Guardar/Editar álbum
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

// Obtener datos (pueden venir como JSON o FormData)
$input = json_decode(file_get_contents('php://input'), true);

if ($input) {
    $id = isset($input['id']) ? intval($input['id']) : 0;
    $title = trim($input['title'] ?? '');
    $artist = trim($input['artist'] ?? '');
    $genre = trim($input['genre'] ?? '');
    $year = isset($input['year']) && !empty($input['year']) ? intval($input['year']) : null;
    $cover = trim($input['cover'] ?? '');
    $zip_url = trim($input['zip_url'] ?? '');
    $description = trim($input['description'] ?? '');
    $active = isset($input['active']) ? intval($input['active']) : 1;
} else {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $title = trim($_POST['title'] ?? '');
    $artist = trim($_POST['artist'] ?? '');
    $genre = trim($_POST['genre'] ?? '');
    $year = isset($_POST['year']) && !empty($_POST['year']) ? intval($_POST['year']) : null;
    $cover = trim($_POST['cover'] ?? '');
    $zip_url = trim($_POST['zip_url'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $active = isset($_POST['active']) ? intval($_POST['active']) : 1;
}

if (empty($title) || empty($artist)) {
    echo json_encode(['success' => false, 'error' => 'Título y artista son requeridos']);
    exit;
}

$db = getDB();

try {
    if ($id > 0) {
        $stmt = $db->prepare("UPDATE albumes SET 
            title = :title, 
            artist = :artist, 
            genre = :genre, 
            year = :year, 
            cover = :cover, 
            zip_url = :zip_url,
            description = :description, 
            active = :active,
            updated_at = NOW() 
            WHERE id = :id");
        
        $stmt->bindValue(':id', $id);
        $stmt->bindValue(':title', $title);
        $stmt->bindValue(':artist', $artist);
        $stmt->bindValue(':genre', $genre);
        $stmt->bindValue(':year', $year);
        $stmt->bindValue(':cover', $cover);
        $stmt->bindValue(':zip_url', $zip_url);
        $stmt->bindValue(':description', $description);
        $stmt->bindValue(':active', $active);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'id' => $id]);
    } else {
        $stmt = $db->prepare("INSERT INTO albumes (title, artist, genre, year, cover, zip_url, description, active) 
                              VALUES (:title, :artist, :genre, :year, :cover, :zip_url, :description, :active)");
        
        $stmt->bindValue(':title', $title);
        $stmt->bindValue(':artist', $artist);
        $stmt->bindValue(':genre', $genre);
        $stmt->bindValue(':year', $year);
        $stmt->bindValue(':cover', $cover);
        $stmt->bindValue(':zip_url', $zip_url);
        $stmt->bindValue(':description', $description);
        $stmt->bindValue(':active', $active);
        $stmt->execute();
        
        $newId = $db->lastInsertId();
        echo json_encode(['success' => true, 'id' => $newId]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error al guardar: ' . $e->getMessage()]);
}
?>