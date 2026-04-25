<?php
// api/live_stream_config.php - Configurar transmisión en vivo
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$auth->requireAdmin();

$action = $_GET['action'] ?? '';
$db = getDB();

switch($action) {
    case 'get_config':
        $stmt = $db->query("SELECT * FROM videos WHERE type = 'live' AND active = 1 ORDER BY id DESC LIMIT 1");
        $live = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($live ?: null);
        break;
        
    case 'update_status':
        $data = json_decode(file_get_contents('php://input'), true);
        $id = $data['id'] ?? 0;
        $status = $data['status'] ?? 'offline';
        $viewers = $data['viewers'] ?? 0;
        
        $stmt = $db->prepare("UPDATE videos SET live_status = :status, live_viewers = :viewers WHERE id = :id");
        $stmt->bindValue(':status', $status);
        $stmt->bindValue(':viewers', $viewers);
        $stmt->bindValue(':id', $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        break;
        
    case 'generate_key':
        $key = 'pandatruck_' . bin2hex(random_bytes(16));
        echo json_encode(['key' => $key]);
        break;
        
    default:
        echo json_encode(['error' => 'Acción no válida']);
}
?>