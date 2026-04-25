<?php
// api/set_maintenance.php - Activar/Desactivar modo mantenimiento
require_once '../includes/config.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$mode = isset($input['mode']) ? intval($input['mode']) : 0;

try {
    $db = getDB();
    
    // Actualizar el valor
    $stmt = $db->prepare("UPDATE system_settings SET setting_value = :mode WHERE setting_key = 'maintenance_mode'");
    $stmt->bindValue(':mode', $mode);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'mode' => $mode]);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>