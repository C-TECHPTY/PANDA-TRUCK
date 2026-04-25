<?php
// includes/funciones.php

function getMixes($limit = null, $genre = null) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $sql = "SELECT * FROM mixes WHERE active = 1";
    $params = [];
    
    if ($genre) {
        $sql .= " AND genre = :genre";
        $params[':genre'] = $genre;
    }
    
    $sql .= " ORDER BY date DESC";
    
    if ($limit) {
        $sql .= " LIMIT :limit";
        $params[':limit'] = $limit;
    }
    
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => &$val) {
        $stmt->bindParam($key, $val);
    }
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getDJsWithStats() {
    $db = new Database();
    $conn = $db->getConnection();
    
    $sql = "SELECT d.*, 
            (SELECT COUNT(*) FROM mixes WHERE dj = d.name AND active = 1) as total_mixes,
            (SELECT SUM(plays) FROM statistics WHERE item_type = 'mix' AND item_id IN (SELECT id FROM mixes WHERE dj = d.name)) as total_plays,
            (SELECT SUM(downloads) FROM statistics WHERE item_type = 'mix' AND item_id IN (SELECT id FROM mixes WHERE dj = d.name)) as total_downloads
            FROM djs d 
            WHERE d.active = 1 
            ORDER BY total_downloads DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getSuperPacks() {
    $db = new Database();
    $conn = $db->getConnection();
    
    $sql = "SELECT dj, COUNT(*) as mix_count, 
            GROUP_CONCAT(id) as mix_ids,
            MAX(date) as last_mix_date
            FROM mixes 
            WHERE active = 1 
            GROUP BY dj 
            HAVING COUNT(*) >= 4 
            ORDER BY mix_count DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function updateStatistics($item_id, $item_type, $action) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $sql = "INSERT INTO statistics (item_id, item_type, plays, downloads, last_updated) 
            VALUES (:item_id, :item_type, 
            CASE WHEN :action = 'play' THEN 1 ELSE 0 END,
            CASE WHEN :action = 'download' THEN 1 ELSE 0 END,
            NOW())
            ON DUPLICATE KEY UPDATE 
            plays = plays + (CASE WHEN :action = 'play' THEN 1 ELSE 0 END),
            downloads = downloads + (CASE WHEN :action = 'download' THEN 1 ELSE 0 END),
            last_updated = NOW()";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':item_id', $item_id);
    $stmt->bindParam(':item_type', $item_type);
    $stmt->bindParam(':action', $action);
    
    return $stmt->execute();
}

function getTopDJs($limit = 10) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $sql = "SELECT d.*, 
            COALESCE(SUM(s.downloads), 0) as total_downloads,
            COALESCE(SUM(s.plays), 0) as total_plays
            FROM djs d
            LEFT JOIN mixes m ON d.name = m.dj AND m.active = 1
            LEFT JOIN statistics s ON m.id = s.item_id AND s.item_type = 'mix'
            WHERE d.active = 1
            GROUP BY d.id
            ORDER BY total_downloads DESC, total_plays DESC
            LIMIT :limit";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>