<?php
header('Content-Type: application/json');
require_once '../includes/config.php';
$id = $_GET['id'] ?? 0;
$stmt = getDB()->prepare("SELECT * FROM events WHERE id = :id");
$stmt->bindValue(':id', $id);
$stmt->execute();
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
?>