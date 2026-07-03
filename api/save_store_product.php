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
$name = trim($data['name'] ?? '');
$description = trim($data['description'] ?? '');
$price = (float)($data['price'] ?? 0);
$image = trim($data['image'] ?? '');
$sizes = trim($data['sizes'] ?? '');
$stock = (int)($data['stock'] ?? 0);
$active = !empty($data['active']) ? 1 : 0;

if ($name === '' || $price <= 0) {
    echo json_encode(['success' => false, 'error' => 'Nombre y precio son requeridos']);
    exit;
}

try {
    $db = getDB();
    store_ensure_tables($db);

    if ($id > 0) {
        $stmt = $db->prepare("UPDATE store_products SET name=:name, description=:description, price=:price, image=:image, sizes=:sizes, stock=:stock, active=:active WHERE id=:id");
        $stmt->execute(compact('name', 'description', 'price', 'image', 'sizes', 'stock', 'active', 'id'));
    } else {
        $stmt = $db->prepare("INSERT INTO store_products (name, description, price, image, sizes, stock, active) VALUES (:name, :description, :price, :image, :sizes, :stock, :active)");
        $stmt->execute(compact('name', 'description', 'price', 'image', 'sizes', 'stock', 'active'));
    }

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
