<?php
// api/change_password.php - Cambiar contraseña
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$auth->requireLogin();

$data = json_decode(file_get_contents('php://input'), true);
$user_id = $data['user_id'] ?? null;
$new_password = $data['new_password'] ?? '';
$current = $data['current'] ?? '';

$db = getDB();

// Si es superadmin y se especifica user_id, cambiar contraseña de otro usuario
if ($auth->isSuperAdmin() && $user_id) {
    if (empty($new_password)) {
        echo json_encode(['success' => false, 'error' => 'La nueva contraseña es requerida']);
        exit;
    }
    
    if (strlen($new_password) < 6) {
        echo json_encode(['success' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres']);
        exit;
    }
    
    $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("UPDATE users SET password = :password WHERE id = :id");
    $stmt->bindValue(':password', $hashedPassword);
    $stmt->bindValue(':id', $user_id);
    
    if ($stmt->execute()) {
        $auth->logActivity($_SESSION['user_id'], 'Contraseña cambiada', "Usuario ID: $user_id");
        echo json_encode(['success' => true]);
        exit;
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al cambiar contraseña']);
        exit;
    }
}

// Cambiar propia contraseña
if (empty($current) || empty($new_password)) {
    echo json_encode(['success' => false, 'error' => 'Todos los campos son requeridos']);
    exit;
}

if (strlen($new_password) < 6) {
    echo json_encode(['success' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres']);
    exit;
}

$stmt = $db->prepare("SELECT password FROM users WHERE id = :id");
$stmt->bindValue(':id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch();

if (!$user || !password_verify($current, $user['password'])) {
    echo json_encode(['success' => false, 'error' => 'Contraseña actual incorrecta']);
    exit;
}

$hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
$stmt = $db->prepare("UPDATE users SET password = :password WHERE id = :id");
$stmt->bindValue(':password', $hashedPassword);
$stmt->bindValue(':id', $_SESSION['user_id']);

if ($stmt->execute()) {
    $auth->logActivity($_SESSION['user_id'], 'Contraseña cambiada');
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al cambiar contraseña']);
}
?>