<?php
// api/share_data.php - Obtener datos para compartir
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$type = $_GET['type'] ?? '';
$id = intval($_GET['id'] ?? 0);

$db = getDB();
$data = [];

if ($type === 'mix' && $id > 0) {
    $stmt = $db->prepare("SELECT id, title, dj, cover FROM mixes WHERE id = :id AND active = 1");
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    $mix = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($mix) {
        $data = [
            'title' => $mix['title'],
            'artist' => $mix['dj'],
            'image' => $mix['cover'] ?? BASE_URL . 'assets/img/default-cover.jpg',
            'url' => BASE_URL . 'player/index.php?id=' . $mix['id'],
            'type' => 'mix'
        ];
    }
} elseif ($type === 'video' && $id > 0) {
    $stmt = $db->prepare("SELECT id, title, dj, cover FROM videos WHERE id = :id AND active = 1");
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    $video = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($video) {
        $data = [
            'title' => $video['title'],
            'artist' => $video['dj'],
            'image' => $video['cover'] ?? BASE_URL . 'assets/img/default-video.jpg',
            'url' => BASE_URL . 'player/video.php?id=' . $video['id'],
            'type' => 'video'
        ];
    }
} elseif ($type === 'dj' && $id > 0) {
    $stmt = $db->prepare("SELECT id, name, avatar FROM djs WHERE id = :id AND active = 1");
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    $dj = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($dj) {
        $data = [
            'title' => 'Perfil de ' . $dj['name'],
            'artist' => $dj['name'],
            'image' => $dj['avatar'] ?? BASE_URL . 'assets/img/default-avatar.jpg',
            'url' => BASE_URL . 'dj/perfil.php?dj=' . urlencode($dj['name']),
            'type' => 'dj'
        ];
    }
} elseif ($type === 'superpack' && !empty($id)) {
    $dj_name = urldecode($id);
    $stmt = $db->prepare("SELECT name, avatar FROM djs WHERE name = :name AND active = 1");
    $stmt->bindValue(':name', $dj_name);
    $stmt->execute();
    $dj = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($dj) {
        $data = [
            'title' => 'Super Pack - ' . $dj['name'],
            'artist' => $dj['name'],
            'image' => $dj['avatar'] ?? BASE_URL . 'assets/img/default-avatar.jpg',
            'url' => BASE_URL . 'dj/superpack.php?dj=' . urlencode($dj['name']),
            'type' => 'superpack'
        ];
    }
}

echo json_encode($data);
?>