<?php
require_once __DIR__ . '/../../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

function chat_db() {
    return getDB();
}

function chat_json($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function chat_input() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $_POST;
}

function chat_client_id($value) {
    $value = trim((string)$value);
    if (!preg_match('/^[a-zA-Z0-9_-]{12,80}$/', $value)) {
        chat_json(['success' => false, 'error' => 'Cliente invalido.'], 422);
    }
    return $value;
}

function chat_device_hash($value) {
    $value = trim((string)$value);
    if ($value === '') {
        return '';
    }
    return preg_match('/^[a-f0-9]{16,64}$/', $value) ? $value : '';
}

function chat_has_column($table, $column) {
    static $cache = [];
    $key = $table . '.' . $column;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    try {
        $stmt = chat_db()->prepare("SHOW COLUMNS FROM `$table` LIKE :column_name");
        $stmt->execute([':column_name' => $column]);
        $cache[$key] = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $cache[$key] = false;
    }

    return $cache[$key];
}

function chat_has_table($table) {
    static $cache = [];
    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }

    try {
        $stmt = chat_db()->prepare("SHOW TABLES LIKE :table_name");
        $stmt->execute([':table_name' => $table]);
        $cache[$table] = (bool)$stmt->fetch(PDO::FETCH_NUM);
    } catch (Exception $e) {
        $cache[$table] = false;
    }

    return $cache[$table];
}

function chat_clean_text($value, $max = 500) {
    $value = trim((string)$value);
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
    $value = preg_replace('/\s+/u', ' ', $value);
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($value, 'UTF-8') > $max) {
            $value = mb_substr($value, 0, $max, 'UTF-8');
        }
    } elseif (strlen($value) > $max) {
        $value = substr($value, 0, $max);
    }
    return $value;
}

function chat_clean_nickname($value) {
    $nickname = chat_clean_text($value, 40);
    $nickname = preg_replace('/[^\p{L}\p{N} _.-]/u', '', $nickname);
    $nickname = preg_replace('/\s+/u', ' ', $nickname);
    return trim($nickname);
}

function chat_nickname_is_valid($nickname) {
    $nickname = chat_clean_nickname($nickname);
    if ($nickname === '') {
        return false;
    }

    $length = function_exists('mb_strlen') ? mb_strlen($nickname, 'UTF-8') : strlen($nickname);
    if ($length < 3 || $length > 40) {
        return false;
    }

    if (!preg_match('/[\p{L}]/u', $nickname)) {
        return false;
    }

    return (bool)preg_match('/^[\p{L}\p{N}][\p{L}\p{N} _.-]*$/u', $nickname);
}

function chat_nickname_is_available($nickname, $excludeUserId = 0) {
    $nickname = chat_clean_nickname($nickname);
    if ($nickname === '') {
        return false;
    }

    $hasNicknameLocked = chat_has_column('chat_users', 'nickname_locked');
    $lockSql = $hasNicknameLocked ? "AND nickname_locked = 1" : "";
    $stmt = chat_db()->prepare(
        "SELECT id
         FROM chat_users
         WHERE LOWER(nickname) = LOWER(:nickname)
           {$lockSql}
           AND id <> :exclude_id
         LIMIT 1"
    );
    $stmt->execute([
        ':nickname' => $nickname,
        ':exclude_id' => max(0, (int)$excludeUserId)
    ]);

    return !$stmt->fetch(PDO::FETCH_ASSOC);
}

function chat_public_user($clientId, $nickname = '', $deviceHash = '', $lockRequestedNickname = true) {
    $db = chat_db();
    $requestedNickname = chat_clean_nickname($nickname);
    $deviceHash = chat_device_hash($deviceHash);
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $hasDeviceHash = chat_has_column('chat_users', 'device_hash');
    $hasNicknameLocked = chat_has_column('chat_users', 'nickname_locked');

    $stmt = $db->prepare("SELECT * FROM chat_users WHERE client_id = :client_id LIMIT 1");
    $stmt->execute([':client_id' => $clientId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user && $hasDeviceHash && $deviceHash !== '') {
        $stmt = $db->prepare("SELECT * FROM chat_users WHERE device_hash = :device_hash LIMIT 1");
        $stmt->execute([':device_hash' => $deviceHash]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$user && $ip) {
        $stmt = $db->prepare(
            "SELECT COUNT(*) AS total
             FROM chat_users
             WHERE last_ip = :ip
               AND created_at >= (NOW() - INTERVAL 24 HOUR)
             LIMIT 1"
        );
        $stmt->execute([':ip' => $ip]);
        $ipCount = (int)(($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0));

        if ($ipCount >= 3) {
            $stmt = $db->prepare(
                "SELECT * FROM chat_users
                 WHERE last_ip = :ip
                   AND created_at >= (NOW() - INTERVAL 24 HOUR)
                 ORDER BY last_seen DESC
                 LIMIT 1"
            );
            $stmt->execute([':ip' => $ip]);
            $recentIpUser = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($recentIpUser) {
                $user = $recentIpUser;
            }
        }
    }

    if ($user) {
        $currentNickname = chat_clean_nickname($user['nickname'] ?? '');
        $displayNickname = $currentNickname !== '' ? $currentNickname : 'Oyente';
        $isLocked = $hasNicknameLocked ? ((int)($user['nickname_locked'] ?? 0) === 1) : ($currentNickname !== '');
        $finalNickname = $currentNickname;
        $lockNickname = $isLocked ? 1 : 0;

        if (!$isLocked && $lockRequestedNickname && chat_nickname_is_valid($requestedNickname)) {
            $finalNickname = $requestedNickname;
            $lockNickname = 1;
            $displayNickname = $finalNickname;
        }

        $sql = "UPDATE chat_users SET client_id = :client_id, nickname = :nickname, last_ip = :ip, last_seen = NOW()";
        $params = [
            ':client_id' => $clientId,
            ':nickname' => $finalNickname,
            ':ip' => $ip,
            ':id' => $user['id']
        ];

        if ($hasNicknameLocked) {
            $sql .= ", nickname_locked = :nickname_locked";
            $params[':nickname_locked'] = $lockNickname;
        }

        if ($hasDeviceHash && $deviceHash !== '') {
            $sql .= ", device_hash = :device_hash";
            $params[':device_hash'] = $deviceHash;
        }

        $sql .= " WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        $user['client_id'] = $clientId;
        $user['nickname'] = $displayNickname;
        $user['nickname_locked'] = $lockNickname;
        $user['last_ip'] = $ip;
        if ($hasDeviceHash && $deviceHash !== '') {
            $user['device_hash'] = $deviceHash;
        }
        return $user;
    }

    $nickname = ($lockRequestedNickname && chat_nickname_is_valid($requestedNickname)) ? $requestedNickname : '';
    $nicknameLocked = $nickname !== '' ? 1 : 0;

    if ($hasDeviceHash) {
        $columns = "client_id, device_hash, nickname, last_ip";
        $values = ":client_id, :device_hash, :nickname, :ip";
        $params = [
            ':client_id' => $clientId,
            ':device_hash' => $deviceHash !== '' ? $deviceHash : null,
            ':nickname' => $nickname,
            ':ip' => $ip
        ];

        if ($hasNicknameLocked) {
            $columns .= ", nickname_locked";
            $values .= ", :nickname_locked";
            $params[':nickname_locked'] = $nicknameLocked;
        }

        $stmt = $db->prepare("INSERT INTO chat_users ($columns) VALUES ($values)");
        $stmt->execute($params);
    } else {
        $columns = "client_id, nickname, last_ip";
        $values = ":client_id, :nickname, :ip";
        $params = [
            ':client_id' => $clientId,
            ':nickname' => $nickname,
            ':ip' => $ip
        ];

        if ($hasNicknameLocked) {
            $columns .= ", nickname_locked";
            $values .= ", :nickname_locked";
            $params[':nickname_locked'] = $nicknameLocked;
        }

        $stmt = $db->prepare("INSERT INTO chat_users ($columns) VALUES ($values)");
        $stmt->execute($params);
    }

    return [
        'id' => (int)$db->lastInsertId(),
        'client_id' => $clientId,
        'nickname' => $nickname !== '' ? $nickname : 'Oyente',
        'nickname_locked' => $nicknameLocked,
        'role' => 'viewer',
        'is_banned' => 0
    ];
}

function chat_settings() {
    $db = chat_db();
    $rows = $db->query("SELECT setting_key, setting_value FROM chat_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    return [
        'enabled' => ($rows['enabled'] ?? '1') === '1',
        'rules' => $rows['rules'] ?? '',
        'welcome_message' => $rows['welcome_message'] ?? '',
        'pinned_announcement' => $rows['pinned_announcement'] ?? '',
        'live_title' => $rows['live_title'] ?? 'Ahora en vivo',
        'live_host' => $rows['live_host'] ?? '',
        'live_program' => $rows['live_program'] ?? ''
    ];
}

function chat_active_listeners() {
    try {
        $stmt = chat_db()->query(
            "SELECT COUNT(*) AS total
             FROM chat_users
             WHERE last_seen >= (NOW() - INTERVAL 2 MINUTE)"
        );
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($row['total'] ?? 0);
    } catch (Exception $e) {
        return 0;
    }
}

function chat_is_admin_session() {
    return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['superadmin', 'admin', 'chat_moderator'], true);
}

function chat_is_system_admin_session() {
    return isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['superadmin', 'admin'], true);
}

function chat_require_admin() {
    if (!chat_is_admin_session()) {
        chat_json(['success' => false, 'error' => 'Acceso denegado.'], 403);
    }
}

function chat_require_superadmin() {
    if (($_SESSION['user_role'] ?? '') !== 'superadmin') {
        chat_json(['success' => false, 'error' => 'Solo superadmin.'], 403);
    }
}

function chat_require_system_admin() {
    if (!chat_is_system_admin_session()) {
        chat_json(['success' => false, 'error' => 'Solo administradores del sistema.'], 403);
    }
}

function chat_badge($role) {
    if ($role === 'superadmin') {
        return 'crown';
    }
    if ($role === 'admin' || $role === 'chat_admin' || $role === 'chat_moderator') {
        return 'star';
    }
    return '';
}
?>
