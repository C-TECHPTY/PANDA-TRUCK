<?php
// player/download.php
require_once '../includes/config.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    $conn = getDB();
    
    // Obtener la URL del mix
    $stmt = $conn->prepare("SELECT url, title, dj FROM mixes WHERE id = :id AND active = 1");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $mix = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($mix) {
        // Actualizar contador de descargas
        $stmt2 = $conn->prepare("UPDATE mixes SET downloads = downloads + 1 WHERE id = :id");
        $stmt2->bindParam(':id', $id);
        $stmt2->execute();
        
        // Actualizar tabla statistics
        $stmt3 = $conn->prepare("INSERT INTO statistics (item_id, item_type, downloads, last_updated) 
                                 VALUES (:id, 'mix', 1, NOW())
                                 ON DUPLICATE KEY UPDATE downloads = downloads + 1, last_updated = NOW()");
        $stmt3->bindParam(':id', $id);
        $stmt3->execute();
        
        // Redirigir al archivo para descargar
        header('Location: ' . $mix['url']);
        exit;
    }
}

// Si no se encuentra, redirigir al inicio
header('Location: ../index.php');
exit;
?>