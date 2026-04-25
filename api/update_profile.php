<?php
// api/update_profile.php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth->requireLogin();

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

$db = getDB();

if ($action === 'update_avatar' && isset($data['avatar'])) {
    // Actualizar avatar del usuario
    $stmt = $db->prepare("UPDATE users SET avatar = :avatar WHERE id = :id");
    $stmt->bindValue(':avatar', $data['avatar']);
    $stmt->bindValue(':id', $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $_SESSION['user_avatar'] = $data['avatar'];
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al actualizar avatar']);
    }
} elseif ($action === 'update_info' && isset($data['email'])) {
    // Actualizar información del usuario
    $stmt = $db->prepare("UPDATE users SET email = :email WHERE id = :id");
    $stmt->bindValue(':email', $data['email']);
    $stmt->bindValue(':id', $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $_SESSION['user_email'] = $data['email'];
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al actualizar información']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Acción inválida']);
}
?>