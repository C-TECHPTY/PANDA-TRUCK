<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/store.php';

try {
    $db = getDB();
    store_ensure_tables($db);

    $items = json_decode($_POST['items'] ?? '[]', true);
    if (!is_array($items) || count($items) === 0) {
        throw new RuntimeException('El carrito esta vacio');
    }

    $name = trim($_POST['customer_name'] ?? '');
    $email = trim($_POST['customer_email'] ?? '');
    $phone = trim($_POST['customer_phone'] ?? '');
    $address = trim($_POST['customer_address'] ?? '');
    $note = trim($_POST['customer_note'] ?? '');
    $method = trim($_POST['payment_method'] ?? '');

    if ($name === '' || $phone === '' || $method === '') {
        throw new RuntimeException('Nombre, WhatsApp y metodo de pago son requeridos');
    }

    if (empty($_FILES['payment_receipt']['name'])) {
        throw new RuntimeException('Sube el comprobante de pago para registrar el pedido');
    }

    $uploadDir = __DIR__ . '/../uploads/receipts';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }
    $ext = strtolower(pathinfo($_FILES['payment_receipt']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp','pdf'], true)) {
        throw new RuntimeException('Comprobante invalido. Usa imagen o PDF');
    }
    $receiptName = 'receipt_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $receiptPath = $uploadDir . '/' . $receiptName;
    if (!move_uploaded_file($_FILES['payment_receipt']['tmp_name'], $receiptPath)) {
        throw new RuntimeException('No se pudo subir el comprobante');
    }
    $receiptPublic = 'uploads/receipts/' . $receiptName;

    $productIds = [];
    foreach ($items as $item) {
        $productIds[] = (int)($item['id'] ?? 0);
    }
    $productIds = array_values(array_unique(array_filter($productIds, function ($id) {
        return $id > 0;
    })));
    if (!$productIds) {
        throw new RuntimeException('Productos invalidos');
    }

    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    $stmt = $db->prepare("SELECT * FROM store_products WHERE active = 1 AND id IN ($placeholders)");
    $stmt->execute($productIds);
    $products = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $product) {
        $products[(int)$product['id']] = $product;
    }

    $orderItems = [];
    $total = 0;
    foreach ($items as $item) {
        $productId = (int)($item['id'] ?? 0);
        if (!isset($products[$productId])) {
            continue;
        }
        $qty = max(1, min(50, (int)($item['quantity'] ?? 1)));
        $size = trim((string)($item['size'] ?? ''));
        $product = $products[$productId];
        $line = $qty * (float)$product['price'];
        $total += $line;
        $orderItems[] = [
            'product_id' => $productId,
            'product_name' => $product['name'],
            'product_image' => $product['image'],
            'size' => $size,
            'quantity' => $qty,
            'unit_price' => (float)$product['price'],
            'line_total' => $line,
        ];
    }

    if (!$orderItems || $total <= 0) {
        throw new RuntimeException('No hay productos validos en el pedido');
    }

    $db->beginTransaction();
    $orderCode = store_order_code();
    $stmt = $db->prepare("INSERT INTO store_orders (order_code, customer_name, customer_email, customer_phone, customer_address, customer_note, payment_method, payment_receipt, subtotal, total, status) VALUES (:code, :name, :email, :phone, :address, :note, :method, :receipt, :subtotal, :total, 'receipt_received')");
    $stmt->execute([
        ':code' => $orderCode,
        ':name' => $name,
        ':email' => $email,
        ':phone' => $phone,
        ':address' => $address,
        ':note' => $note,
        ':method' => $method,
        ':receipt' => $receiptPublic,
        ':subtotal' => $total,
        ':total' => $total,
    ]);
    $orderId = (int)$db->lastInsertId();

    $stmt = $db->prepare("INSERT INTO store_order_items (order_id, product_id, product_name, product_image, size, quantity, unit_price, line_total) VALUES (:order_id, :product_id, :product_name, :product_image, :size, :quantity, :unit_price, :line_total)");
    foreach ($orderItems as $orderItem) {
        $orderItem['order_id'] = $orderId;
        $stmt->execute($orderItem);
    }

    $db->commit();
    store_notify_order($db, $orderId);

    echo json_encode(['success' => true, 'order_code' => $orderCode]);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
