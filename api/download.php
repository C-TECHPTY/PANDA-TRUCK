<?php
// api/download_mix.php - Forzar descarga de mix
require_once '../includes/config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die('ID de mix inválido');
}

$db = getDB();

// Obtener el mix
$stmt = $db->prepare("SELECT * FROM mixes WHERE id = :id AND active = 1");
$stmt->bindValue(':id', $id);
$stmt->execute();
$mix = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mix) {
    die('Mix no encontrado');
}

// Obtener la URL del archivo
$url = $mix['url'];
$filename = preg_replace('/[^a-zA-Z0-9áéíóúñÑ]/', '_', $mix['title']) . '.mp3';

// Obtener el contenido del archivo
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$file_content = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code !== 200 || empty($file_content)) {
    // Fallback: redirigir a la URL original
    header('Location: ' . $url);
    exit;
}

// Actualizar contador de descargas
$stmt = $db->prepare("UPDATE mixes SET downloads = downloads + 1 WHERE id = :id");
$stmt->bindValue(':id', $id);
$stmt->execute();

// Actualizar estadísticas
$stmt = $db->prepare("INSERT INTO statistics (item_id, item_type, downloads) 
                      VALUES (:id, 'mix', 1)
                      ON DUPLICATE KEY UPDATE downloads = downloads + 1");
$stmt->bindValue(':id', $id);
$stmt->execute();

// Forzar descarga
header('Content-Type: audio/mpeg');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($file_content));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

echo $file_content;
exit;
?>