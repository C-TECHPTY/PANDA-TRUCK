<?php
// api/get_banners_admin.php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth->requireLogin();

try {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM banners ORDER BY type, position, id DESC");
    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($banners);
} catch (PDOException $e) {
    echo json_encode([]);
}
?>