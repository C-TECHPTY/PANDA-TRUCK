<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Incluir configuración de base de datos
require_once __DIR__ . '/../config/database.php';

$tipo = $_GET['tipo'] ?? '';

if (empty($tipo)) {
    echo json_encode(['error' => 'Tipo no especificado']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception("No se pudo conectar a la base de datos. Verifica las credenciales en config/database.php");
    }
    
    $datos = cargarDatosMySQL($db, $tipo);
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Error en cargar.php: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}

function cargarDatosMySQL($db, $tipo) {
    switch($tipo) {
        case 'mixes':
            return cargarMixes($db);
        case 'videos':
            return cargarVideos($db);
        case 'djs':
            return cargarDJs($db);
        case 'events':
            return cargarEvents($db);
        case 'banners':
            return cargarBanners($db);
        case 'player':
            return cargarConfig($db, 'player_config');
        case 'radio':
            return cargarConfig($db, 'radio_config');
        case 'customization':
            return cargarConfig($db, 'customization_config');
        case 'navigation':
            return cargarConfig($db, 'navigation_config');
        default:
            return ['error' => 'Tipo no válido: ' . $tipo];
    }
}

function cargarBanners($db) {
    try {
        $sql = "SELECT * FROM banners WHERE active = TRUE ORDER BY position ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        
        $banners = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $banners[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'type' => $row['type'],
                'image' => $row['image'],
                'url' => $row['url'],
                'size' => $row['size'],
                'position' => (int)$row['position'],
                'active' => (bool)$row['active'],
                'startDate' => $row['startDate'],
                'endDate' => $row['endDate'],
                'clicks' => (int)$row['clicks'],
                'impressions' => (int)$row['impressions']
            ];
        }
        
        error_log("Banners cargados: " . count($banners));
        return $banners;
        
    } catch (Exception $e) {
        error_log("Error cargando banners: " . $e->getMessage());
        return [];
    }
}

// ... (las otras funciones cargarMixes, cargarVideos, etc. se mantienen igual)
function cargarMixes($db) {
    try {
        $sql = "SELECT * FROM mixes ORDER BY id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        
        $mixes = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $mixes[] = [
                'id' => (int)$row['id'],
                'title' => $row['title'],
                'dj' => $row['dj'],
                'genre' => $row['genre'],
                'url' => $row['url'],
                'cover' => $row['cover'],
                'duration' => $row['duration'],
                'sizeMB' => (float)$row['sizeMB'],
                'plays' => (int)$row['plays'],
                'downloads' => (int)$row['downloads'],
                'date' => $row['date'],
                'tracks' => json_decode($row['tracks'] ?? '[]', true)
            ];
        }
        return $mixes;
    } catch (Exception $e) {
        error_log("Error cargando mixes: " . $e->getMessage());
        return [];
    }
}

function cargarVideos($db) {
    try {
        $sql = "SELECT * FROM videos ORDER BY id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        
        $videos = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $videos[] = [
                'id' => (int)$row['id'],
                'title' => $row['title'],
                'dj' => $row['dj'],
                'type' => $row['type'],
                'url' => $row['url'],
                'cover' => $row['cover'],
                'duration' => $row['duration'],
                'sizeMB' => (float)$row['sizeMB'],
                'plays' => (int)$row['plays'],
                'downloads' => (int)$row['downloads'],
                'date' => $row['date']
            ];
        }
        return $videos;
    } catch (Exception $e) {
        error_log("Error cargando videos: " . $e->getMessage());
        return [];
    }
}

function cargarDJs($db) {
    try {
        $sql = "SELECT * FROM djs ORDER BY name ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        
        $djs = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $djs[] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'genre' => $row['genre'],
                'city' => $row['city'],
                'bio' => $row['bio'],
                'avatar' => $row['avatar'],
                'socials' => $row['socials'],
                'mixes' => (int)$row['mixes'],
                'videos' => (int)$row['videos']
            ];
        }
        return $djs;
    } catch (Exception $e) {
        error_log("Error cargando DJs: " . $e->getMessage());
        return [];
    }
}

function cargarEvents($db) {
    try {
        $sql = "SELECT * FROM events ORDER BY id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        
        $events = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $events[] = [
                'id' => (int)$row['id'],
                'title' => $row['title'],
                'date' => $row['date'],
                'time' => $row['time'],
                'place' => $row['place'],
                'status' => $row['status']
            ];
        }
        return $events;
    } catch (Exception $e) {
        error_log("Error cargando eventos: " . $e->getMessage());
        return [];
    }
}

function cargarConfig($db, $table) {
    try {
        $sql = "SELECT config_data FROM $table WHERE id = 1";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && $row['config_data']) {
            return json_decode($row['config_data'], true);
        }
        return null;
    } catch (Exception $e) {
        error_log("Error cargando configuración $table: " . $e->getMessage());
        return null;
    }
}
?>