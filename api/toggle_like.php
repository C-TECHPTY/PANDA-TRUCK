<?php
// api/toggle_like.php - Like/unlike mixes and return shared like counts.
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';

function likesVisitorHash() {
    $cookieName = 'pt_like_visitor';
    $visitorId = $_COOKIE[$cookieName] ?? '';

    if (!preg_match('/^[a-f0-9]{32}$/', $visitorId)) {
        $visitorId = bin2hex(random_bytes(16));
        setcookie($cookieName, $visitorId, [
            'expires' => time() + (86400 * 365),
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    return hash('sha256', $visitorId);
}

function ensureMixLikesTable(PDO $db) {
    $db->exec("
        CREATE TABLE IF NOT EXISTS mix_likes (
            id INT(11) NOT NULL AUTO_INCREMENT,
            mix_id INT(11) NOT NULL,
            visitor_hash VARCHAR(64) NOT NULL,
            created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_mix_visitor (mix_id, visitor_hash),
            KEY idx_mix_likes_mix_id (mix_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function likeCounts(PDO $db, array $ids, $visitorHash) {
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), function ($id) {
        return $id > 0;
    })));
    if (!$ids) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("
        SELECT
            m.id,
            COUNT(ml.id) AS likes,
            MAX(CASE WHEN ml.visitor_hash = ? THEN 1 ELSE 0 END) AS liked
        FROM mixes m
        LEFT JOIN mix_likes ml ON ml.mix_id = m.id
        WHERE m.id IN ($placeholders) AND m.active = 1
        GROUP BY m.id
    ");
    $stmt->execute(array_merge([$visitorHash], $ids));

    $result = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $result[(int)$row['id']] = [
            'likes' => (int)$row['likes'],
            'liked' => ((int)$row['liked']) === 1,
        ];
    }

    return $result;
}

try {
    $db = getDB();
    ensureMixLikesTable($db);
    $visitorHash = likesVisitorHash();
    $payload = json_decode(file_get_contents('php://input'), true);
    $payload = is_array($payload) ? $payload : [];
    $action = $payload['action'] ?? ($_GET['action'] ?? 'status');

    if ($action === 'toggle') {
        $mixId = (int)($payload['mix_id'] ?? 0);
        if ($mixId <= 0) {
            throw new RuntimeException('Mix invalido');
        }

        $stmt = $db->prepare("SELECT id FROM mixes WHERE id = :id AND active = 1");
        $stmt->execute([':id' => $mixId]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new RuntimeException('Mix no encontrado');
        }

        $stmt = $db->prepare("SELECT id FROM mix_likes WHERE mix_id = :mix_id AND visitor_hash = :visitor_hash");
        $stmt->execute([':mix_id' => $mixId, ':visitor_hash' => $visitorHash]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $stmt = $db->prepare("DELETE FROM mix_likes WHERE id = :id");
            $stmt->execute([':id' => $existing['id']]);
            $liked = false;
        } else {
            $stmt = $db->prepare("INSERT INTO mix_likes (mix_id, visitor_hash) VALUES (:mix_id, :visitor_hash)");
            $stmt->execute([':mix_id' => $mixId, ':visitor_hash' => $visitorHash]);
            $liked = true;
        }

        $counts = likeCounts($db, [$mixId], $visitorHash);
        echo json_encode([
            'success' => true,
            'mix_id' => $mixId,
            'likes' => $counts[$mixId]['likes'] ?? 0,
            'liked' => $liked,
        ]);
        exit;
    }

    $ids = $payload['ids'] ?? ($_GET['ids'] ?? []);
    if (is_string($ids)) {
        $ids = explode(',', $ids);
    }

    echo json_encode([
        'success' => true,
        'mixes' => likeCounts($db, is_array($ids) ? $ids : [], $visitorHash),
    ]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
    ]);
}
?>
