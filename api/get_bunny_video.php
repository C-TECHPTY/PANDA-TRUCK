<?php
// api/get_bunny_video.php - Obtener información de video de Bunny.net
require_once '../includes/config.php';
require_once '../includes/auth.php';

global $auth;

if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

header('Content-Type: application/json');

$video_id = isset($_GET['video_id']) ? trim($_GET['video_id']) : '';

if (empty($video_id)) {
    echo json_encode(['error' => 'ID de video requerido']);
    exit;
}

// Llamar a la API de Bunny.net
$ch = curl_init("https://video.bunnycdn.com/library/" . BUNNY_LIBRARY_ID . "/videos/" . $video_id);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'AccessKey: ' . BUNNY_API_KEY,
    'accept: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    $video = json_decode($response, true);
    
    // Generar URLs de diferentes calidades
    $urls = [
        'hls' => "https://video.bunnycdn.com/play/{$video_id}/playlist.m3u8",
        'mp4_1080p' => "https://video.bunnycdn.com/play/{$video_id}/download/video.mp4",
        'mp4_720p' => "https://video.bunnycdn.com/play/{$video_id}/download/video_720.mp4",
        'mp4_480p' => "https://video.bunnycdn.com/play/{$video_id}/download/video_480.mp4",
        'thumbnail' => "https://video.bunnycdn.com/play/{$video_id}/thumbnail.jpg"
    ];
    
    echo json_encode([
        'success' => true,
        'video' => $video,
        'urls' => $urls,
        'embed' => "<iframe src='https://iframe.mediadelivery.net/embed/" . BUNNY_LIBRARY_ID . "/" . $video_id . "' loading='lazy' style='border:0;width:100%;height:100%;' allowfullscreen></iframe>"
    ]);
} else {
    echo json_encode(['error' => 'Video no encontrado en Bunny.net']);
}
?>