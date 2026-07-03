<?php
// api/app_djs.php - Lista limpia de DJs para la app Android nativa.
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/config.php';

function panda_app_abs_url($path) {
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return SITE_URL . ltrim(str_replace('../', '', $path), '/');
}

try {
    $db = getDB();
    $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 80;
    $stmt = $db->prepare("
        SELECT
            d.id, d.name, d.genre, d.city, d.avatar,
            COALESCE(SUM(s.plays), 0) AS total_plays,
            COALESCE(SUM(s.downloads), 0) AS total_downloads,
            COUNT(DISTINCT m.id) AS total_mixes
        FROM djs d
        LEFT JOIN mixes m ON d.name = m.dj AND m.active = 1
        LEFT JOIN statistics s ON m.id = s.item_id AND s.item_type = 'mix'
        WHERE d.active = 1
        GROUP BY d.id
        HAVING total_mixes > 0
        ORDER BY total_downloads DESC, total_mixes DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $djs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($djs as &$dj) {
        $dj['id'] = (int)$dj['id'];
        $dj['total_plays'] = (int)($dj['total_plays'] ?? 0);
        $dj['total_downloads'] = (int)($dj['total_downloads'] ?? 0);
        $dj['total_mixes'] = (int)($dj['total_mixes'] ?? 0);
        $dj['avatar_url'] = panda_app_abs_url($dj['avatar'] ?: 'assets/img/default-avatar.jpg');
        unset($dj['avatar']);
    }
    unset($dj);

    echo json_encode(['success' => true, 'djs' => $djs], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'djs' => [], 'error' => 'No se pudieron cargar DJs.']);
}
?>
