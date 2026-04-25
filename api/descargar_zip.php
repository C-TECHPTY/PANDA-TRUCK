<?php
// api/descargar_zip.php - Descargar mixes seleccionados en ZIP
require_once '../includes/config.php';

set_time_limit(300);
error_reporting(E_ALL);
ini_set('display_errors', 0);

$ids = isset($_GET['ids']) ? $_GET['ids'] : (isset($_POST['ids']) ? $_POST['ids'] : '');
$dj = isset($_GET['dj']) ? trim(urldecode($_GET['dj'])) : (isset($_POST['dj']) ? trim(urldecode($_POST['dj'])) : 'DJ');

if ($ids === '') {
    http_response_code(400);
    die('No se seleccionaron mixes. Por favor, selecciona al menos un mix.');
}

$ids_array = array_filter(array_map('intval', explode(',', $ids)), function ($id) {
    return $id > 0;
});

if (empty($ids_array)) {
    http_response_code(400);
    die('IDs invalidos. IDs recibidos: ' . htmlspecialchars($ids));
}

$db = getDB();

$placeholders = implode(',', array_fill(0, count($ids_array), '?'));
$stmt = $db->prepare("SELECT * FROM mixes WHERE id IN ($placeholders) AND active = 1 ORDER BY id DESC");
foreach ($ids_array as $i => $id) {
    $stmt->bindValue($i + 1, $id, PDO::PARAM_INT);
}
$stmt->execute();
$mixes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($mixes)) {
    http_response_code(404);
    die('No se encontraron mixes para los IDs: ' . implode(',', $ids_array));
}

$zipname = 'mixes_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $dj) . '_' . date('Y-m-d_H-i-s') . '.zip';
$temp_file = sys_get_temp_dir() . '/' . uniqid('pt_mixes_zip_', true) . '_' . $zipname;

$zip = new ZipArchive();
if ($zip->open($temp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    die('Error al crear el archivo ZIP');
}

function downloadMixToTemp($url) {
    $tempPath = tempnam(sys_get_temp_dir(), 'pt_mix_');
    $handle = fopen($tempPath, 'wb');
    if (!$handle) {
        return [false, 0];
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FILE, $handle);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_errno($ch);
    curl_close($ch);
    fclose($handle);

    if ($error || $http_code !== 200 || filesize($tempPath) === 0) {
        unlink($tempPath);
        return [false, $http_code];
    }

    return [$tempPath, $http_code];
}

$files_added = 0;
$failed_files = [];
$successful_titles = [];
$temp_downloads = [];

foreach ($mixes as $mix) {
    $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $mix['title']);
    $filename = trim($filename, '_') . '.mp3';

    [$downloaded_file, $http_code] = downloadMixToTemp($mix['url']);

    if ($downloaded_file !== false) {
        $zip->addFile($downloaded_file, $filename);
        $temp_downloads[] = $downloaded_file;
        $successful_titles[] = $mix['title'];
        $files_added++;

        try {
            $stmt_update = $db->prepare("UPDATE mixes SET downloads = downloads + 1 WHERE id = :id");
            $stmt_update->bindValue(':id', $mix['id'], PDO::PARAM_INT);
            $stmt_update->execute();
        } catch (Exception $e) {
        }
    } else {
        $failed_files[] = $mix['title'] . " (HTTP $http_code)";
    }
}

$info = "========================================\n";
$info .= "PACK DE MIXES\n";
$info .= "========================================\n";
$info .= "DJ/Artista: " . $dj . "\n";
$info .= "Fecha de descarga: " . date('d/m/Y H:i:s') . "\n";
$info .= "Total de mixes seleccionados: " . count($mixes) . "\n";
$info .= "Descargados exitosamente: " . $files_added . "\n";
$info .= "========================================\n\n";

if (!empty($failed_files)) {
    $info .= "ARCHIVOS NO DESCARGADOS:\n";
    $info .= "----------------------------------------\n";
    foreach ($failed_files as $failed) {
        $info .= "- " . $failed . "\n";
    }
    $info .= "----------------------------------------\n\n";
}

$info .= "LISTA DE MIXES INCLUIDOS:\n";
$info .= "----------------------------------------\n";
foreach ($mixes as $index => $mix) {
    $status = in_array($mix['title'], $successful_titles, true) ? 'OK' : 'ERROR';
    $info .= ($index + 1) . ". " . $status . " " . $mix['title'] . "\n";
}
$info .= "----------------------------------------\n\n";
$info .= "Panda Truck Reloaded - " . date('Y') . "\n";
$info .= "La casa de los DJs en Panama\n";

$zip->addFromString('info.txt', $info);
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
    die('Error: No se pudo descargar ningun mix. Verifica las URLs.');
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
