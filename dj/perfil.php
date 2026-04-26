<?php
// dj/perfil.php - Compatibilidad para enlaces antiguos de perfiles DJ.
require_once '../includes/config.php';

$db = getDB();
$djParam = trim($_GET['dj'] ?? $_GET['slug'] ?? '');

if ($djParam === '') {
    header('Location: ../index.php');
    exit;
}

$stmt = $db->prepare("SELECT id, name, slug FROM djs WHERE (name = :dj OR slug = :slug OR id = :id) AND active = 1 LIMIT 1");
$stmt->execute([
    ':dj' => $djParam,
    ':slug' => $djParam,
    ':id' => (int)$djParam,
]);
$dj = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dj) {
    header('Location: ../index.php');
    exit;
}

$profileKey = $dj['slug'] ?: $dj['id'];
header('Location: ../dj.php?slug=' . urlencode($profileKey), true, 302);
exit;
