<?php
// api/download_superpack.php - Descargar Super Pack de DJ en ZIP
require_once '../includes/config.php';

$dj = isset($_GET['dj']) ? trim($_GET['dj']) : '';

if (empty($dj)) {
    die('DJ no especificado');
}

$db = getDB();

// Obtener mixes del DJ
$stmt = $db->prepare("SELECT * FROM mixes WHERE dj = :dj AND active = 1 ORDER BY id DESC");
$stmt->bindValue(':dj', $dj);
$stmt->execute();
$mixes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($mixes) < 4) {
    die('Este DJ no tiene suficientes mixes para un Super Pack');
}

// Crear nombre del archivo ZIP
$zipname = 'superpack_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $dj) . '_' . date('Y-m-d') . '.zip';
$temp_file = sys_get_temp_dir() . '/' . $zipname;
$zip = new ZipArchive();

if ($zip->open($temp_file, ZipArchive::CREATE) !== TRUE) {
    die('Error al crear el archivo ZIP');
}

// Función para descargar archivo con cURL
function downloadFile($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200 || empty($data)) {
        return false;
    }
    return $data;
}

// Agregar los mixes al ZIP
$files_added = 0;
foreach ($mixes as $mix) {
    $filename = preg_replace('/[^a-zA-Z0-9áéíóúñÑ]/', '_', $mix['title']) . '.mp3';
    
    $file_content = downloadFile($mix['url']);
    if ($file_content !== false) {
        $zip->addFromString($filename, $file_content);
        $files_added++;
        
        // Actualizar contador de descargas
        $stmt = $db->prepare("UPDATE mixes SET downloads = downloads + 1 WHERE id = :id");
        $stmt->bindValue(':id', $mix['id']);
        $stmt->execute();
        
        // Actualizar estadísticas
        $stmt = $db->prepare("INSERT INTO statistics (item_id, item_type, downloads) 
                              VALUES (:id, 'mix', 1)
                              ON DUPLICATE KEY UPDATE downloads = downloads + 1");
        $stmt->bindValue(':id', $mix['id']);
        $stmt->execute();
    }
}

// Agregar archivo de información
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
$info_content .= "© Panda Truck Reloaded - " . date('Y') . "\n";
$info_content .= "La casa de los DJs en Panamá\n";
$info_content .= "https://pandatruckreloaded.com\n";
$zip->addFromString('info.txt', $info_content);

$zip->close();

// Si no se pudo agregar ningún archivo, mostrar error
if ($files_added == 0) {
    unlink($temp_file);
    die('Error: No se pudieron descargar los mixes. Verifica que las URLs sean accesibles.');
}

// Enviar el archivo ZIP
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipname . '"');
header('Content-Length: ' . filesize($temp_file));
header('Cache-Control: no-cache, must-revalidate');
readfile($temp_file);

// Eliminar archivo temporal
unlink($temp_file);
exit;
?>