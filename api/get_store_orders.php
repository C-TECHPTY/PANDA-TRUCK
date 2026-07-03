<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/store.php';
require_once __DIR__ . '/../includes/auth.php';

$auth->requireLogin();

$db = getDB();
store_ensure_tables($db);

$orders = $db->query("SELECT * FROM store_orders ORDER BY id DESC LIMIT 100")->fetchAll(PDO::FETCH_ASSOC);
$stmt = $db->prepare("SELECT * FROM store_order_items WHERE order_id = :id ORDER BY id ASC");
foreach ($orders as &$order) {
    $stmt->execute([':id' => $order['id']]);
    $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($order);

echo json_encode(['success' => true, 'orders' => $orders]);
?>
