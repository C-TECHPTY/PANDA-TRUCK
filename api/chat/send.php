<?php
require_once __DIR__ . '/_bootstrap.php';

$settings = chat_settings();
if (!$settings['enabled']) {
    chat_json(['success' => false, 'error' => 'El chat esta desactivado por el momento.'], 423);
}

$data = chat_input();
$clientId = chat_client_id($data['client_id'] ?? '');
$nickname = $data['nickname'] ?? '';
$deviceHash = $data['device_hash'] ?? '';
$message = chat_clean_text($data['message'] ?? '', 500);

if ($message === '') {
    chat_json(['success' => false, 'error' => 'Escribe un mensaje.'], 422);
}

$user = chat_public_user($clientId, $nickname, $deviceHash);
if ((int)$user['is_banned'] === 1) {
    chat_json(['success' => false, 'error' => $user['banned_reason'] ?: 'No puedes escribir en esta sala.'], 403);
}

$db = chat_db();
$stmt = $db->prepare(
    "INSERT INTO chat_messages (user_id, nickname, role, message, message_type)
     VALUES (:user_id, :nickname, :role, :message, 'public')"
);
$stmt->execute([
    ':user_id' => (int)$user['id'],
    ':nickname' => $user['nickname'],
    ':role' => $user['role'],
    ':message' => $message
]);

chat_json(['success' => true, 'id' => (int)$db->lastInsertId()]);
?>
