<?php
// api/get_stats.php - Estadísticas UNIFICADAS usando la tabla statistics
// MEJORADO: Incluye total_downloads en super_packs y ordenamiento correcto
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/config.php';

$db = getDB();

try {
    // ============================================
    // 1. ESTADÍSTICAS TOTALES desde statistics
    // ============================================
    $stmt = $db->query("SELECT 
        COALESCE(SUM(plays), 0) as total_plays,
        COALESCE(SUM(downloads), 0) as total_downloads
        FROM statistics");
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // ============================================
    // 2. CONTAR MIXES, DJS, VIDEOS activos
    // ============================================
    $stmt = $db->query("SELECT COUNT(*) as total FROM mixes WHERE active = 1");
    $total_mixes = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM djs WHERE active = 1");
    $total_djs = $stmt->fetch()['total'];
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM videos WHERE active = 1");
    $total_videos = $stmt->fetch()['total'];
    
    // ============================================
    // 3. ESTADÍSTICAS POR MIX (desde statistics)
    // ============================================
    $stmt = $db->query("SELECT 
        s.item_id as id,
        COALESCE(SUM(s.plays), 0) as plays,
        COALESCE(SUM(s.downloads), 0) as downloads
        FROM statistics s
        WHERE s.item_type = 'mix'
        GROUP BY s.item_id");
    $mixes_stats = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $mixes_stats[$row['id']] = [
            'plays' => (int)$row['plays'],
            'downloads' => (int)$row['downloads']
        ];
    }
    
    // ============================================
    // 4. TOP 10 MIXES MÁS DESCARGADOS
    // ============================================
    $stmt = $db->query("SELECT 
        m.id, 
        m.title, 
        m.dj, 
        m.cover, 
        m.duration,
        COALESCE(SUM(s.downloads), 0) as total_downloads,
        COALESCE(SUM(s.plays), 0) as total_plays
        FROM mixes m
        LEFT JOIN statistics s ON m.id = s.item_id AND s.item_type = 'mix'
        WHERE m.active = 1
        GROUP BY m.id
        ORDER BY total_downloads DESC
        LIMIT 10");
    $top_mixes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ============================================
    // 5. TOP 5 DJS POR DESCARGAS (SOLO CON MIXES)
    // ============================================
    $stmt = $db->query("SELECT 
        d.id, 
        d.name, 
        d.genre, 
        d.city, 
        d.avatar, 
        d.bio,
        COALESCE(SUM(s.downloads), 0) as total_downloads,
        COALESCE(SUM(s.plays), 0) as total_plays,
        COUNT(DISTINCT m.id) as total_mixes
        FROM djs d
        INNER JOIN mixes m ON d.name = m.dj AND m.active = 1
        LEFT JOIN statistics s ON m.id = s.item_id AND s.item_type = 'mix'
        WHERE d.active = 1
        GROUP BY d.id
        HAVING total_mixes > 0
        ORDER BY total_downloads DESC
        LIMIT 5");
    $top_djs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ============================================
    // 6. SUPER PACKS (DJs con 4+ mixes) - MEJORADO con descargas
    // ============================================
    $stmt = $db->query("SELECT 
        m.dj,
        COUNT(*) as mix_count,
        MAX(m.date) as last_mix_date,
        COALESCE(SUM(s.downloads), 0) as total_downloads,
        COALESCE(SUM(s.plays), 0) as total_plays,
        (SELECT avatar FROM djs WHERE name = m.dj LIMIT 1) as avatar
        FROM mixes m
        LEFT JOIN statistics s ON m.id = s.item_id AND s.item_type = 'mix'
        WHERE m.active = 1
        GROUP BY m.dj
        HAVING COUNT(*) >= 4
        ORDER BY total_downloads DESC");
    $super_packs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ============================================
    // 7. TODOS LOS DJS PARA LISTA (con paginación opcional)
    // ============================================
    $all_djs_stmt = $db->query("SELECT 
        d.id, 
        d.name, 
        d.genre, 
        d.city, 
        d.avatar, 
        d.bio,
        COALESCE(SUM(s.downloads), 0) as total_downloads,
        COALESCE(SUM(s.plays), 0) as total_plays,
        COUNT(DISTINCT m.id) as total_mixes
        FROM djs d
        LEFT JOIN mixes m ON d.name = m.dj AND m.active = 1
        LEFT JOIN statistics s ON m.id = s.item_id AND s.item_type = 'mix'
        WHERE d.active = 1
        GROUP BY d.id
        ORDER BY total_downloads DESC");
    $all_djs = $all_djs_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ============================================
    // 8. ESTADÍSTICAS POR DÍA (últimos 7 días)
    // ============================================
    $daily_stats = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $stmt = $db->prepare("SELECT 
            COALESCE(SUM(plays), 0) as plays,
            COALESCE(SUM(downloads), 0) as downloads
            FROM statistics 
            WHERE DATE(last_updated) = :date");
        $stmt->execute([':date' => $date]);
        $daily = $stmt->fetch(PDO::FETCH_ASSOC);
        $daily_stats[] = [
            'date' => $date,
            'plays' => (int)$daily['plays'],
            'downloads' => (int)$daily['downloads']
        ];
    }
    
    // ============================================
    // RESPUESTA COMPLETA
    // ============================================
    echo json_encode([
        'success' => true,
        'total_mixes' => (int)$total_mixes,
        'total_djs' => (int)$total_djs,
        'total_videos' => (int)$total_videos,
        'total_plays' => (int)$totals['total_plays'],
        'total_downloads' => (int)$totals['total_downloads'],
        'mixes' => $mixes_stats,
        'top_mixes' => $top_mixes,
        'top_djs' => $top_djs,
        'super_packs' => $super_packs,
        'all_djs' => $all_djs,
        'daily_stats' => $daily_stats,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener estadísticas: ' . $e->getMessage()
    ]);
}
?>