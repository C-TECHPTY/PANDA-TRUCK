<?php
// api/app_notifications.php - Ultimos avisos para la app Android nativa.
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/push.php';

try {
    $db = getDB();
    panda_push_ensure_tables($db);

    $lastId = isset($_GET['last_id']) ? max(0, (int)$_GET['last_id']) : 0;
    $stmt = $db->prepare("
        SELECT id, title, body, target_url, created_at
        FROM push_notifications_log
        WHERE id > :last_id
        ORDER BY id ASC
        LIMIT 10
    ");
    $stmt->bindValue(':last_id', $lastId, PDO::PARAM_INT);
    $stmt->execute();

    echo json_encode([
        'success' => true,
        'notifications' => $stmt->fetchAll(PDO::FETCH_ASSOC),
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'notifications' => [],
    ]);
}
?>
