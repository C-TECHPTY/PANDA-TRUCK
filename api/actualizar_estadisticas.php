<?php
// api/actualizar_estadisticas.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/config.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['itemId']) || !isset($input['itemType']) || !isset($input['action'])) {
    echo json_encode(['success' => false, 'error' => 'Datos incompletos']);
    exit;
}

$item_id = (int)$input['itemId'];
$item_type = $input['itemType'];
$action = $input['action'];

$db = getDB();

try {
    if ($action === 'play') {
        // Actualizar tabla mixes
        $stmt = $db->prepare("UPDATE mixes SET plays = plays + 1 WHERE id = :id");
        $stmt->bindParam(':id', $item_id);
        $stmt->execute();
        
        // Actualizar tabla statistics
        $stmt = $db->prepare("INSERT INTO statistics (item_id, item_type, plays, downloads, last_updated) 
                              VALUES (:id, :type, 1, 0, NOW())
                              ON DUPLICATE KEY UPDATE plays = plays + 1, last_updated = NOW()");
        $stmt->bindParam(':id', $item_id);
        $stmt->bindParam(':type', $item_type);
        $stmt->execute();
        
        // Registrar en user_activity
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $stmt = $db->prepare("INSERT INTO user_activity (item_id, item_type, action, ip_address, user_agent) 
                              VALUES (:id, :type, 'play', :ip, :ua)");
        $stmt->bindParam(':id', $item_id);
        $stmt->bindParam(':type', $item_type);
        $stmt->bindParam(':ip', $ip);
        $stmt->bindParam(':ua', $user_agent);
        $stmt->execute();
        
    } elseif ($action === 'download') {
        // Actualizar tabla mixes
        $stmt = $db->prepare("UPDATE mixes SET downloads = downloads + 1 WHERE id = :id");
        $stmt->bindParam(':id', $item_id);
        $stmt->execute();
        
        // Actualizar tabla statistics
        $stmt = $db->prepare("INSERT INTO statistics (item_id, item_type, plays, downloads, last_updated) 
                              VALUES (:id, :type, 0, 1, NOW())
                              ON DUPLICATE KEY UPDATE downloads = downloads + 1, last_updated = NOW()");
        $stmt->bindParam(':id', $item_id);
        $stmt->bindParam(':type', $item_type);
        $stmt->execute();
        
        // Registrar en user_activity
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $stmt = $db->prepare("INSERT INTO user_activity (item_id, item_type, action, ip_address, user_agent) 
                              VALUES (:id, :type, 'download', :ip, :ua)");
        $stmt->bindParam(':id', $item_id);
        $stmt->bindParam(':type', $item_type);
        $stmt->bindParam(':ip', $ip);
        $stmt->bindParam(':ua', $user_agent);
        $stmt->execute();
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>