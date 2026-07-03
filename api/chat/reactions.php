<?php
require_once __DIR__ . '/_bootstrap.php';

$db = chat_db();

if (isset($_GET['latest'])) {
    $stmt = $db->query("SELECT COALESCE(MAX(id), 0) AS last_id FROM chat_reactions");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    chat_json(['success' => true, 'last_id' => (int)($row['last_id'] ?? 0)]);
}

$afterId = max(0, (int)($_GET['after_id'] ?? 0));

$stmt = $db->prepare(
    "SELECT r.id, r.reaction, r.created_at, u.client_id AS sender_client_id, u.nickname
     FROM chat_reactions r
     LEFT JOIN chat_users u ON u.id = r.user_id
     WHERE r.id > :after_id
     ORDER BY r.id ASC
     LIMIT 80"
);
$stmt->bindValue(':after_id', $afterId, PDO::PARAM_INT);
$stmt->execute();
$reactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($reactions as &$reaction) {
    $reaction['id'] = (int)$reaction['id'];
}
unset($reaction);

chat_json(['success' => true, 'reactions' => $reactions]);
?>
