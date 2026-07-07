<?php
require_once __DIR__ . '/_bootstrap.php';

$data = chat_input();
$clientId = chat_client_id($data['client_id'] ?? '');
$deviceHash = $data['device_hash'] ?? '';
$nickname = chat_clean_nickname($data['nickname'] ?? '');

if (!chat_nickname_is_valid($nickname)) {
    chat_json([
        'success' => false,
        'error' => 'El nick debe tener de 3 a 40 caracteres, incluir letras y empezar con letra o numero.'
    ], 422);
}

$currentUser = chat_public_user($clientId, '', $deviceHash, false);
if (!chat_nickname_is_available($nickname, (int)$currentUser['id'])) {
    chat_json([
        'success' => false,
        'error' => 'Ese nick ya esta registrado. Elige otro nombre para participar.'
    ], 409);
}

$user = chat_public_user($clientId, $nickname, $deviceHash, true);

chat_json([
    'success' => true,
    'user' => [
        'client_id' => $user['client_id'],
        'nickname' => $user['nickname'],
        'nickname_locked' => (int)($user['nickname_locked'] ?? 0) === 1,
        'role' => $user['role'],
        'badge' => chat_badge($user['role']),
        'is_banned' => (int)$user['is_banned'] === 1,
        'banned_reason' => $user['banned_reason'] ?? ''
    ]
]);
?>
