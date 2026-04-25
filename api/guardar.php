<?php
// api/guardar.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../includes/config.php';

// Verificar autenticación (simplificado para pruebas)
// En producción, verificar token/sesión

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['tipo']) || !isset($input['datos'])) {
    echo json_encode(['error' => 'Datos inválidos', 'success' => false]);
    exit;
}

$tipo = $input['tipo'];
$datos = $input['datos'];
$db = getDB();

try {
    $db->beginTransaction();
    
    foreach ($datos as $item) {
        if (isset($item['id']) && $item['id'] > 0) {
            // Actualizar
            $sql = "UPDATE $tipo SET ";
            $fields = [];
            $params = [];
            
            foreach ($item as $key => $value) {
                if ($key != 'id') {
                    $fields[] = "$key = :$key";
                    $params[":$key"] = $value;
                }
            }
            
            $sql .= implode(', ', $fields);
            $sql .= " WHERE id = :id";
            $params[':id'] = $item['id'];
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
        } else {
            // Insertar
            $columns = array_keys($item);
            $placeholders = array_map(function($col) { return ":$col"; }, $columns);
            
            $sql = "INSERT INTO $tipo (" . implode(', ', $columns) . ") 
                    VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($item);
        }
    }
    
    $db->commit();
    
    echo json_encode(['success' => true, 'message' => 'Datos guardados correctamente']);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['error' => $e->getMessage(), 'success' => false]);
}
?>