<?php
// backup.php - Script para respaldar base de datos automáticamente
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Incluir configuración de base de datos
require_once 'api/config.php';

function backupDatabase() {
    $conn = getDBConnection();
    
    // Crear carpeta backups si no existe
    if (!file_exists('backups')) {
        mkdir('backups', 0777, true);
    }
    
    $backupFile = 'backups/backup-' . date('Y-m-d-H-i-s') . '.sql';
    $sqlScript = "-- Respaldo de Base de Datos Panda Truck\n";
    $sqlScript .= "-- Generado: " . date('Y-m-d H:i:s') . "\n";
    $sqlScript .= "-- Base de datos: panda_truck\n\n";
    
    // Obtener todas las tablas
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    
    // Generar SQL para cada tabla
    foreach ($tables as $table) {
        $sqlScript .= "-- --------------------------------------------------------\n";
        $sqlScript .= "-- Table structure for table `$table`\n";
        $sqlScript .= "-- --------------------------------------------------------\n\n";
        
        // Obtener estructura de la tabla
        $result = $conn->query("SHOW CREATE TABLE `$table`");
        $row = $result->fetch_row();
        $sqlScript .= $row[1] . ";\n\n";
        
        // Obtener datos de la tabla
        $sqlScript .= "-- --------------------------------------------------------\n";
        $sqlScript .= "-- Dumping data for table `$table`\n";
        $sqlScript .= "-- --------------------------------------------------------\n\n";
        
        $result = $conn->query("SELECT * FROM `$table`");
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $columns = [];
                $values = [];
                
                foreach ($row as $key => $value) {
                    $columns[] = "`$key`";
                    $values[] = "'" . $conn->real_escape_string($value) . "'";
                }
                
                $sqlScript .= "INSERT INTO `$table` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
            }
            $sqlScript .= "\n";
        }
    }
    
    // Guardar archivo
    if (file_put_contents($backupFile, $sqlScript)) {
        // Comprimir el archivo (opcional)
        $zipFile = $backupFile . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
            $zip->addFile($backupFile, basename($backupFile));
            $zip->close();
            unlink($backupFile); // Eliminar el .sql original
            return $zipFile;
        }
        return $backupFile;
    }
    
    return false;
}

function getBackupList() {
    $backups = [];
    if (file_exists('backups')) {
        $files = scandir('backups', SCANDIR_SORT_DESCENDING);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && (strpos($file, '.sql') !== false || strpos($file, '.zip') !== false)) {
                $backups[] = [
                    'name' => $file,
                    'size' => filesize('backups/' . $file),
                    'date' => date('Y-m-d H:i:s', filemtime('backups/' . $file))
                ];
            }
        }
    }
    return $backups;
}

// Manejar solicitudes
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action'])) {
        switch ($_GET['action']) {
            case 'create':
                $backupFile = backupDatabase();
                if ($backupFile) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Respaldo creado exitosamente',
                        'file' => $backupFile,
                        'size' => filesize($backupFile)
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Error al crear respaldo'
                    ]);
                }
                break;
                
            case 'list':
                $backups = getBackupList();
                echo json_encode([
                    'success' => true,
                    'backups' => $backups
                ]);
                break;
                
            case 'download':
                if (isset($_GET['file'])) {
                    $file = 'backups/' . basename($_GET['file']);
                    if (file_exists($file)) {
                        header('Content-Type: application/octet-stream');
                        header('Content-Disposition: attachment; filename="' . basename($file) . '"');
                        header('Content-Length: ' . filesize($file));
                        readfile($file);
                        exit;
                    }
                }
                break;
        }
    }
}
?>