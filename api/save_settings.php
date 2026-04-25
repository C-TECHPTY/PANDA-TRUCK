<?php
// api/save_settings.php - Guardar configuración
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

$db = getDB();

// Obtener datos del POST
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $group = $_POST['group'] ?? '';
    $data = $_POST['data'] ?? [];
} else {
    $group = $input['group'] ?? '';
    $data = $input['data'] ?? [];
}

if (empty($group) || empty($data)) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

try {
    foreach ($data as $key => $value) {
        $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value, `group`) 
                              VALUES (:key, :value, :group)
                              ON DUPLICATE KEY UPDATE setting_value = :value, updated_at = NOW()");
        $stmt->bindValue(':key', $key);
        $stmt->bindValue(':value', $value);
        $stmt->bindValue(':group', $group);
        $stmt->execute();
    }
    
    // También actualizar tabla configuration para compatibilidad
    foreach ($data as $key => $value) {
        $stmt = $db->prepare("INSERT INTO configuration (config_key, config_value) 
                              VALUES (:key, :value)
                              ON DUPLICATE KEY UPDATE config_value = :value");
        $stmt->bindValue(':key', $key);
        $stmt->bindValue(':value', $value);
        $stmt->execute();
    }
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error al guardar: ' . $e->getMessage()]);
}
?>