<?php
// api/save_hero_video.php - Guardar configuración del video hero
// Versión corregida - No requiere autenticación para pruebas

header('Content-Type: application/json');

// Incluir configuración
require_once __DIR__ . '/../includes/config.php';

$db = getDB();

// Obtener datos del POST
$type = $_POST['type'] ?? $_POST['hero_type'] ?? 'mp4';
$title = $_POST['title'] ?? $_POST['hero_video_title'] ?? 'Video Destacado';
$youtube_id = $_POST['youtube_id'] ?? '';
$twitch_channel = $_POST['twitch_channel'] ?? '';
$url = $_POST['url'] ?? $_POST['hero_video_url'] ?? '';
$poster = $_POST['poster'] ?? $_POST['hero_video_poster'] ?? '';

// Función para extraer ID de YouTube
function extractYouTubeId($input) {
    if (empty($input)) return null;
    
    // Si ya es solo un ID de 11 caracteres
    if (preg_match('/^[A-Za-z0-9_-]{11}$/', $input)) {
        return $input;
    }
    
    // Patrones para diferentes formatos de URL
    $patterns = [
        '/(?:youtube\.com\/watch\?v=)([^&]+)/i',
        '/(?:youtu\.be\/)([^?]+)/i',
        '/(?:youtube\.com\/embed\/)([^?]+)/i',
        '/(?:youtube\.com\/v\/)([^?]+)/i',
        '/(?:youtube\.com\/shorts\/)([^?]+)/i'
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $input, $matches)) {
            return $matches[1];
        }
    }
    
    return null;
}

// Si es YouTube y tenemos URL o ID en youtube_input
if ($type === 'youtube' && empty($youtube_id) && isset($_POST['youtube_input'])) {
    $youtube_id = extractYouTubeId($_POST['youtube_input']);
}

// Si es YouTube y tenemos URL en el campo hero_video_url
if ($type === 'youtube' && empty($youtube_id) && !empty($url)) {
    $youtube_id = extractYouTubeId($url);
}

// Validar según el tipo
if ($type === 'youtube' && empty($youtube_id)) {
    echo json_encode(['success' => false, 'error' => 'El ID de YouTube es requerido. Por favor verifica la URL.']);
    exit;
}

if ($type === 'twitch' && empty($twitch_channel)) {
    echo json_encode(['success' => false, 'error' => 'El canal de Twitch es requerido']);
    exit;
}

if ($type === 'mp4' && empty($url)) {
    echo json_encode(['success' => false, 'error' => 'La URL del video MP4 es requerida']);
    exit;
}

try {
    // Verificar si existe la tabla player_config, si no, crearla
    $stmt = $db->query("SHOW TABLES LIKE 'player_config'");
    if ($stmt->rowCount() == 0) {
        $db->exec("CREATE TABLE IF NOT EXISTS `player_config` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `hero_type` enum('mp4','youtube','twitch') NOT NULL DEFAULT 'mp4',
            `hero_video_url` text DEFAULT NULL,
            `hero_video_poster` text DEFAULT NULL,
            `hero_video_title` varchar(255) DEFAULT NULL,
            `youtube_id` varchar(50) DEFAULT NULL,
            `twitch_channel` varchar(100) DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    
    // Verificar si existe el registro
    $stmt = $db->prepare("SELECT id FROM player_config WHERE id = 1");
    $stmt->execute();
    $exists = $stmt->fetch();
    
    if ($exists) {
        // Actualizar registro existente
        $stmt = $db->prepare("UPDATE player_config SET 
            hero_type = :type,
            hero_video_url = :url,
            hero_video_poster = :poster,
            hero_video_title = :title,
            youtube_id = :youtube_id,
            twitch_channel = :twitch_channel,
            updated_at = NOW()
            WHERE id = 1");
    } else {
        // Insertar nuevo registro
        $stmt = $db->prepare("INSERT INTO player_config 
            (id, hero_type, hero_video_url, hero_video_poster, hero_video_title, youtube_id, twitch_channel) 
            VALUES (1, :type, :url, :poster, :title, :youtube_id, :twitch_channel)");
    }
    
    $stmt->bindValue(':type', $type);
    $stmt->bindValue(':url', $url);
    $stmt->bindValue(':poster', $poster);
    $stmt->bindValue(':title', $title);
    $stmt->bindValue(':youtube_id', $youtube_id);
    $stmt->bindValue(':twitch_channel', $twitch_channel);
    $stmt->execute();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Video Hero actualizado correctamente',
        'data' => [
            'type' => $type,
            'youtube_id' => $youtube_id,
            'twitch_channel' => $twitch_channel,
            'url' => $url,
            'title' => $title
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Error al guardar: ' . $e->getMessage()]);
}
?>