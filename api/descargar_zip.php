<?php
// api/descargar_zip.php - Versión que funciona con GET
require_once '../includes/config.php';

set_time_limit(300);
ini_set('memory_limit', '512M');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Obtener parámetros (GET es más simple)
$ids = isset($_GET['ids']) ? $_GET['ids'] : (isset($_POST['ids']) ? $_POST['ids'] : '');
$dj = isset($_GET['dj']) ? trim(urldecode($_GET['dj'])) : (isset($_POST['dj']) ? trim(urldecode($_POST['dj'])) : 'DJ');

// Para depuración, si no hay IDs, mostrar error claro
if (empty($ids)) {
    die('No se seleccionaron mixes. Por favor, selecciona al menos un mix.');
}

// Convertir IDs a array
$ids_array = explode(',', $ids);
$ids_array = array_map('intval', $ids_array);
$ids_array = array_filter($ids_array, function($id) { return $id > 0; });

if (empty($ids_array)) {
    die('IDs inválidos. IDs recibidos: ' . htmlspecialchars($ids));
}

$db = getDB();

// Obtener los mixes seleccionados
$placeholders = implode(',', array_fill(0, count($ids_array), '?'));
$stmt = $db->prepare("SELECT * FROM mixes WHERE id IN ($placeholders) AND active = 1 ORDER BY id DESC");
foreach ($ids_array as $i => $id) {
    $stmt->bindValue($i + 1, $id, PDO::PARAM_INT);
}
$stmt->execute();
$mixes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($mixes)) {
    die('No se encontraron mixes para los IDs: ' . implode(',', $ids_array));
}

// Crear nombre del archivo ZIP
$zipname = 'mixes_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $dj) . '_' . date('Y-m-d_H-i-s') . '.zip';
$temp_file = sys_get_temp_dir() . '/' . $zipname;

if (file_exists($temp_file)) {
    @unlink($temp_file);
}

$zip = new ZipArchive();
if ($zip->open($temp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
    die('Error al crear el archivo ZIP');
}

$files_added = 0;
$failed_files = [];

foreach ($mixes as $mix) {
    $filename = preg_replace('/[^a-zA-Z0-9áéíóúñÑ]/', '_', $mix['title']);
    $filename = trim($filename, '_');
    $filename = $filename . '.mp3';
    
    // Descargar usando cURL
    $ch = curl_init($mix['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
    $data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200 && !empty($data)) {
        $zip->addFromString($filename, $data);
        $files_added++;
        
        // Actualizar contador de descargas
        try {
            $stmt_update = $db->prepare("UPDATE mixes SET downloads = downloads + 1 WHERE id = :id");
            $stmt_update->bindValue(':id', $mix['id']);
            $stmt_update->execute();
        } catch (Exception $e) {}
    } else {
        $failed_files[] = $mix['title'] . " (HTTP $http_code)";
    }
    
    unset($data);
}

// Agregar archivo info
$info = "========================================\n";
$info .= "PACK DE MIXES\n";
$info .= "========================================\n";
$info .= "DJ/Artista: " . $dj . "\n";
$info .= "Fecha de descarga: " . date('d/m/Y H:i:s') . "\n";
$info .= "Total de mixes seleccionados: " . count($mixes) . "\n";
$info .= "Descargados exitosamente: " . $files_added . "\n";
$info .= "========================================\n\n";

if (!empty($failed_files)) {
    $info .= "⚠️ ARCHIVOS NO DESCARGADOS:\n";
    $info .= "----------------------------------------\n";
    foreach ($failed_files as $failed) {
        $info .= "❌ " . $failed . "\n";
    }
    $info .= "----------------------------------------\n\n";
}

$info .= "LISTA DE MIXES INCLUIDOS:\n";
$info .= "----------------------------------------\n";
foreach ($mixes as $index => $mix) {
    $status = in_array($mix['title'] . " (HTTP $http_code)", $failed_files) ? '❌' : '✅';
    $info .= ($index + 1) . ". " . $status . " " . $mix['title'] . "\n";
}
$info .= "----------------------------------------\n\n";
$info .= "© Panda Truck Reloaded - " . date('Y') . "\n";
$info .= "La casa de los DJs en Panamá\n";

$zip->addFromString('info.txt', $info);
$zip->close();

if ($files_added == 0) {
    unlink($temp_file);
    die('Error: No se pudo descargar ningún mix. Verifica las URLs.');
}

// Enviar el ZIP
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipname . '"');
header('Content-Length: ' . filesize($temp_file));
header('Cache-Control: no-cache, must-revalidate');

if (ob_get_level()) {
    ob_end_clean();
}

readfile($temp_file);
unlink($temp_file);
exit;
?>