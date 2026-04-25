<?php
// api/download_mix.php - Count download and redirect to the storage/CDN URL.
require_once '../includes/config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    http_response_code(400);
    die('ID de mix invalido');
}

$db = getDB();

$stmt = $db->prepare("SELECT id, url FROM mixes WHERE id = :id AND active = 1");
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

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Location: ' . $mix['url'], true, 302);
exit;
?>
