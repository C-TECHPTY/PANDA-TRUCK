<?php
// api/sync_stats.php - Sincronizar estadísticas entre statistics y mixes
header('Content-Type: application/json');

require_once '../includes/config.php';

$db = getDB();

try {
    // Actualizar cada mix con los totales de statistics
    $stmt = $db->prepare("
        UPDATE mixes m
        SET 
            m.plays = COALESCE((
                SELECT SUM(s.plays) 
                FROM statistics s 
                WHERE s.item_id = m.id AND s.item_type = 'mix'
            ), 0),
            m.downloads = COALESCE((
                SELECT SUM(s.downloads) 
                FROM statistics s 
                WHERE s.item_id = m.id AND s.item_type = 'mix'
            ), 0)
        WHERE m.active = 1
    ");
    
    $stmt->execute();
    $updated = $stmt->rowCount();
    
    // También actualizar videos si existen
    $stmt2 = $db->prepare("
        UPDATE videos v
        SET v.plays = COALESCE((
            SELECT SUM(s.plays) 
            FROM statistics s 
            WHERE s.item_id = v.id AND s.item_type = 'video'
        ), 0)
        WHERE v.active = 1
    ");
    $stmt2->execute();
    $updatedVideos = $stmt2->rowCount();
    
    echo json_encode([
        'success' => true,
        'message' => "Estadísticas sincronizadas correctamente",
        'mixes_updated' => $updated,
        'videos_updated' => $updatedVideos,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al sincronizar: ' . $e->getMessage()
    ]);
}
?>