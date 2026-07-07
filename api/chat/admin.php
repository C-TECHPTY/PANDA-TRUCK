<?php
require_once __DIR__ . '/_bootstrap.php';

chat_require_admin();

$db = chat_db();
$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$data = chat_input();

if ($action === 'overview') {
    $users = $db->query(
        "SELECT id, client_id, nickname, role, is_banned, banned_reason, last_ip, last_seen
         FROM chat_users
         ORDER BY last_seen DESC
         LIMIT 120"
    )->fetchAll(PDO::FETCH_ASSOC);

    $featuredSelect = chat_has_column('chat_messages', 'is_featured') ? 'm.is_featured' : '0 AS is_featured';
    $messages = $db->query(
        "SELECT m.id, m.user_id, m.nickname, COALESCE(u.role, m.role) AS role, m.message, m.message_type, m.is_deleted, {$featuredSelect}, m.created_at
         FROM chat_messages m
         LEFT JOIN chat_users u ON u.id = m.user_id
         WHERE is_deleted = 0
         ORDER BY m.id DESC
         LIMIT 120"
    )->fetchAll(PDO::FETCH_ASSOC);

    $reactionTotals = $db->query(
        "SELECT reaction, COUNT(*) AS total
         FROM chat_reactions
         GROUP BY reaction
         ORDER BY total DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $reactionToday = $db->query(
        "SELECT reaction, COUNT(*) AS total
         FROM chat_reactions
         WHERE DATE(created_at) = CURDATE()
         GROUP BY reaction
         ORDER BY total DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $activePoll = null;
    if (chat_has_table('chat_polls') && chat_has_table('chat_poll_options') && chat_has_table('chat_poll_votes')) {
        $pollRow = $db->query("SELECT id, question FROM chat_polls WHERE active = 1 ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($pollRow) {
            $stmt = $db->prepare(
                "SELECT o.id, o.option_text, COUNT(v.id) AS votes
                 FROM chat_poll_options o
                 LEFT JOIN chat_poll_votes v ON v.option_id = o.id
                 WHERE o.poll_id = :poll_id
                 GROUP BY o.id, o.option_text, o.position
                 ORDER BY o.position ASC, o.id ASC"
            );
            $stmt->execute([':poll_id' => (int)$pollRow['id']]);
            $activePoll = [
                'id' => (int)$pollRow['id'],
                'question' => $pollRow['question'],
                'options' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        }
    }

    chat_json([
        'success' => true,
        'settings' => chat_settings(),
        'users' => $users,
        'messages' => $messages,
        'active_listeners' => chat_active_listeners(),
        'active_poll' => $activePoll,
        'reaction_totals' => $reactionTotals,
        'reaction_today' => $reactionToday,
        'session_role' => $_SESSION['user_role'] ?? ''
    ]);
}

if ($action === 'delete_message') {
    $id = (int)($data['id'] ?? 0);
    $stmt = $db->prepare("UPDATE chat_messages SET is_deleted = 1 WHERE id = :id");
    $stmt->execute([':id' => $id]);
    chat_json(['success' => true]);
}

if ($action === 'feature_message') {
    chat_require_admin();
    if (!chat_has_column('chat_messages', 'is_featured')) {
        chat_json(['success' => false, 'error' => 'Falta ejecutar migrations/live_chat_engagement.sql.'], 422);
    }
    $id = (int)($data['id'] ?? 0);
    $featured = !empty($data['featured']) ? 1 : 0;
    $stmt = $db->prepare("UPDATE chat_messages SET is_featured = :featured WHERE id = :id AND is_deleted = 0");
    $stmt->execute([':featured' => $featured, ':id' => $id]);
    chat_json(['success' => true]);
}

if ($action === 'clear_messages') {
    chat_require_superadmin();
    $stmt = $db->prepare("UPDATE chat_messages SET is_deleted = 1 WHERE is_deleted = 0");
    $stmt->execute();
    chat_json(['success' => true, 'deleted' => $stmt->rowCount()]);
}

if ($action === 'reset_nicknames') {
    chat_require_superadmin();
    if (!chat_has_column('chat_users', 'nickname_locked')) {
        chat_json(['success' => false, 'error' => 'Falta ejecutar migrations/live_chat_lock_nickname.sql.'], 422);
    }

    $stmt = $db->prepare(
        "UPDATE chat_users
         SET nickname = '', nickname_locked = 0
         WHERE nickname_locked = 1 OR nickname <> ''"
    );
    $stmt->execute();
    chat_json(['success' => true, 'updated' => $stmt->rowCount()]);
}

if ($action === 'rename_user') {
    chat_require_system_admin();
    $id = (int)($data['id'] ?? 0);
    $nickname = chat_clean_nickname($data['nickname'] ?? '');

    if ($id <= 0 || !chat_nickname_is_valid($nickname)) {
        chat_json(['success' => false, 'error' => 'El nick debe tener de 3 a 40 caracteres e incluir letras.'], 422);
    }

    $lockSql = chat_has_column('chat_users', 'nickname_locked') ? ", nickname_locked = 1" : "";
    $stmt = $db->prepare("UPDATE chat_users SET nickname = :nickname{$lockSql} WHERE id = :id");
    $stmt->execute([':nickname' => $nickname, ':id' => $id]);

    $stmt = $db->prepare("UPDATE chat_messages SET nickname = :nickname WHERE user_id = :id");
    $stmt->execute([':nickname' => $nickname, ':id' => $id]);

    chat_json(['success' => true]);
}

if ($action === 'ban_user' || $action === 'unban_user') {
    $id = (int)($data['id'] ?? 0);
    $reason = chat_clean_text($data['reason'] ?? 'Moderado por administracion.', 180);
    $banned = $action === 'ban_user' ? 1 : 0;
    $stmt = $db->prepare("UPDATE chat_users SET is_banned = :banned, banned_reason = :reason WHERE id = :id");
    $stmt->execute([
        ':banned' => $banned,
        ':reason' => $banned ? $reason : null,
        ':id' => $id
    ]);
    chat_json(['success' => true]);
}

if ($action === 'set_role') {
    chat_require_superadmin();
    $id = (int)($data['id'] ?? 0);
    $role = ($data['role'] ?? '') === 'chat_admin' ? 'chat_admin' : 'viewer';
    $stmt = $db->prepare("UPDATE chat_users SET role = :role WHERE id = :id");
    $stmt->execute([':role' => $role, ':id' => $id]);

    $stmt = $db->prepare("UPDATE chat_messages SET role = :role WHERE user_id = :id AND message_type = 'public'");
    $stmt->execute([':role' => $role, ':id' => $id]);

    chat_json(['success' => true]);
}

if ($action === 'private_message') {
    chat_require_superadmin();
    $recipientId = (int)($data['recipient_id'] ?? 0);
    $message = chat_clean_text($data['message'] ?? '', 500);
    if ($recipientId <= 0 || $message === '') {
        chat_json(['success' => false, 'error' => 'Falta destinatario o mensaje.'], 422);
    }

    $nickname = $_SESSION['username'] ?? 'Super Admin';
    $stmt = $db->prepare(
        "INSERT INTO chat_messages (admin_user_id, recipient_user_id, nickname, role, message, message_type)
         VALUES (:admin_user_id, :recipient_user_id, :nickname, :role, :message, 'private')"
    );
    $stmt->execute([
        ':admin_user_id' => $_SESSION['user_id'] ?? null,
        ':recipient_user_id' => $recipientId,
        ':nickname' => $nickname,
        ':role' => $_SESSION['user_role'] ?? 'superadmin',
        ':message' => $message
    ]);
    chat_json(['success' => true]);
}

if ($action === 'admin_public_message') {
    chat_require_admin();
    $message = chat_clean_text($data['message'] ?? '', 500);
    if ($message === '') {
        chat_json(['success' => false, 'error' => 'Escribe un mensaje.'], 422);
    }

    $nickname = chat_clean_text($_SESSION['username'] ?? '', 40);
    $sessionRole = $_SESSION['user_role'] ?? '';
    if ($nickname === '') {
        $nickname = $sessionRole === 'superadmin' ? 'Super Admin' : ($sessionRole === 'chat_moderator' ? 'Moderador' : 'Admin');
    }

    $role = $sessionRole === 'superadmin' ? 'superadmin' : ($sessionRole === 'chat_moderator' ? 'chat_moderator' : 'admin');
    $stmt = $db->prepare(
        "INSERT INTO chat_messages (admin_user_id, nickname, role, message, message_type)
         VALUES (:admin_user_id, :nickname, :role, :message, 'public')"
    );
    $stmt->execute([
        ':admin_user_id' => $_SESSION['user_id'] ?? null,
        ':nickname' => $nickname,
        ':role' => $role,
        ':message' => $message
    ]);

    chat_json(['success' => true, 'id' => (int)$db->lastInsertId()]);
}

if ($action === 'save_settings') {
    chat_require_superadmin();
    $enabled = !empty($data['enabled']) ? '1' : '0';
    $rules = chat_clean_text($data['rules'] ?? '', 1200);
    $welcome = chat_clean_text($data['welcome_message'] ?? '', 300);
    $announcement = chat_clean_text($data['pinned_announcement'] ?? '', 500);
    $liveTitle = chat_clean_text($data['live_title'] ?? 'Ahora en vivo', 120);
    $liveHost = chat_clean_text($data['live_host'] ?? '', 120);
    $liveProgram = chat_clean_text($data['live_program'] ?? '', 120);
    $stmt = $db->prepare(
        "INSERT INTO chat_settings (setting_key, setting_value) VALUES (:key_name, :value)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
    );
    foreach ([
        'enabled' => $enabled,
        'rules' => $rules,
        'welcome_message' => $welcome,
        'pinned_announcement' => $announcement,
        'live_title' => $liveTitle,
        'live_host' => $liveHost,
        'live_program' => $liveProgram
    ] as $key => $value) {
        $stmt->execute([':key_name' => $key, ':value' => $value]);
    }
    chat_json(['success' => true]);
}

if ($action === 'create_poll') {
    chat_require_admin();
    if (!chat_has_table('chat_polls') || !chat_has_table('chat_poll_options')) {
        chat_json(['success' => false, 'error' => 'Falta ejecutar migrations/live_chat_engagement.sql.'], 422);
    }

    $question = chat_clean_text($data['question'] ?? '', 180);
    $options = $data['options'] ?? [];
    if (!is_array($options)) {
        $options = [];
    }
    $cleanOptions = [];
    foreach ($options as $option) {
        $option = chat_clean_text($option, 80);
        if ($option !== '' && !in_array($option, $cleanOptions, true)) {
            $cleanOptions[] = $option;
        }
    }

    if ($question === '' || count($cleanOptions) < 2) {
        chat_json(['success' => false, 'error' => 'La encuesta necesita pregunta y minimo 2 opciones.'], 422);
    }

    $db->beginTransaction();
    $db->exec("UPDATE chat_polls SET active = 0, closed_at = NOW() WHERE active = 1");
    $stmt = $db->prepare("INSERT INTO chat_polls (question, active, created_by) VALUES (:question, 1, :created_by)");
    $stmt->execute([':question' => $question, ':created_by' => $_SESSION['user_id'] ?? null]);
    $pollId = (int)$db->lastInsertId();
    $stmt = $db->prepare("INSERT INTO chat_poll_options (poll_id, option_text, position) VALUES (:poll_id, :option_text, :position)");
    foreach ($cleanOptions as $index => $option) {
        $stmt->execute([':poll_id' => $pollId, ':option_text' => $option, ':position' => $index + 1]);
    }
    $db->commit();
    chat_json(['success' => true, 'poll_id' => $pollId]);
}

if ($action === 'close_poll') {
    chat_require_admin();
    if (!chat_has_table('chat_polls')) {
        chat_json(['success' => true]);
    }
    $stmt = $db->prepare("UPDATE chat_polls SET active = 0, closed_at = NOW() WHERE active = 1");
    $stmt->execute();
    chat_json(['success' => true]);
}

chat_json(['success' => false, 'error' => 'Accion invalida.'], 400);
?>
