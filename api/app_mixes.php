<?php
// api/app_mixes.php - Lista limpia de mixes para la app Android nativa.
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/config.php';

function panda_app_abs_url($path) {
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return SITE_URL . ltrim(str_replace('../', '', $path), '/');
}

function panda_app_download_name($title) {
    $name = preg_replace('/[^a-zA-Z0-9_-]+/', '_', (string)$title);
    $name = trim($name, '_');
    return $name !== '' ? $name . '.mp3' : 'mix.mp3';
}

try {
    $db = getDB();
    $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 80;
    $stmt = $db->prepare("
        SELECT id, title, dj, url, cover, duration, downloads, plays, date
        FROM mixes
        WHERE active = 1
        ORDER BY date DESC, id DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $mixes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($mixes as &$mix) {
        $audioPath = $mix['url'] ?? '';
        $mix['id'] = (int)$mix['id'];
        $mix['downloads'] = (int)($mix['downloads'] ?? 0);
        $mix['plays'] = (int)($mix['plays'] ?? 0);
        $mix['audio_url'] = cdn_audio_url($audioPath);
        $mix['audio_fallback_url'] = cdn_origin_audio_url($audioPath);
        $mix['download_url'] = SITE_URL . 'api/download_mix.php?id=' . (int)$mix['id'];
        $mix['direct_download_url'] = cdn_download_url($audioPath, panda_app_download_name($mix['title'] ?? 'mix'));
        $mix['cover_url'] = panda_app_abs_url($mix['cover'] ?: 'assets/img/default-cover.jpg');
        unset($mix['url']);
    }
    unset($mix);

    echo json_encode(['success' => true, 'mixes' => $mixes], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'mixes' => [], 'error' => 'No se pudieron cargar mixes.']);
}
?>
