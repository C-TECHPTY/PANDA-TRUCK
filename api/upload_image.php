<?php
// api/upload_image.php - Subir imagen (logo, avatar, etc.)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$auth->requireAdmin();

$uploadDir = __DIR__ . '/../uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$response = ['success' => false, 'error' => 'No se recibió archivo'];

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['image'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'error' => 'Formato no permitido. Usa JPG, PNG, GIF o WEBP']);
        exit;
    }
    
    $filename = 'logo_' . time() . '.' . $ext;
    $destination = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $url = 'uploads/' . $filename;
        
        // Si es un logo, actualizar la configuración automáticamente
        if (isset($_POST['type']) && $_POST['type'] === 'logo') {
            $db = getDB();
            $stmt = $db->prepare("INSERT INTO system_settings (setting_key, setting_value) 
                                  VALUES ('site_logo', :url) 
                                  ON DUPLICATE KEY UPDATE setting_value = :url");
            $stmt->bindValue(':url', $url);
            $stmt->execute();
        }
        
        echo json_encode(['success' => true, 'url' => $url]);
        exit;
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al mover el archivo']);
        exit;
    }
}

echo json_encode($response);
?>