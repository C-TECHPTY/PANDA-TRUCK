<?php
// api/get_stats.php - Estadísticas en tiempo real
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';

$db = getDB();

// Estadísticas generales de mixes
$stmt = $db->query("SELECT COUNT(*) as total FROM mixes WHERE active = 1");
$total_mixes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM djs WHERE active = 1");
$total_djs = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $db->query("SELECT SUM(downloads) as total FROM statistics WHERE item_type = 'mix'");
$total_downloads = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$stmt = $db->query("SELECT SUM(plays) as total FROM statistics WHERE item_type = 'mix'");
$total_plays = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Estadísticas de videos
$stmt = $db->query("SELECT COUNT(*) as total FROM videos WHERE active = 1");
$total_videos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// SUPER PACKS - CORREGIDO
// Obtener DJs con 4 o más mixes activos
$stmt = $db->query("
    SELECT 
        dj, 
        COUNT(*) as mix_count, 
        MAX(date) as last_mix_date 
    FROM mixes 
    WHERE active = 1 
    GROUP BY dj 
    HAVING COUNT(*) >= 4 
    ORDER BY mix_count DESC
");
$super_packs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// TOP DJS - CORREGIDO
$stmt = $db->query("
    SELECT 
        d.name, 
        d.avatar, 
        COALESCE(SUM(s.downloads), 0) as total_downloads 
    FROM djs d
    LEFT JOIN mixes m ON d.name = m.dj AND m.active = 1
    LEFT JOIN statistics s ON m.id = s.item_id AND s.item_type = 'mix'
    WHERE d.active = 1
    GROUP BY d.id
    ORDER BY total_downloads DESC
    LIMIT 5
");
$top_djs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Estadísticas por mix
$stmt = $db->query("SELECT id, plays, downloads FROM mixes WHERE active = 1");
$mixes_stats = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $mixes_stats[$row['id']] = [
        'plays' => $row['plays'],
        'downloads' => $row['downloads']
    ];
}

// Estadísticas por video
$stmt = $db->query("SELECT id, plays FROM videos WHERE active = 1");
$videos_stats = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $videos_stats[$row['id']] = [
        'plays' => $row['plays']
    ];
}

// Construir respuesta
$response = [
    'success' => true,
    'total_mixes' => (int)$total_mixes,
    'total_djs' => (int)$total_djs,
    'total_downloads' => (int)$total_downloads,
    'total_plays' => (int)$total_plays,
    'total_videos' => (int)$total_videos,
    'super_packs' => $super_packs,
    'top_djs' => $top_djs,
    'mixes' => $mixes_stats,
    'videos' => $videos_stats
];

echo json_encode($response);
?>