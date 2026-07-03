<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/push.php';

try {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!$payload || empty($payload['endpoint']) || empty($payload['keys']['p256dh']) || empty($payload['keys']['auth'])) {
        throw new RuntimeException('Suscripcion push incompleta.');
    }

    $db = getDB();
    panda_push_ensure_tables($db);

    $endpoint = (string)$payload['endpoint'];
    $stmt = $db->prepare("
        INSERT INTO push_subscriptions (endpoint, endpoint_hash, p256dh, auth, user_agent, ip_address, active, last_error)
        VALUES (:endpoint, :endpoint_hash, :p256dh, :auth, :user_agent, :ip_address, 1, NULL)
        ON DUPLICATE KEY UPDATE
            endpoint = :endpoint,
            p256dh = :p256dh,
            auth = :auth,
            user_agent = :user_agent,
            ip_address = :ip_address,
            active = 1,
            last_error = NULL,
            updated_at = NOW()
    ");
    $stmt->execute([
        ':endpoint' => $endpoint,
        ':endpoint_hash' => hash('sha256', $endpoint),
        ':p256dh' => (string)$payload['keys']['p256dh'],
        ':auth' => (string)$payload['keys']['auth'],
        ':user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 1000),
        ':ip_address' => substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 64),
    ]);

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
