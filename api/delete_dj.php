<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? 0;

if ($id > 0) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE djs SET active = 0 WHERE id = :id");
    $stmt->bindValue(':id', $id);
    echo json_encode(['success' => $stmt->execute()]);
} else {
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
}
?>