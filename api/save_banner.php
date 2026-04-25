<?php
// api/save_banner.php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth->requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$id = $_POST['id'] ?? 0;
$name = $_POST['name'] ?? '';
$type = $_POST['type'] ?? 'sidebar';
$image = $_POST['image'] ?? '';
$url = $_POST['url'] ?? '#';
$size = $_POST['size'] ?? '300x250';
$position = $_POST['position'] ?? 1;
$start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
$end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
$active = isset($_POST['active']) ? 1 : 0;

if (empty($name) || empty($image)) {
    echo json_encode(['success' => false, 'error' => 'Nombre e imagen son requeridos']);
    exit;
}

try {
    $db = getDB();
    
    if ($id > 0) {
        // Actualizar
        $stmt = $db->prepare("UPDATE banners SET name = ?, type = ?, image = ?, url = ?, size = ?, position = ?, start_date = ?, end_date = ?, active = ? WHERE id = ?");
        $stmt->execute([$name, $type, $image, $url, $size, $position, $start_date, $end_date, $active, $id]);
        $message = "Se actualizó el banner: $name";
    } else {
        // Insertar
        $stmt = $db->prepare("INSERT INTO banners (name, type, image, url, size, position, start_date, end_date, active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $type, $image, $url, $size, $position, $start_date, $end_date, $active]);
        $message = "Se creó el banner: $name";
    }
    
    // Registrar actividad
    $user_id = $_SESSION['user_id'] ?? null;
    $username = $_SESSION['username'] ?? 'Unknown';
    $auth->logActivity($user_id, $username, 'Guardar banner', $message);
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    error_log("Error save_banner: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>