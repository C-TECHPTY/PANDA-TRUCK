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
    $name = trim($data['name'] ?? '');
    $slug = trim($data['slug'] ?? '');
    if ($slug === '' && $name !== '') {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        $slug = trim($slug, '-');
    }
    $avatar = $data['avatar'] ?? '';
    $bio = $data['bio'] ?? '';
    $biography = $data['biography'] ?? $bio;
    $profilePhoto = $data['profile_photo'] ?? $avatar;
    $socials = $data['socials'] ?? '';

    if (is_array($socials)) {
        $socials = json_encode($socials, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    if (isset($data['id']) && $data['id'] > 0) {
        $sql = "UPDATE djs SET name = :name, genre = :genre, city = :city, bio = :bio, 
                avatar = :avatar, socials = :socials, email = :email, instagram = :instagram,
                biography = :biography, profile_photo = :profile_photo, slug = :slug,
                active = :active WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $data['id']);
    } else {
        $sql = "INSERT INTO djs (name, genre, city, bio, avatar, socials, email, instagram, biography, profile_photo, slug, active)
                VALUES (:name, :genre, :city, :bio, :avatar, :socials, :email, :instagram, :biography, :profile_photo, :slug, :active)";
        $stmt = $db->prepare($sql);
    }

    $stmt->bindValue(':name', $name);
    $stmt->bindValue(':genre', $data['genre'] ?? '');
    $stmt->bindValue(':city', $data['city'] ?? '');
    $stmt->bindValue(':bio', $bio);
    $stmt->bindValue(':avatar', $avatar);
    $stmt->bindValue(':socials', $socials);
    $stmt->bindValue(':email', $data['email'] ?? '');
    $stmt->bindValue(':instagram', $data['instagram'] ?? '');
    $stmt->bindValue(':biography', $biography);
    $stmt->bindValue(':profile_photo', $profilePhoto);
    $stmt->bindValue(':slug', $slug);
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
