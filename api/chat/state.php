<?php
require_once __DIR__ . '/_bootstrap.php';

$data = chat_input();
$clientId = chat_client_id($data['client_id'] ?? ($_GET['client_id'] ?? ''));
$nickname = $data['nickname'] ?? ($_GET['nickname'] ?? '');
$deviceHash = $data['device_hash'] ?? ($_GET['device_hash'] ?? '');
$user = chat_public_user($clientId, $nickname, $deviceHash);
$settings = chat_settings();

chat_json([
    'success' => true,
    'settings' => $settings,
    'active_listeners' => chat_active_listeners(),
    'user' => [
        'client_id' => $user['client_id'],
        'nickname' => $user['nickname'],
        'nickname_locked' => (int)($user['nickname_locked'] ?? 0) === 1,
        'role' => $user['role'],
        'badge' => chat_badge($user['role']),
        'is_banned' => (int)$user['is_banned'] === 1,
        'banned_reason' => $user['banned_reason'] ?? ''
    ],
    'is_admin_session' => chat_is_admin_session(),
    'session_role' => $_SESSION['user_role'] ?? ''
]);
?>
