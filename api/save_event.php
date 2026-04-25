<?php
// api/save_event.php
header('Content-Type: application/json');
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth->requireAdmin();

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
    exit;
}

$db = getDB();

if (isset($data['id']) && $data['id'] > 0) {
    $sql = "UPDATE events SET title = :title, date = :date, time = :time, place = :place, active = :active WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':id', $data['id']);
} else {
    $sql = "INSERT INTO events (title, date, time, place, active) VALUES (:title, :date, :time, :place, :active)";
    $stmt = $db->prepare($sql);
}

$stmt->bindValue(':title', $data['title']);
$stmt->bindValue(':date', $data['date']);
$stmt->bindValue(':time', $data['time']);
$stmt->bindValue(':place', $data['place']);
$stmt->bindValue(':active', $data['active']);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al guardar']);
}
?>