<?php
// api/download_video.php - Descargar video
require_once '../includes/config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$format = isset($_GET['format']) ? $_GET['format'] : 'mp4';
$quality = isset($_GET['quality']) ? $_GET['quality'] : '720p';

if ($id <= 0) {
    die('ID de video inválido');
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM videos WHERE id = :id AND active = 1");
$stmt->bindValue(':id', $id);
$stmt->execute();
$video = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$video) {
    die('Video no encontrado');
}

// Registrar descarga
$stmt = $db->prepare("UPDATE videos SET download_count = download_count + 1 WHERE id = :id");
$stmt->bindValue(':id', $id);
$stmt->execute();

// Determinar la URL de descarga según el tipo de video
if ($video['type'] === 'mp4') {
    // Para MP4, usar el archivo original
    $download_url = $video['url'];
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $video['title']) . '_' . $quality . '.mp4';
    
    header('Content-Type: video/mp4');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // Redirigir a la URL del archivo
    header('Location: ' . $download_url);
    exit;
    
} elseif ($video['type'] === 'youtube') {
    // Para YouTube, usar servicio externo
    $youtube_id = '';
    preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&]+)/', $video['url'], $matches);
    $youtube_id = $matches[1] ?? '';
    
    if ($youtube_id) {
        // Redirigir a servicio de descarga externo
        $download_url = "https://www.y2mate.com/youtube/{$youtube_id}";
        header('Location: ' . $download_url);
        exit;
    } else {
        die('No se pudo obtener el ID del video de YouTube');
    }
}

// Si no se pudo, redirigir al video
header('Location: video.php?id=' . $id);
exit;
?>