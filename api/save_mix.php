<?php
// api/save_mix.php - Guardar/Editar mix (VERSIÓN SIMPLE PARA PRUEBA)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Obtener datos
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'No se recibieron datos JSON']);
    exit;
}

// Conectar a BD
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/notifications.php';
require_once __DIR__ . '/../includes/push.php';

try {
    $db = getDB();
    $isUpdate = isset($data['id']) && $data['id'] > 0;
    
    // Verificar si es actualización o inserción
    if ($isUpdate) {
        // Actualizar
        $sql = "UPDATE mixes SET title = :title, dj = :dj, genre = :genre, url = :url, 
                cover = :cover, duration = :duration, sizeMB = :sizeMB, date = :date, active = :active 
                WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':id', $data['id']);
    } else {
        // Insertar nuevo
        $sql = "INSERT INTO mixes (title, dj, genre, url, cover, duration, sizeMB, date, active) 
                VALUES (:title, :dj, :genre, :url, :cover, :duration, :sizeMB, :date, :active)";
        $stmt = $db->prepare($sql);
    }
    
    $stmt->bindValue(':title', $data['title'] ?? 'Sin título');
    $stmt->bindValue(':dj', $data['dj'] ?? 'Sin DJ');
    $stmt->bindValue(':genre', $data['genre'] ?? 'Sin género');
    $audioPath = cdn_normalize_audio_path($data['url'] ?? '');
    $stmt->bindValue(':url', $audioPath);
    $stmt->bindValue(':cover', $data['cover'] ?? '');
    $stmt->bindValue(':duration', $data['duration'] ?? '');
    $stmt->bindValue(':sizeMB', $data['sizeMB'] ?? 0);
    $stmt->bindValue(':date', $data['date'] ?? date('Y-m-d'));
    $stmt->bindValue(':active', $data['active'] ?? 1);
    
    if ($stmt->execute()) {
        $newId = $isUpdate ? (int)$data['id'] : (int)$db->lastInsertId();

        try {
            if ($isUpdate && !empty($data['send_notification'])) {
                send_new_mix_notification($newId, true);
            } elseif (!$isUpdate) {
                send_new_mix_notification($newId);
            }
        } catch (Throwable $notificationError) {
            // El correo no debe bloquear el guardado del mix.
        }

        try {
            if (!$isUpdate) {
                panda_push_new_mix($newId);
            }
        } catch (Throwable $pushError) {
            // El push no debe bloquear el guardado del mix.
        }

        echo json_encode(['success' => true, 'id' => $newId]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al ejecutar SQL']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
