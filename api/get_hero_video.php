<?php
// api/get_hero_video.php - Obtener configuración del video hero
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$db = getDB();

$stmt = $db->query("SELECT hero_type, hero_video_url, hero_video_poster, hero_video_title, youtube_id, twitch_channel 
                    FROM player_config WHERE id = 1");
$hero = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hero) {
    $hero = [
        'hero_type' => 'mp4',
        'hero_video_url' => '',
        'hero_video_poster' => '',
        'hero_video_title' => 'Video Destacado',
        'youtube_id' => '',
        'twitch_channel' => ''
    ];
}

echo json_encode($hero);
?>