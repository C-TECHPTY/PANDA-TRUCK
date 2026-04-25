<?php
// api/cargar.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../includes/config.php';

$tipo = $_GET['tipo'] ?? '';
$db = getDB();

try {
    switch($tipo) {
        case 'mixes':
            $search = $_GET['search'] ?? '';
            $genre = $_GET['genre'] ?? '';
            $status = $_GET['status'] ?? '';
            
            $sql = "SELECT * FROM mixes WHERE 1=1";
            $params = [];
            if ($search) { $sql .= " AND (title LIKE :search OR dj LIKE :search)"; $params[':search'] = "%$search%"; }
            if ($genre) { $sql .= " AND genre = :genre"; $params[':genre'] = $genre; }
            if ($status !== '') { $sql .= " AND active = :status"; $params[':status'] = $status; }
            $sql .= " ORDER BY id DESC";
            
            $stmt = $db->prepare($sql);
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->execute();
            $data = $stmt->fetchAll();
            break;
            
        case 'djs':
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? '';
            
            $sql = "SELECT * FROM djs WHERE 1=1";
            $params = [];
            if ($search) { $sql .= " AND name LIKE :search"; $params[':search'] = "%$search%"; }
            if ($status !== '') { $sql .= " AND active = :status"; $params[':status'] = $status; }
            $sql .= " ORDER BY name";
            
            $stmt = $db->prepare($sql);
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->execute();
            $data = $stmt->fetchAll();
            break;
            
        default:
            echo json_encode(['error' => 'Tipo no válido']);
            exit;
    }
    echo json_encode($data);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>