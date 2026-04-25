<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/auth.php';
$auth->requireSuperAdmin();
$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? 0;
if($id>0){
    $stmt = getDB()->prepare("UPDATE users SET active = 0 WHERE id = :id AND role != 'superadmin'");
    $stmt->bindValue(':id', $id);
    echo json_encode(['success' => $stmt->execute()]);
} else {
    echo json_encode(['success' => false]);
}
?>