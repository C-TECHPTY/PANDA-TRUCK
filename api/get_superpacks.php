<?php
// api/get_superpacks.php - Obtener Super Packs para el index
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/config.php';

$db = getDB();

// Obtener DJs con 4+ mixes
$stmt = $db->query("SELECT dj, COUNT(*) as mix_count, MAX(date) as last_mix_date 
                    FROM mixes 
                    WHERE active = 1 
                    GROUP BY dj 
                    HAVING COUNT(*) >= 4 
                    ORDER BY mix_count DESC");
$super_packs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// También obtener estadísticas de cada Super Pack
foreach ($super_packs as &$pack) {
    $stmt = $db->prepare("SELECT SUM(plays) as total_plays, SUM(downloads) as total_downloads 
                          FROM mixes WHERE dj = :dj AND active = 1");
    $stmt->bindValue(':dj', $pack['dj']);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $pack['total_plays'] = $stats['total_plays'] ?? 0;
    $pack['total_downloads'] = $stats['total_downloads'] ?? 0;
}

echo json_encode($super_packs);
?>