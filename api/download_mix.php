<?php
// api/download_mix.php - Count download and redirect to the storage/CDN URL.
require_once '../includes/config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    http_response_code(400);
    die('ID de mix invalido');
}

$db = getDB();

$stmt = $db->prepare("SELECT id, title, url FROM mixes WHERE id = :id AND active = 1");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$mix = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mix || empty($mix['url'])) {
    http_response_code(404);
    die('Mix no encontrado');
}

$stmt = $db->prepare("UPDATE mixes SET downloads = downloads + 1 WHERE id = :id");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();

$stmt = $db->prepare("INSERT INTO statistics (item_id, item_type, downloads, last_updated)
                      VALUES (:id, 'mix', 1, NOW())
                      ON DUPLICATE KEY UPDATE downloads = downloads + 1, last_updated = NOW()");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();

$filename = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $mix['title'] ?? 'mix');
$filename = trim($filename, '_') ?: 'mix';
$cdnUrl = cdn_download_url($mix['url'], $filename . '.mp3');

if ($cdnUrl === '') {
    http_response_code(404);
    die('Archivo no disponible');
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('X-Robots-Tag: noindex, nofollow');
header('Location: ' . $cdnUrl, true, 302);
exit;
?>
