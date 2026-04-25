<?php
// api/toggle_superpack.php - Activar/Desactivar Super Pack por DJ
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/config.php';

$data = json_decode(file_get_contents('php://input'), true);
$dj_name = $data['dj'] ?? '';

if (empty($dj_name)) {
    echo json_encode(['success' => false, 'error' => 'DJ no especificado']);
    exit;
}

$db = getDB();

try {
    // Contar mixes activos del DJ
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM mixes WHERE dj = :dj AND active = 1");
    $stmt->bindValue(':dj', $dj_name);
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    if ($count >= 4) {
        // Activar Super Pack para todos los mixes de este DJ
        $stmt = $db->prepare("UPDATE mixes SET is_superpack = 1 WHERE dj = :dj AND active = 1");
        $stmt->bindValue(':dj', $dj_name);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => '🔥 Super Pack ACTIVADO', 'count' => $count]);
    } else {
        // Desactivar Super Pack para todos los mixes de este DJ
        $stmt = $db->prepare("UPDATE mixes SET is_superpack = 0 WHERE dj = :dj AND active = 1");
        $stmt->bindValue(':dj', $dj_name);
        $stmt->execute();
        echo json_encode(['success' => true, 'message' => '❌ Super Pack DESACTIVADO (necesitas ' . (4 - $count) . ' mixes más)', 'count' => $count]);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>