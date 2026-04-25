<?php
// api/delete_banner.php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth->requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'ID de banner no proporcionado']);
    exit;
}

try {
    $db = getDB();
    
    // Verificar si el banner existe
    $stmt = $db->prepare("SELECT id, name FROM banners WHERE id = ?");
    $stmt->execute([$id]);
    $banner = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$banner) {
        echo json_encode(['success' => false, 'error' => 'Banner no encontrado']);
        exit;
    }
    
    // Eliminar el banner
    $stmt = $db->prepare("DELETE FROM banners WHERE id = ?");
    $stmt->execute([$id]);
    
    // Registrar la actividad
    $user_id = $_SESSION['user_id'] ?? null;
    $username = $_SESSION['username'] ?? 'Unknown';
    $auth->logActivity($user_id, $username, 'Eliminar banner', "Se eliminó el banner: {$banner['name']}");
    
    echo json_encode(['success' => true]);
    
} catch (PDOException $e) {
    error_log("Error delete_banner: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>