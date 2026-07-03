<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/push.php';

try {
    $db = getDB();
    panda_push_ensure_tables($db);
    $settings = panda_push_settings($db);

    echo json_encode([
        'enabled' => ($settings['push_enabled'] ?? '0') === '1',
        'publicKey' => $settings['push_vapid_public_key'] ?? '',
        'error' => empty($settings['push_vapid_public_key']) ? 'Faltan llaves push. Genera llaves VAPID en el dashboard.' : '',
    ]);
} catch (Throwable $e) {
    echo json_encode(['enabled' => false, 'publicKey' => '', 'error' => $e->getMessage()]);
}
?>
