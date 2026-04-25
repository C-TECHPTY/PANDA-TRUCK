<?php
// api/get_banner.php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth->requireLogin();

$id = $_GET['id'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM banners WHERE id = ?");
    $stmt->execute([$id]);
    $banner = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($banner) {
        echo json_encode($banner);
    } else {
        echo json_encode(['success' => false, 'error' => 'Banner no encontrado']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>