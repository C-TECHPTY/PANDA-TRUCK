<?php
// api/update_superpack.php - Actualizar Super Pack de un mix específico
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/config.php';

$data = json_decode(file_get_contents('php://input'), true);
$mix_id = $data['id'] ?? 0;
$is_superpack = $data['is_superpack'] ?? 0;

if ($mix_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
    exit;
}

$db = getDB();

try {
    // Primero obtener el DJ de este mix
    $stmt = $db->prepare("SELECT dj FROM mixes WHERE id = :id");
    $stmt->bindValue(':id', $mix_id);
    $stmt->execute();
    $mix = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($mix) {
        $dj_name = $mix['dj'];
        
        // Actualizar el Super Pack de este mix específico
        $stmt = $db->prepare("UPDATE mixes SET is_superpack = :is_superpack WHERE id = :id");
        $stmt->bindValue(':is_superpack', $is_superpack);
        $stmt->bindValue(':id', $mix_id);
        $stmt->execute();
        
        // Verificar si el DJ tiene 4+ mixes y actualizar todos
        $stmt = $db->prepare("SELECT COUNT(*) as total FROM mixes WHERE dj = :dj AND active = 1");
        $stmt->bindValue(':dj', $dj_name);
        $stmt->execute();
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        if ($count >= 4) {
            // Si tiene 4+ mixes, asegurar que todos estén marcados
            $stmt = $db->prepare("UPDATE mixes SET is_superpack = 1 WHERE dj = :dj AND active = 1");
            $stmt->bindValue(':dj', $dj_name);
            $stmt->execute();
            echo json_encode(['success' => true, 'message' => 'Super Pack activado para todo el DJ', 'count' => $count]);
        } else {
            echo json_encode(['success' => true, 'message' => 'Super Pack actualizado para este mix', 'count' => $count]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Mix no encontrado']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>