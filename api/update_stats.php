<?php
// api/update_stats.php
header('Content-Type: application/json');

require_once '../includes/config.php';

$response = ['success' => false, 'message' => 'Error'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $item_id = $data['id'] ?? null;
    $action = $data['action'] ?? null; // 'play' o 'download'
    
    if ($item_id && $action) {
        $conn = getDB();
        
        if ($action === 'play') {
            $sql = "UPDATE mixes SET plays = plays + 1 WHERE id = :id";
        } elseif ($action === 'download') {
            $sql = "UPDATE mixes SET downloads = downloads + 1 WHERE id = :id";
        } else {
            echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            exit;
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $item_id);
        
        if ($stmt->execute()) {
            // También actualizar la tabla statistics
            if ($action === 'play') {
                $sql2 = "INSERT INTO statistics (item_id, item_type, plays, downloads, last_updated) 
                         VALUES (:id, 'mix', 1, 0, NOW())
                         ON DUPLICATE KEY UPDATE plays = plays + 1, last_updated = NOW()";
            } else {
                $sql2 = "INSERT INTO statistics (item_id, item_type, plays, downloads, last_updated) 
                         VALUES (:id, 'mix', 0, 1, NOW())
                         ON DUPLICATE KEY UPDATE downloads = downloads + 1, last_updated = NOW()";
            }
            
            $stmt2 = $conn->prepare($sql2);
            $stmt2->bindParam(':id', $item_id);
            $stmt2->execute();
            
            // Obtener el nuevo valor
            $stmt3 = $conn->prepare("SELECT plays, downloads FROM mixes WHERE id = :id");
            $stmt3->bindParam(':id', $item_id);
            $stmt3->execute();
            $result = $stmt3->fetch(PDO::FETCH_ASSOC);
            
            $response = [
                'success' => true, 
                'plays' => $result['plays'],
                'downloads' => $result['downloads']
            ];
        }
    }
}

echo json_encode($response);
?>