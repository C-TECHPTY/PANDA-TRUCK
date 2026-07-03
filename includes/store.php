<?php
// includes/store.php - Souvenir store helpers.

require_once __DIR__ . '/config.php';

function store_ensure_tables(PDO $db)
{
    $db->exec("CREATE TABLE IF NOT EXISTS store_products (
        id INT(11) NOT NULL AUTO_INCREMENT,
        name VARCHAR(190) NOT NULL,
        description TEXT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        image TEXT NULL,
        sizes VARCHAR(255) NULL,
        stock INT(11) NOT NULL DEFAULT 0,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_store_products_active (active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS store_orders (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        order_code VARCHAR(30) NOT NULL,
        customer_name VARCHAR(190) NOT NULL,
        customer_email VARCHAR(190) NULL,
        customer_phone VARCHAR(60) NOT NULL,
        customer_address TEXT NULL,
        customer_note TEXT NULL,
        payment_method VARCHAR(40) NOT NULL,
        payment_receipt TEXT NULL,
        subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        status ENUM('pending','receipt_received','paid','delivered','cancelled') NOT NULL DEFAULT 'receipt_received',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uniq_store_orders_code (order_code),
        KEY idx_store_orders_status (status),
        KEY idx_store_orders_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db->exec("CREATE TABLE IF NOT EXISTS store_order_items (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        order_id BIGINT UNSIGNED NOT NULL,
        product_id INT(11) NOT NULL,
        product_name VARCHAR(190) NOT NULL,
        product_image TEXT NULL,
        size VARCHAR(60) NULL,
        quantity INT(11) NOT NULL DEFAULT 1,
        unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        line_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        PRIMARY KEY (id),
        KEY idx_store_order_items_order (order_id),
        KEY idx_store_order_items_product (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function store_settings(PDO $db)
{
    $settings = [
        'store_notify_email_1' => '',
        'store_notify_email_2' => '',
        'store_yappy_info' => '',
        'store_ach_info' => '',
        'store_paypal_email' => '',
        'store_paypal_url' => '',
    ];

    try {
        $keys = "'" . implode("','", array_keys($settings)) . "'";
        $stmt = $db->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ($keys)");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $settings[$row['setting_key']] = (string)$row['setting_value'];
        }
    } catch (Throwable $e) {
        // Keep defaults.
    }

    return $settings;
}

function store_absolute_url($path = '')
{
    $path = trim((string)$path);
    if ($path === '') {
        return rtrim(SITE_URL, '/') . '/assets/img/logo.png';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return rtrim(SITE_URL, '/') . '/' . ltrim($path, '/');
}

function store_money($value)
{
    return '$' . number_format((float)$value, 2);
}

function store_order_code()
{
    return 'PT-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
}

function store_order_status_label($status)
{
    $labels = [
        'pending' => 'Pendiente',
        'receipt_received' => 'Comprobante recibido',
        'paid' => 'Pagado',
        'delivered' => 'Entregado',
        'cancelled' => 'Cancelado',
    ];
    return $labels[$status] ?? $status;
}

function store_notify_order(PDO $db, $orderId)
{
    require_once __DIR__ . '/notifications.php';

    $settings = store_settings($db);
    $recipients = array_filter(array_unique([
        trim($settings['store_notify_email_1']),
        trim($settings['store_notify_email_2']),
    ]));
    if (!$recipients) {
        return;
    }

    $stmt = $db->prepare("SELECT * FROM store_orders WHERE id = :id");
    $stmt->execute([':id' => $orderId]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) {
        return;
    }

    $stmt = $db->prepare("SELECT * FROM store_order_items WHERE order_id = :id");
    $stmt->execute([':id' => $orderId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $rows = '';
    foreach ($items as $item) {
        $image = store_absolute_url($item['product_image'] ?? '');
        $rows .= '<tr>'
            . '<td style="padding:8px;border-bottom:1px solid #eee;"><img src="' . htmlspecialchars($image) . '" alt="" style="width:64px;height:64px;object-fit:cover;border-radius:8px;"></td>'
            . '<td style="padding:8px;border-bottom:1px solid #eee;">' . htmlspecialchars($item['product_name']) . '<br><small>Talla: ' . htmlspecialchars($item['size'] ?: '-') . '</small></td>'
            . '<td style="padding:8px;border-bottom:1px solid #eee;text-align:center;">' . (int)$item['quantity'] . '</td>'
            . '<td style="padding:8px;border-bottom:1px solid #eee;text-align:right;">' . store_money($item['line_total']) . '</td>'
            . '</tr>';
    }

    $receipt = $order['payment_receipt'] ? '<p><strong>Comprobante:</strong> <a href="' . htmlspecialchars(store_absolute_url($order['payment_receipt'])) . '">Ver archivo</a></p>' : '';
    $subject = 'Nuevo pedido souvenir ' . $order['order_code'];
    $html = '<div style="font-family:Arial,sans-serif;color:#222;">'
        . '<h2>Nuevo pedido en Panda Truck</h2>'
        . '<p><strong>Pedido:</strong> ' . htmlspecialchars($order['order_code']) . '</p>'
        . '<p><strong>Cliente:</strong> ' . htmlspecialchars($order['customer_name']) . '<br>'
        . '<strong>WhatsApp:</strong> ' . htmlspecialchars($order['customer_phone']) . '<br>'
        . '<strong>Email:</strong> ' . htmlspecialchars($order['customer_email'] ?: '-') . '<br>'
        . '<strong>Metodo de pago:</strong> ' . htmlspecialchars(strtoupper($order['payment_method'])) . '<br>'
        . '<strong>Entrega estimada:</strong> 8 dias habiles despues de confirmar el pago<br>'
        . '<strong>Estado:</strong> ' . htmlspecialchars(store_order_status_label($order['status'])) . '</p>'
        . '<p><strong>Direccion de entrega/nota:</strong><br>' . nl2br(htmlspecialchars(trim(($order['customer_address'] ?? '') . "\n" . ($order['customer_note'] ?? '')))) . '</p>'
        . $receipt
        . '<table style="width:100%;border-collapse:collapse;"><thead><tr><th></th><th style="text-align:left;">Producto</th><th>Cant.</th><th style="text-align:right;">Total</th></tr></thead><tbody>' . $rows . '</tbody></table>'
        . '<h3 style="text-align:right;">Total: ' . store_money($order['total']) . '</h3>'
        . '</div>';

    foreach ($recipients as $recipient) {
        $error = null;
        notification_send_mail($recipient, $subject, $html, $error);
    }
}
?>
