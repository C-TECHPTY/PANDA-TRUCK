<?php
require_once __DIR__ . '/_bootstrap.php';

$clientId = chat_client_id($_GET['client_id'] ?? '');
$afterId = max(0, (int)($_GET['after_id'] ?? 0));
$user = chat_public_user($clientId, $_GET['nickname'] ?? '', $_GET['device_hash'] ?? '');

$db = chat_db();
$featuredSelect = chat_has_column('chat_messages', 'is_featured') ? 'm.is_featured' : '0 AS is_featured';
$stmt = $db->prepare(
    "SELECT m.id, m.nickname, COALESCE(cu.role, m.role) AS role, m.message, m.message_type, {$featuredSelect}, m.created_at,
            cu.client_id AS sender_client_id, ru.client_id AS recipient_client_id
     FROM chat_messages m
     LEFT JOIN chat_users cu ON cu.id = m.user_id
     LEFT JOIN chat_users ru ON ru.id = m.recipient_user_id
     WHERE m.id > :after_id
       AND m.is_deleted = 0
       AND (
         m.message_type IN ('public', 'system')
         OR (m.message_type = 'private' AND m.recipient_user_id = :user_id)
       )
     ORDER BY m.id ASC
     LIMIT 80"
);
$stmt->bindValue(':after_id', $afterId, PDO::PARAM_INT);
$stmt->bindValue(':user_id', (int)$user['id'], PDO::PARAM_INT);
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($messages as &$message) {
    $message['id'] = (int)$message['id'];
    $message['is_featured'] = (int)($message['is_featured'] ?? 0) === 1;
    $message['badge'] = chat_badge($message['role']);
    $message['is_private'] = $message['message_type'] === 'private';
}
unset($message);

chat_json(['success' => true, 'messages' => $messages, 'server_time' => date('c')]);
?>
