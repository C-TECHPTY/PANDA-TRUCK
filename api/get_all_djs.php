<?php
// api/get_all_djs.php - Obtener todos los DJs con filtro de búsqueda
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/config.php';

$db = getDB();

$search = $_GET['search'] ?? '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

try {
    // Construir consulta base
    $sql = "SELECT 
                d.id, d.name, d.genre, d.city, d.bio, d.avatar, d.socials, d.featured_week, d.active,
                COALESCE(SUM(s.plays), 0) as total_plays,
                COALESCE(SUM(s.downloads), 0) as total_downloads,
                COUNT(DISTINCT m.id) as total_mixes
            FROM djs d
            LEFT JOIN mixes m ON d.name = m.dj AND m.active = 1
            LEFT JOIN statistics s ON m.id = s.item_id AND s.item_type = 'mix'
            WHERE d.active = 1";
    
    $params = [];
    
    // Aplicar filtro de búsqueda si existe
    if (!empty($search)) {
        $sql .= " AND (d.name LIKE :search OR d.genre LIKE :search OR d.city LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    // Agrupar y ordenar por descargas (los más populares primero)
    $sql .= " GROUP BY d.id 
              HAVING total_mixes > 0
              ORDER BY total_downloads DESC, total_mixes DESC
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $djs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener total de DJs para paginación (solo los que tienen al menos 1 mix)
    $countSql = "SELECT COUNT(DISTINCT d.id) as total 
                 FROM djs d
                 LEFT JOIN mixes m ON d.name = m.dj AND m.active = 1
                 WHERE d.active = 1 AND m.id IS NOT NULL";
    
    if (!empty($search)) {
        $countSql .= " AND (d.name LIKE :search OR d.genre LIKE :search OR d.city LIKE :search)";
    }
    
    $countStmt = $db->prepare($countSql);
    if (!empty($search)) {
        $countStmt->bindValue(':search', "%$search%");
    }
    $countStmt->execute();
    $total = $countStmt->fetch()['total'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'djs' => $djs,
        'total' => (int)$total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($total / $limit)
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al cargar DJs: ' . $e->getMessage()
    ]);
}
?>