<?php
// api/download_superpack.php - Descargar Super Pack de DJ en ZIP
require_once '../includes/config.php';

$dj = isset($_GET['dj']) ? trim($_GET['dj']) : '';

if ($dj === '') {
    http_response_code(400);
    die('DJ no especificado');
}

$db = getDB();

$stmt = $db->prepare("SELECT * FROM mixes WHERE dj = :dj AND active = 1 ORDER BY id DESC");
$stmt->bindValue(':dj', $dj);
$stmt->execute();
$mixes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($mixes) < 4) {
    http_response_code(400);
    die('Este DJ no tiene suficientes mixes para un Super Pack');
}

$zipname = 'superpack_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $dj) . '_' . date('Y-m-d') . '.zip';
$temp_file = sys_get_temp_dir() . '/' . uniqid('pt_superpack_', true) . '_' . $zipname;
$zip = new ZipArchive();

if ($zip->open($temp_file, ZipArchive::CREATE) !== true) {
    http_response_code(500);
    die('Error al crear el archivo ZIP');
}

function downloadFileToTemp($url) {
    $tempPath = tempnam(sys_get_temp_dir(), 'pt_mix_');
    $handle = fopen($tempPath, 'wb');
    if (!$handle) {
        return false;
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FILE, $handle);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
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

foreach ($mixes as $mix) {
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $mix['title']) . '.mp3';
    $downloaded_file = downloadFileToTemp($mix['url']);

    if ($downloaded_file !== false) {
        $zip->addFile($downloaded_file, $filename);
        $temp_downloads[] = $downloaded_file;
        $files_added++;

        $stmt = $db->prepare("UPDATE mixes SET downloads = downloads + 1 WHERE id = :id");
        $stmt->bindValue(':id', $mix['id'], PDO::PARAM_INT);
        $stmt->execute();

        $stmt = $db->prepare("INSERT INTO statistics (item_id, item_type, downloads, last_updated)
                              VALUES (:id, 'mix', 1, NOW())
                              ON DUPLICATE KEY UPDATE downloads = downloads + 1, last_updated = NOW()");
        $stmt->bindValue(':id', $mix['id'], PDO::PARAM_INT);
        $stmt->execute();
    }
}

$info_content = "========================================\n";
$info_content .= "SUPER PACK: " . $dj . "\n";
$info_content .= "TOTAL DE MIXES: " . count($mixes) . "\n";
$info_content .= "FECHA DE DESCARGA: " . date('d/m/Y H:i:s') . "\n";
$info_content .= "========================================\n\n";
$info_content .= "LISTA DE MIXES:\n";
$info_content .= "----------------------------------------\n";
foreach ($mixes as $index => $mix) {
    $info_content .= ($index + 1) . ". " . $mix['title'] . " (" . ($mix['duration'] ?? '00:00') . ")\n";
}
$info_content .= "----------------------------------------\n\n";
$info_content .= "Panda Truck Reloaded - " . date('Y') . "\n";
$info_content .= "La casa de los DJs en Panama\n";
$info_content .= "https://pandatruckreloaded.com\n";
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
    die('Error: No se pudieron descargar los mixes. Verifica que las URLs sean accesibles.');
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
