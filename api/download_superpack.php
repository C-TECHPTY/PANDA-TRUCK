<?php
// api/download_superpack.php - Compatibilidad segura para Super Pack.
// Redirige a la lista de descargas CDN sin servir MP3 ni ZIP desde PHP.
require_once __DIR__ . '/../includes/config.php';

$dj = isset($_GET['dj']) ? trim($_GET['dj']) : '';

if ($dj === '') {
    http_response_code(400);
    die('DJ no especificado');
}

$db = getDB();
$stmt = $db->prepare("SELECT id FROM mixes WHERE dj = :dj AND active = 1 ORDER BY id DESC");
$stmt->bindValue(':dj', $dj);
$stmt->execute();
$ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

if (count($ids) < 4) {
    http_response_code(400);
    die('Este DJ no tiene suficientes mixes para un Super Pack');
}

$url = 'descargar_zip.php?ids=' . implode(',', $ids) . '&dj=' . rawurlencode($dj);
header('Location: ' . $url, true, 302);
exit;
?>
