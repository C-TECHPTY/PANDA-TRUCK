<?php
// api/optimize.php - Optimizar base de datos
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth->requireAdmin();

$db = getDB();
$result = ['success' => true, 'message' => '', 'tables' => []];

try {
    // Obtener todas las tablas
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $total_before = 0;
    $total_after = 0;
    
    foreach ($tables as $table) {
        // Obtener tamaño antes de optimizar
        $stmt = $db->query("SHOW TABLE STATUS LIKE '{$table}'");
        $status = $stmt->fetch(PDO::FETCH_ASSOC);
        $size_before = ($status['Data_length'] + $status['Index_length']);
        $total_before += $size_before;
        
        // Optimizar tabla
        $db->exec("OPTIMIZE TABLE `{$table}`");
        
        // Obtener tamaño después de optimizar
        $stmt = $db->query("SHOW TABLE STATUS LIKE '{$table}'");
        $status = $stmt->fetch(PDO::FETCH_ASSOC);
        $size_after = ($status['Data_length'] + $status['Index_length']);
        $total_after += $size_after;
        
        $saved = $size_before - $size_after;
        
        if ($saved > 0) {
            $result['tables'][] = [
                'table' => $table,
                'saved' => formatBytes($saved)
            ];
        }
    }
    
    $total_saved = $total_before - $total_after;
    
    if ($total_saved > 0) {
        $result['message'] = '✅ Base de datos optimizada. Se liberaron ' . formatBytes($total_saved);
    } else {
        $result['message'] = '✅ Base de datos optimizada. No se necesitaba liberar espacio.';
    }
    
    // Registrar actividad
    $auth->logActivity($_SESSION['user_id'], 'Base de datos optimizada');
    
} catch (PDOException $e) {
    $result['success'] = false;
    $result['message'] = 'Error: ' . $e->getMessage();
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

echo json_encode($result);
?>