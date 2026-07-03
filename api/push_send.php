<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/push.php';

global $auth;

if (!$auth->isLoggedIn() || !in_array($_SESSION['user_role'] ?? '', ['superadmin', 'admin', 'chat_moderator'], true)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

try {
    $action = $_GET['action'] ?? 'send';
    $db = getDB();
    panda_push_ensure_tables($db);

    if ($action === 'status') {
        $settings = panda_push_settings($db);
        $active = (int)$db->query("SELECT COUNT(*) FROM push_subscriptions WHERE active = 1")->fetchColumn();
        echo json_encode([
            'success' => true,
            'enabled' => ($settings['push_enabled'] ?? '0') === '1',
            'hasKeys' => !empty($settings['push_vapid_public_key']) && !empty($settings['push_vapid_private_key']),
            'publicKey' => $settings['push_vapid_public_key'] ?? '',
            'activeSubscriptions' => $active,
        ]);
        exit;
    }

    if ($action === 'generate_keys') {
        if (!in_array($_SESSION['user_role'] ?? '', ['superadmin', 'admin'], true)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Solo administradores pueden generar llaves.']);
            exit;
        }
        $keys = panda_push_generate_vapid_keys($db);
        echo json_encode(['success' => true, 'publicKey' => $keys['publicKey']]);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $title = trim((string)($input['title'] ?? 'Panda Truck Reloaded'));
    $body = trim((string)($input['body'] ?? 'Nuevo aviso en la plataforma'));
    $url = trim((string)($input['url'] ?? SITE_URL . 'index.php#radio'));

    if ($title === '' || $body === '') {
        throw new RuntimeException('Titulo y mensaje son requeridos.');
    }

    if ($url !== '' && !preg_match('#^https?://#i', $url)) {
        $url = SITE_URL . ltrim($url, '/');
    }

    $result = panda_push_broadcast([
        'title' => $title,
        'body' => $body,
        'url' => $url ?: SITE_URL . 'index.php#radio',
        'icon' => '/assets/img/android-chrome-192x192.png',
        'badge' => '/assets/img/favicon-32x32.png',
        'tag' => 'panda-truck-live',
        'vibrate' => [300, 120, 300, 120, 300],
        'requireInteraction' => true,
        'actions' => [
            ['action' => 'open-radio', 'title' => 'Abrir radio'],
        ],
    ]);

    echo json_encode($result);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
