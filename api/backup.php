<?php
// api/backup.php - Generar backup de la base de datos
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Verificar que el usuario es Super Admin
$auth->requireSuperAdmin();

// Configuración
$backup_dir = __DIR__ . '/../backups/';
$db_name = DB_NAME;
$host = DB_HOST;
$user = DB_USER;
$pass = DB_PASS;

// Crear carpeta de backups si no existe
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0777, true);
}

// Nombre del archivo de backup
$filename = 'backup_' . $db_name . '_' . date('Y-m-d_H-i-s') . '.sql';
$filepath = $backup_dir . $filename;

// Comando mysqldump
$command = "mysqldump --host={$host} --user={$user} ";
if (!empty($pass)) {
    $command .= "--password={$pass} ";
}
$command .= "{$db_name} > {$filepath} 2>&1";

// Ejecutar el comando
exec($command, $output, $return_var);

// Verificar si se creó el archivo
if (file_exists($filepath) && filesize($filepath) > 0) {
    // Registrar actividad
    $auth->logActivity($_SESSION['user_id'], 'Backup creado', "Archivo: {$filename}");
    
    // Descargar el archivo
    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filepath));
    readfile($filepath);
    
    // Opcional: eliminar backups antiguos (más de 30 días)
    $files = glob($backup_dir . '*.sql');
    foreach ($files as $file) {
        if (filemtime($file) < strtotime('-30 days')) {
            unlink($file);
        }
    }
    exit;
} else {
    // Si falla, intentar con método alternativo usando PHP puro
    backupWithPHP($db_name, $host, $user, $pass, $backup_dir, $filename);
}

function backupWithPHP($db_name, $host, $user, $pass, $backup_dir, $filename) {
    try {
        $db = new PDO("mysql:host={$host};dbname={$db_name}", $user, $pass);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Obtener todas las tablas
        $stmt = $db->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $output = "-- =====================================================\n";
        $output .= "-- Backup de Base de Datos\n";
        $output .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
        $output .= "-- Base de datos: {$db_name}\n";
        $output .= "-- =====================================================\n\n";
        $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        foreach ($tables as $table) {
            // Estructura de la tabla
            $stmt = $db->query("SHOW CREATE TABLE `{$table}`");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $output .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $output .= $row['Create Table'] . ";\n\n";
            
            // Datos de la tabla
            $stmt = $db->query("SELECT * FROM `{$table}`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($rows) > 0) {
                $output .= "INSERT INTO `{$table}` (`" . implode('`, `', array_keys($rows[0])) . "`) VALUES\n";
                $values = [];
                foreach ($rows as $row) {
                    $row_values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $row_values[] = "NULL";
                        } else {
                            $row_values[] = "'" . addslashes($value) . "'";
                        }
                    }
                    $values[] = "(" . implode(', ', $row_values) . ")";
                }
                $output .= implode(",\n", $values) . ";\n\n";
            }
        }
        
        $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        // Guardar archivo
        $filepath = $backup_dir . $filename;
        file_put_contents($filepath, $output);
        
        if (file_exists($filepath) && filesize($filepath) > 0) {
            // Descargar
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
        } else {
            die('Error al crear el backup con PHP');
        }
        
    } catch (PDOException $e) {
        die('Error de conexión: ' . $e->getMessage());
    }
}
?>