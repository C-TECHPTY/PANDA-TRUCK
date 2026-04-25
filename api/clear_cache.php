<?php
// api/clear_cache.php - Limpiar caché del sistema
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth->requireAdmin();

$db = getDB();
$result = ['success' => true, 'message' => '', 'deleted' => []];

try {
    // 1. Limpiar tabla de caché de sincronización si existe
    $tables = $db->query("SHOW TABLES LIKE 'sync_cache'")->fetchAll();
    if (count($tables) > 0) {
        $stmt = $db->exec("TRUNCATE TABLE sync_cache");
        $result['deleted'][] = 'sync_cache (tabla)';
    }
    
    // 2. Limpiar actividad antigua (más de 7 días) si la tabla existe
    $tables = $db->query("SHOW TABLES LIKE 'user_activity'")->fetchAll();
    if (count($tables) > 0) {
        $stmt = $db->prepare("DELETE FROM user_activity WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stmt->execute();
        $result['deleted'][] = 'user_activity (' . $stmt->rowCount() . ' registros antiguos)';
    }
    
    // 3. Limpiar sesiones antiguas (más de 24 horas)
    $session_path = session_save_path();
    if (empty($session_path)) {
        $session_path = sys_get_temp_dir();
    }
    if (is_dir($session_path)) {
        $files = glob($session_path . '/sess_*');
        $now = time();
        $deleted = 0;
        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file) > 86400)) { // 24 horas
                if (unlink($file)) {
                    $deleted++;
                }
            }
        }
        if ($deleted > 0) {
            $result['deleted'][] = 'sesiones (' . $deleted . ' archivos)';
        }
    }
    
    // 4. Limpiar carpeta temporal de PHP
    $temp_dir = sys_get_temp_dir();
    $files = glob($temp_dir . '/panda_truck_*');
    $deleted = 0;
    foreach ($files as $file) {
        if (is_file($file) && unlink($file)) {
            $deleted++;
        }
    }
    if ($deleted > 0) {
        $result['deleted'][] = 'archivos temporales (' . $deleted . ' archivos)';
    }
    
    // 5. Limpiar carpeta de caché si existe
    $cache_dir = __DIR__ . '/../cache/';
    if (is_dir($cache_dir)) {
        $files = glob($cache_dir . '*');
        $deleted = 0;
        foreach ($files as $file) {
            if (is_file($file) && unlink($file)) {
                $deleted++;
            }
        }
        if ($deleted > 0) {
            $result['deleted'][] = 'caché local (' . $deleted . ' archivos)';
        }
    }
    
    // 6. Limpiar variables de sesión del sistema
    if (isset($_SESSION['_cache'])) {
        unset($_SESSION['_cache']);
        $result['deleted'][] = 'variables de sesión';
    }
    
    if (count($result['deleted']) > 0) {
        $result['message'] = '✅ Caché limpiado correctamente';
        $result['details'] = 'Se limpió: ' . implode(', ', $result['deleted']);
    } else {
        $result['message'] = '✅ No se encontraron archivos de caché para limpiar';
    }
    
    // Registrar actividad
    $auth->logActivity($_SESSION['user_id'], 'Caché limpiado');
    
} catch (PDOException $e) {
    $result['success'] = false;
    $result['message'] = 'Error: ' . $e->getMessage();
} catch (Exception $e) {
    $result['success'] = false;
    $result['message'] = 'Error: ' . $e->getMessage();
}

echo json_encode($result);
?>