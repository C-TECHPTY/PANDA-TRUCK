<?php
// api/get_banners.php - Obtener banners activos para el frontend
require_once '../includes/config.php';

header('Content-Type: application/json');

$db = getDB();
$today = date('Y-m-d');

// Obtener banners activos (fecha vigente y activos)
$stmt = $db->prepare("SELECT * FROM banners 
                      WHERE active = 1 
                      AND type = 'sidebar'
                      AND (start_date IS NULL OR start_date <= :today)
                      AND (end_date IS NULL OR end_date >= :today)
                      ORDER BY position ASC, id ASC");
$stmt->bindValue(':today', $today);
$stmt->execute();
$banners = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($banners);
?>