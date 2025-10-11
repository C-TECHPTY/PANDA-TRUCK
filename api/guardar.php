<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Incluir configuración
require_once __DIR__ . '/../config/database.php';

// Leer input JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['tipo']) || !isset($input['datos'])) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

$tipo = $input['tipo'];
$datos = $input['datos'];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("No se pudo conectar a la base de datos. Verifica las credenciales en config/database.php");
    }
    
    $resultado = guardarDatosMySQL($db, $tipo, $datos);
    echo json_encode($resultado);
    
} catch (Exception $e) {
    error_log("Error en guardar.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

function guardarDatosMySQL($db, $tipo, $datos) {
    switch($tipo) {
        case 'mixes':
            return guardarMixes($db, $datos);
        case 'videos':
            return guardarVideos($db, $datos);
        case 'djs':
            return guardarDJs($db, $datos);
        case 'events':
            return guardarEvents($db, $datos);
        case 'banners':
            return guardarBanners($db, $datos);
        case 'player':
            return guardarConfig($db, 'player_config', $datos);
        case 'radio':
            return guardarConfig($db, 'radio_config', $datos);
        case 'customization':
            return guardarConfig($db, 'customization_config', $datos);
        case 'navigation':
            return guardarConfig($db, 'navigation_config', $datos);
        default:
            return ['success' => false, 'error' => 'Tipo no válido: ' . $tipo];
    }
}

function guardarMixes($db, $mixes) {
    // Primero limpiar tabla
    $db->exec("DELETE FROM mixes");
    
    $sql = "INSERT INTO mixes (id, title, dj, genre, url, cover, duration, sizeMB, plays, downloads, date, tracks) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    $count = 0;
    
    foreach ($mixes as $mix) {
        $tracksJson = json_encode($mix['tracks'] ?? []);
        try {
            $stmt->execute([
                $mix['id'],
                $mix['title'],
                $mix['dj'],
                $mix['genre'],
                $mix['url'],
                $mix['cover'],
                $mix['duration'],
                $mix['sizeMB'],
                $mix['plays'] ?? 0,
                $mix['downloads'] ?? 0,
                $mix['date'],
                $tracksJson
            ]);
            $count++;
        } catch (Exception $e) {
            error_log("Error guardando mix ID {$mix['id']}: " . $e->getMessage());
        }
    }
    
    return ['success' => true, 'message' => "{$count} mixes guardados correctamente", 'count' => $count];
}

function guardarVideos($db, $videos) {
    $db->exec("DELETE FROM videos");
    
    $sql = "INSERT INTO videos (id, title, dj, type, url, cover, duration, sizeMB, plays, downloads, date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    $count = 0;
    
    foreach ($videos as $video) {
        try {
            $stmt->execute([
                $video['id'],
                $video['title'],
                $video['dj'],
                $video['type'],
                $video['url'],
                $video['cover'],
                $video['duration'],
                $video['sizeMB'] ?? null,
                $video['plays'] ?? 0,
                $video['downloads'] ?? 0,
                $video['date']
            ]);
            $count++;
        } catch (Exception $e) {
            error_log("Error guardando video ID {$video['id']}: " . $e->getMessage());
        }
    }
    
    return ['success' => true, 'message' => "{$count} videos guardados correctamente", 'count' => $count];
}

function guardarDJs($db, $djs) {
    $db->exec("DELETE FROM djs");
    
    $sql = "INSERT INTO djs (id, name, genre, city, bio, avatar, socials, mixes, videos) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    $count = 0;
    
    foreach ($djs as $dj) {
        try {
            $stmt->execute([
                $dj['id'],
                $dj['name'],
                $dj['genre'],
                $dj['city'] ?? '',
                $dj['bio'] ?? '',
                $dj['avatar'] ?? '',
                $dj['socials'] ?? '',
                $dj['mixes'] ?? 0,
                $dj['videos'] ?? 0
            ]);
            $count++;
        } catch (Exception $e) {
            error_log("Error guardando DJ ID {$dj['id']}: " . $e->getMessage());
        }
    }
    
    return ['success' => true, 'message' => "{$count} DJs guardados correctamente", 'count' => $count];
}

function guardarEvents($db, $events) {
    $db->exec("DELETE FROM events");
    
    $sql = "INSERT INTO events (id, title, date, time, place, status) 
            VALUES (?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    $count = 0;
    
    foreach ($events as $event) {
        try {
            $stmt->execute([
                $event['id'],
                $event['title'],
                $event['date'],
                $event['time'],
                $event['place'],
                $event['status']
            ]);
            $count++;
        } catch (Exception $e) {
            error_log("Error guardando evento ID {$event['id']}: " . $e->getMessage());
        }
    }
    
    return ['success' => true, 'message' => "{$count} eventos guardados correctamente", 'count' => $count];
}

function guardarBanners($db, $banners) {
    $db->exec("DELETE FROM banners");
    
    $sql = "INSERT INTO banners (id, name, type, image, url, size, position, active, startDate, endDate, clicks, impressions) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    $count = 0;
    
    foreach ($banners as $banner) {
        try {
            $stmt->execute([
                $banner['id'],
                $banner['name'],
                $banner['type'],
                $banner['image'],
                $banner['url'],
                $banner['size'],
                $banner['position'],
                $banner['active'] ? 1 : 0,
                $banner['startDate'] ?? null,
                $banner['endDate'] ?? null,
                $banner['clicks'] ?? 0,
                $banner['impressions'] ?? 0
            ]);
            $count++;
        } catch (Exception $e) {
            error_log("Error guardando banner ID {$banner['id']}: " . $e->getMessage());
        }
    }
    
    return ['success' => true, 'message' => "{$count} banners guardados correctamente", 'count' => $count];
}

function guardarConfig($db, $table, $config) {
    $configJson = json_encode($config);
    
    // Verificar si existe
    $checkSql = "SELECT id FROM $table WHERE id = 1";
    $stmt = $db->prepare($checkSql);
    $stmt->execute();
    
    if ($stmt->fetch()) {
        // Actualizar
        $sql = "UPDATE $table SET config_data = ? WHERE id = 1";
    } else {
        // Insertar
        $sql = "INSERT INTO $table (id, config_data) VALUES (1, ?)";
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$configJson]);
    
    return ['success' => true, 'message' => "Configuración guardada en $table"];
}
?>