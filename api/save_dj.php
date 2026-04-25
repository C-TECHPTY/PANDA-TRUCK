<?php
// api/save_dj.php - Guardar/Editar DJ
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$auth->requireAdmin();

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'No se recibieron datos']);
    exit;
}

$db = getDB();

try {
    if (isset($data['id']) && $data['id'] > 0) {
        $sql = "UPDATE djs SET name = :name, genre = :genre, city = :city, bio = :bio, 
                avatar = :avatar, socials = :socials, active = :active WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $data['id']);
    } else {
        $sql = "INSERT INTO djs (name, genre, city, bio, avatar, socials, active) 
                VALUES (:name, :genre, :city, :bio, :avatar, :socials, :active)";
        $stmt = $db->prepare($sql);
    }

    $stmt->bindValue(':name', $data['name'] ?? '');
    $stmt->bindValue(':genre', $data['genre'] ?? '');
    $stmt->bindValue(':city', $data['city'] ?? '');
    $stmt->bindValue(':bio', $data['bio'] ?? '');
    $stmt->bindValue(':avatar', $data['avatar'] ?? '');
    $stmt->bindValue(':socials', $data['socials'] ?? '');
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