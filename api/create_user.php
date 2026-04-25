<?php
// api/create_user.php - Crear usuarios (solo superadmin)
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth->requireRole('superadmin');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

$username = $data['username'] ?? '';
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$role = $data['role'] ?? 'admin';
$dj_id = $data['dj_id'] ?? null;

if (empty($username) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Todos los campos son requeridos']);
    exit;
}

$result = $auth->createUser($username, $email, $password, $role, $dj_id);
echo json_encode($result);
?>