<?php
// api/check_maintenance.php - Verificar estado de mantenimiento
require_once '../includes/config.php';

header('Content-Type: application/json');

try {
    $db = getDB();
    $stmt = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'maintenance_mode'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $maintenance = ($result && $result['setting_value'] == '1');
    
    echo json_encode(['maintenance' => $maintenance]);
} catch (Exception $e) {
    echo json_encode(['maintenance' => false]);
}
?>