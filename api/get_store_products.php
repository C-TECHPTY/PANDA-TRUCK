<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/store.php';

$db = getDB();
store_ensure_tables($db);

$includeInactive = isset($_GET['admin']) && $_GET['admin'] == '1';
$sql = "SELECT * FROM store_products" . ($includeInactive ? "" : " WHERE active = 1") . " ORDER BY id DESC";
$products = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'products' => $products, 'settings' => store_settings($db)]);
?>
