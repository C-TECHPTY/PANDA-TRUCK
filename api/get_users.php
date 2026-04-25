<?php
// api/get_users.php - Listar usuarios (solo superadmin)
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth->requireRole('superadmin');

$users = $auth->getUsers();
echo json_encode($users);
?>