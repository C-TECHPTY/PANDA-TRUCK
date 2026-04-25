<?php
// api/get_mix.php - Obtener mix por ID
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/config.php';

$id = $_GET['id'] ?? 0;
if ($id > 0) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM mixes WHERE id = :id");
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($result ?: null);
} else {
    echo json_encode(null);
}
?>