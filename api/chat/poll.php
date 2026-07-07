<?php
require_once __DIR__ . '/_bootstrap.php';

if (!chat_has_table('chat_polls') || !chat_has_table('chat_poll_options') || !chat_has_table('chat_poll_votes')) {
    chat_json(['success' => true, 'poll' => null]);
}

$db = chat_db();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

function chat_poll_payload($db, $userId = 0, $deviceHash = '') {
    $stmt = $db->query(
        "SELECT id, question, created_at
         FROM chat_polls
         WHERE active = 1
         ORDER BY id DESC
         LIMIT 1"
    );
    $poll = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$poll) {
        return ['success' => true, 'poll' => null];
    }

    $stmt = $db->prepare(
        "SELECT o.id, o.option_text, COUNT(v.id) AS votes
         FROM chat_poll_options o
         LEFT JOIN chat_poll_votes v ON v.option_id = o.id
         WHERE o.poll_id = :poll_id
         GROUP BY o.id, o.option_text, o.position
         ORDER BY o.position ASC, o.id ASC"
    );
    $stmt->execute([':poll_id' => (int)$poll['id']]);
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalVotes = 0;
    foreach ($options as &$option) {
        $option['id'] = (int)$option['id'];
        $option['votes'] = (int)$option['votes'];
        $totalVotes += $option['votes'];
    }
    unset($option);

    $voteOptionId = null;
    if ($userId > 0 || $deviceHash !== '') {
        $stmt = $db->prepare(
            "SELECT option_id
             FROM chat_poll_votes
             WHERE poll_id = :poll_id
               AND ((user_id = :user_id AND :user_id > 0) OR (device_hash = :device_hash AND :device_hash <> ''))
             ORDER BY id ASC
             LIMIT 1"
        );
        $stmt->execute([
            ':poll_id' => (int)$poll['id'],
            ':user_id' => $userId,
            ':device_hash' => $deviceHash
        ]);
        $vote = $stmt->fetch(PDO::FETCH_ASSOC);
        $voteOptionId = $vote ? (int)$vote['option_id'] : null;
    }

    return [
        'success' => true,
        'poll' => [
            'id' => (int)$poll['id'],
            'question' => $poll['question'],
            'options' => $options,
            'total_votes' => $totalVotes,
            'voted_option_id' => $voteOptionId
        ]
    ];
}

if ($method === 'POST') {
    $data = chat_input();
    $clientId = chat_client_id($data['client_id'] ?? '');
    $deviceHash = chat_device_hash($data['device_hash'] ?? '');
    $user = chat_public_user($clientId, $data['nickname'] ?? '', $deviceHash, false);
    if ((int)$user['is_banned'] === 1) {
        chat_json(['success' => false, 'error' => 'No puedes votar en esta sala.'], 403);
    }

    $optionId = (int)($data['option_id'] ?? 0);
    if ($optionId <= 0) {
        chat_json(['success' => false, 'error' => 'Opcion invalida.'], 422);
    }

    $stmt = $db->prepare(
        "SELECT p.id AS poll_id, o.id AS option_id
         FROM chat_poll_options o
         INNER JOIN chat_polls p ON p.id = o.poll_id
         WHERE o.id = :option_id AND p.active = 1
         LIMIT 1"
    );
    $stmt->execute([':option_id' => $optionId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        chat_json(['success' => false, 'error' => 'Encuesta cerrada o inexistente.'], 404);
    }

    $pollId = (int)$row['poll_id'];
    $stmt = $db->prepare(
        "SELECT id
         FROM chat_poll_votes
         WHERE poll_id = :poll_id
           AND ((user_id = :user_id AND :user_id > 0) OR (device_hash = :device_hash AND :device_hash <> ''))
         LIMIT 1"
    );
    $stmt->execute([
        ':poll_id' => $pollId,
        ':user_id' => (int)$user['id'],
        ':device_hash' => $deviceHash
    ]);
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        $stmt = $db->prepare(
            "INSERT INTO chat_poll_votes (poll_id, option_id, user_id, device_hash, client_id)
             VALUES (:poll_id, :option_id, :user_id, :device_hash, :client_id)"
        );
        $stmt->execute([
            ':poll_id' => $pollId,
            ':option_id' => $optionId,
            ':user_id' => (int)$user['id'],
            ':device_hash' => $deviceHash !== '' ? $deviceHash : null,
            ':client_id' => $clientId
        ]);
    }

    chat_json(chat_poll_payload($db, (int)$user['id'], $deviceHash));
}

$clientId = $_GET['client_id'] ?? '';
$deviceHash = chat_device_hash($_GET['device_hash'] ?? '');
$userId = 0;
if ($clientId !== '' && preg_match('/^[a-zA-Z0-9_-]{12,80}$/', $clientId)) {
    $stmt = $db->prepare("SELECT id FROM chat_users WHERE client_id = :client_id OR (device_hash = :device_hash AND :device_hash <> '') LIMIT 1");
    $stmt->execute([':client_id' => $clientId, ':device_hash' => $deviceHash]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $userId = $user ? (int)$user['id'] : 0;
}

chat_json(chat_poll_payload($db, $userId, $deviceHash));
?>
