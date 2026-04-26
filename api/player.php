<?php
// api/player.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../includes/config.php';

$action = $_GET['action'] ?? 'playlist';
$db = getDB();

switch ($action) {
    case 'playlist':
        // Obtener playlist completa (mixes activos)
        $stmt = $db->query("SELECT id, title, dj, url, cover, duration 
                            FROM mixes 
                            WHERE active = 1 
                            ORDER BY date DESC");
        $playlist = $stmt->fetchAll();
        foreach ($playlist as &$track) {
            $track['type'] = 'mix';
            $track['url'] = cdn_audio_url($track['url'] ?? '');
        }
        unset($track);
        
        // Agregar videos a la playlist
        $stmt = $db->query("SELECT id, title, dj, url, cover, duration, 'video' as type 
                            FROM videos 
                            WHERE active = 1 
                            ORDER BY date DESC");
        $videos = $stmt->fetchAll();
        
        $playlist = array_merge($playlist, $videos);
        
        echo json_encode($playlist);
        break;
        
    case 'track':
        $id = $_GET['id'] ?? 0;
        $type = $_GET['type'] ?? 'mix';
        
        if ($type == 'mix') {
            $stmt = $db->prepare("SELECT * FROM mixes WHERE id = :id AND active = 1");
        } else {
            $stmt = $db->prepare("SELECT * FROM videos WHERE id = :id AND active = 1");
        }
        
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $track = $stmt->fetch();
        
        if ($track) {
            // Registrar reproducción
            updateStatistics($id, $type, 'play');
            if ($type == 'mix') {
                $track['url'] = cdn_audio_url($track['url'] ?? '');
            }
            echo json_encode($track);
        } else {
            echo json_encode(['error' => 'Track no encontrado']);
        }
        break;
        
    case 'now_playing':
        // Obtener la última reproducción (simulado)
        $stmt = $db->query("SELECT * FROM user_activity 
                            WHERE action = 'play' 
                            ORDER BY created_at DESC 
                            LIMIT 1");
        $last = $stmt->fetch();
        
        if ($last) {
            if ($last['item_type'] == 'mix') {
                $track = getMixById($last['item_id']);
            } else {
                $track = getVideoById($last['item_id']);
            }
            echo json_encode($track);
        } else {
            echo json_encode(null);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Acción no válida']);
}
?>
