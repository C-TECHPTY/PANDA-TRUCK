<?php
// api/download_album.php - Descargar album completo en ZIP
require_once '../includes/config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    http_response_code(400);
    die('ID de album invalido');
}

$db = getDB();

$stmt = $db->prepare("SELECT * FROM albumes WHERE id = :id AND active = 1");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$album = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$album) {
    http_response_code(404);
    die('Album no encontrado');
}

$stmt = $db->prepare("SELECT * FROM canciones WHERE album_id = :id ORDER BY track_number ASC");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$canciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($canciones) === 0) {
    http_response_code(400);
    die('Este album no tiene canciones');
}

$stmt = $db->prepare("UPDATE albumes SET download_count = download_count + 1 WHERE id = :id");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();

$zipname = 'album_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $album['title']) . '_' . date('Y-m-d') . '.zip';
$temp_file = sys_get_temp_dir() . '/' . uniqid('pt_album_zip_', true) . '_' . $zipname;
$zip = new ZipArchive();

if ($zip->open($temp_file, ZipArchive::CREATE) !== true) {
    http_response_code(500);
    die('Error al crear el archivo ZIP');
}

function downloadFileToTemp($url) {
    $tempPath = tempnam(sys_get_temp_dir(), 'pt_album_');
    $handle = fopen($tempPath, 'wb');
    if (!$handle) {
        return false;
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FILE, $handle);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_errno($ch);
    curl_close($ch);
    fclose($handle);

    if ($error || $http_code !== 200 || filesize($tempPath) === 0) {
        unlink($tempPath);
        return false;
    }

    return $tempPath;
}

$files_added = 0;
$temp_downloads = [];

foreach ($canciones as $cancion) {
    $track_num = str_pad($cancion['track_number'], 2, '0', STR_PAD_LEFT);
    $filename = $track_num . ' - ' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $cancion['title']) . '.mp3';
    $downloaded_file = downloadFileToTemp($cancion['url']);

    if ($downloaded_file !== false) {
        $zip->addFile($downloaded_file, $filename);
        $temp_downloads[] = $downloaded_file;
        $files_added++;
    }
}

$info_content = "========================================\n";
$info_content .= "ALBUM: " . $album['title'] . "\n";
$info_content .= "ARTISTA: " . $album['artist'] . "\n";
$info_content .= "ANO: " . $album['year'] . "\n";
$info_content .= "GENERO: " . $album['genre'] . "\n";
$info_content .= "DESCARGAS TOTALES: " . ($album['download_count'] + 1) . "\n";
$info_content .= "========================================\n\n";
$info_content .= "LISTA DE CANCIONES:\n";
$info_content .= "----------------------------------------\n";
foreach ($canciones as $cancion) {
    $info_content .= str_pad($cancion['track_number'], 2, '0', STR_PAD_LEFT) . ". " . $cancion['title'] . " (" . $cancion['duration'] . ")\n";
}
$info_content .= "----------------------------------------\n\n";
$info_content .= "Panda Truck Reloaded - " . date('Y') . "\n";
$info_content .= "La casa de los DJs en Panama\n";
$zip->addFromString('info.txt', $info_content);

$zip->close();

foreach ($temp_downloads as $downloaded_file) {
    if (file_exists($downloaded_file)) {
        unlink($downloaded_file);
    }
}

if ($files_added === 0) {
    if (file_exists($temp_file)) {
        unlink($temp_file);
    }
    http_response_code(502);
    die('Error: No se pudieron descargar las canciones. Verifica que las URLs sean accesibles.');
}

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipname . '"');
header('Content-Length: ' . filesize($temp_file));
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if (ob_get_level()) {
    ob_end_clean();
}

readfile($temp_file);
unlink($temp_file);
exit;
?>
