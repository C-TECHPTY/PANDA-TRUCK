<?php
// api/descargar_zip.php - Lista segura de descargas CDN para mixes seleccionados.
// No descarga MP3 al hosting y no sirve archivos desde PHP.
require_once __DIR__ . '/../includes/config.php';

$ids = isset($_GET['ids']) ? $_GET['ids'] : (isset($_POST['ids']) ? $_POST['ids'] : '');
$dj = isset($_GET['dj']) ? trim(urldecode($_GET['dj'])) : (isset($_POST['dj']) ? trim(urldecode($_POST['dj'])) : 'DJ');

if ($ids === '') {
    http_response_code(400);
    die('No se seleccionaron mixes.');
}

$idsArray = array_values(array_unique(array_filter(array_map('intval', explode(',', $ids)), function ($id) {
    return $id > 0;
})));

if (!$idsArray) {
    http_response_code(400);
    die('IDs invalidos.');
}

$db = getDB();
$placeholders = implode(',', array_fill(0, count($idsArray), '?'));
$stmt = $db->prepare("SELECT id, title, dj, url FROM mixes WHERE id IN ($placeholders) AND active = 1 ORDER BY id DESC");
foreach ($idsArray as $index => $id) {
    $stmt->bindValue($index + 1, $id, PDO::PARAM_INT);
}
$stmt->execute();
$mixes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$mixes) {
    http_response_code(404);
    die('No se encontraron mixes activos.');
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Descargas CDN - Panda Truck</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-neutral-950 text-white min-h-screen">
    <main class="max-w-3xl mx-auto px-4 py-8">
        <a href="javascript:history.back()" class="text-sm text-red-400 hover:underline">Volver</a>
        <h1 class="text-2xl font-bold mt-4">Descargas de <?php echo htmlspecialchars($dj); ?></h1>
        <p class="text-neutral-400 mt-2">
            Por seguridad del hosting, los MP3 se descargan individualmente desde BunnyCDN. Usa los botones de abajo.
        </p>

        <div class="mt-6 space-y-3">
            <?php foreach ($mixes as $mix): ?>
                <div class="rounded border border-neutral-800 bg-neutral-900 p-4 flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-semibold truncate"><?php echo htmlspecialchars($mix['title']); ?></p>
                        <p class="text-sm text-neutral-500 truncate"><?php echo htmlspecialchars($mix['dj']); ?></p>
                    </div>
                    <a href="download_mix.php?id=<?php echo (int)$mix['id']; ?>"
                       class="shrink-0 rounded bg-red-600 px-4 py-2 text-sm font-semibold hover:bg-red-700">
                        Descargar MP3
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
</body>
</html>
