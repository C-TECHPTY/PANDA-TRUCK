<?php
require_once __DIR__ . '/_bootstrap.php';

$allowed = [
    '❤️', '🔥', '👏', '🎶', '😂', '😍', '👍', '😭', '🙌', '🥶', '🤔', '😜',
    '🧠', '👉', '⚠️', '😘', '🥺', '🤤', '🤢', '🐸', '🫡', '🤫', '👀', '💪',
    '🤣', '✌️', '😱', '🤐', '😕', '🌲', '🎄', '😀', '😃', '😄', '😁', '😆',
    '😇', '🙂', '😉', '😊', '😎', '🤓', '🥳', '💯', '🚀', '⭐', '👑', '💃',
    '🕺', '🎧', '📻', '🔊'
];
$data = chat_input();
$clientId = chat_client_id($data['client_id'] ?? '');
$deviceHash = $data['device_hash'] ?? '';
$reaction = (string)($data['reaction'] ?? '');

if (!in_array($reaction, $allowed, true)) {
    chat_json(['success' => false, 'error' => 'Reaccion invalida.'], 422);
}

$user = chat_public_user($clientId, $data['nickname'] ?? '', $deviceHash, false);
if ((int)$user['is_banned'] === 1) {
    chat_json(['success' => false, 'error' => 'No puedes reaccionar en esta sala.'], 403);
}

$db = chat_db();
$stmt = $db->prepare("INSERT INTO chat_reactions (user_id, reaction) VALUES (:user_id, :reaction)");
$stmt->execute([':user_id' => (int)$user['id'], ':reaction' => $reaction]);

chat_json(['success' => true, 'id' => (int)$db->lastInsertId()]);
?>
