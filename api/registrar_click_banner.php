<?php
// api/registrar_click_banner.php - Registrar click en banner
require_once '../includes/config.php';

header('Content-Type: application/json');

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false]);
    exit;
}

$db = getDB();

try {
    $stmt = $db->prepare("UPDATE banners SET clicks = clicks + 1 WHERE id = :id");
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false]);
}
?>