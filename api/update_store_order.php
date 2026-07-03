<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/store.php';
require_once __DIR__ . '/../includes/auth.php';

$auth->requireLogin();
if (!$auth->isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Permiso denegado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$id = (int)($data['id'] ?? 0);
$status = $data['status'] ?? '';
$allowed = ['pending','receipt_received','paid','delivered','cancelled'];

if ($id <= 0 || !in_array($status, $allowed, true)) {
    echo json_encode(['success' => false, 'error' => 'Datos invalidos']);
    exit;
}

try {
    $db = getDB();
    store_ensure_tables($db);
    $stmt = $db->prepare("UPDATE store_orders SET status = :status WHERE id = :id");
    $stmt->execute([':status' => $status, ':id' => $id]);
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
