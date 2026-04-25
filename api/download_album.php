<?php
// api/download_album.php - Descargar álbum completo en ZIP
require_once '../includes/config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    die('ID de álbum inválido');
}

$db = getDB();

// Obtener información del álbum
$stmt = $db->prepare("SELECT * FROM albumes WHERE id = :id AND active = 1");
$stmt->bindValue(':id', $id);
$stmt->execute();
$album = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$album) {
    die('Álbum no encontrado');
}

// Obtener canciones del álbum
$stmt = $db->prepare("SELECT * FROM canciones WHERE album_id = :id ORDER BY track_number ASC");
$stmt->bindValue(':id', $id);
$stmt->execute();
$canciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($canciones) == 0) {
    die('Este álbum no tiene canciones');
}

// Actualizar contador de descargas
$stmt = $db->prepare("UPDATE albumes SET download_count = download_count + 1 WHERE id = :id");
$stmt->bindValue(':id', $id);
$stmt->execute();

// Crear nombre del archivo ZIP
$zipname = 'album_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $album['title']) . '_' . date('Y-m-d') . '.zip';
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200 || empty($data)) {
        return false;
    }
    return $data;
}

// Agregar las canciones al ZIP
$files_added = 0;
foreach ($canciones as $cancion) {
    $track_num = str_pad($cancion['track_number'], 2, '0', STR_PAD_LEFT);
    $filename = $track_num . ' - ' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $cancion['title']) . '.mp3';
    
    $file_content = downloadFile($cancion['url']);
    if ($file_content !== false) {
        $zip->addFromString($filename, $file_content);
        $files_added++;
    }
}

// Agregar archivo de información
$info_content = "========================================\n";
$info_content .= "ÁLBUM: " . $album['title'] . "\n";
$info_content .= "ARTISTA: " . $album['artist'] . "\n";
$info_content .= "AÑO: " . $album['year'] . "\n";
$info_content .= "GÉNERO: " . $album['genre'] . "\n";
$info_content .= "DESCARGAS TOTALES: " . ($album['download_count'] + 1) . "\n";
$info_content .= "========================================\n\n";
$info_content .= "LISTA DE CANCIONES:\n";
$info_content .= "----------------------------------------\n";
foreach ($canciones as $cancion) {
    $info_content .= str_pad($cancion['track_number'], 2, '0', STR_PAD_LEFT) . ". " . $cancion['title'] . " (" . $cancion['duration'] . ")\n";
}
$info_content .= "----------------------------------------\n\n";
$info_content .= "© Panda Truck Reloaded - " . date('Y') . "\n";
$info_content .= "La casa de los DJs en Panamá\n";
$zip->addFromString('info.txt', $info_content);

$zip->close();

// Si no se pudo agregar ningún archivo, mostrar error
if ($files_added == 0) {
    unlink($temp_file);
    die('Error: No se pudieron descargar las canciones. Verifica que las URLs sean accesibles.');
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