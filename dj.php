<?php
require_once 'includes/config.php';
require_once 'includes/track_visit.php';

$db = getDB();
$slug = trim($_GET['slug'] ?? '');

if ($slug === '') {
    http_response_code(404);
    die('DJ no encontrado');
}

$stmt = $db->prepare("SELECT * FROM djs WHERE (slug = :slug OR id = :id) AND active = 1 LIMIT 1");
$stmt->execute([':slug' => $slug, ':id' => (int)$slug]);
$dj = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dj) {
    http_response_code(404);
    die('DJ no encontrado');
}

$isFounder = ($dj['plan'] ?? 'free') === 'founder' && ($dj['subscription_status'] ?? '') === 'active';
$isPro = $isFounder || (($dj['plan'] ?? 'free') === 'pro'
    && ($dj['subscription_status'] ?? '') === 'active'
    && !empty($dj['subscription_end'])
    && strtotime($dj['subscription_end']) >= time());

trackVisit('dj_profile', (int)$dj['id'], (int)$dj['id']);

$limitSql = $isPro ? '' : ' LIMIT 2';
$stmt = $db->prepare("SELECT * FROM mixes WHERE dj = :dj AND active = 1 ORDER BY date DESC, id DESC" . $limitSql);
$stmt->execute([':dj' => $dj['name']]);
$mixes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT
    COALESCE(SUM(s.plays), 0) AS total_plays,
    COALESCE(SUM(s.downloads), 0) AS total_downloads
    FROM mixes m
    LEFT JOIN statistics s ON s.item_id = m.id AND s.item_type = 'mix'
    WHERE m.dj = :dj AND m.active = 1");
$stmt->execute([':dj' => $dj['name']]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT COUNT(*) AS visits FROM site_visits WHERE dj_id = :dj_id");
$stmt->execute([':dj_id' => $dj['id']]);
$visits = (int)($stmt->fetch(PDO::FETCH_ASSOC)['visits'] ?? 0);

$photo = $dj['profile_photo'] ?: ($dj['avatar'] ?: 'assets/img/default-avatar.jpg');
$bio = $dj['biography'] ?: ($dj['bio'] ?? '');
$instagram = trim($dj['instagram'] ?? '');
$shareUrl = BASE_URL . 'dj.php?slug=' . urlencode($dj['slug'] ?: $dj['id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($dj['name']); ?> - Panda Truck</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-neutral-950 text-white">
    <main class="max-w-6xl mx-auto p-4 md:p-8">
        <a href="index.php" class="text-neutral-400 hover:text-red-500 text-sm"><i class="fas fa-arrow-left mr-2"></i>Volver</a>

        <section class="mt-6 grid md:grid-cols-[260px_1fr] gap-6 items-start">
            <div class="bg-neutral-900 rounded-lg p-4">
                <img src="<?php echo htmlspecialchars($photo); ?>" class="w-full aspect-square object-cover rounded-lg mb-4" onerror="this.src='assets/img/default-avatar.jpg'">
                <?php if ($isFounder): ?>
                    <div class="inline-flex items-center gap-2 bg-amber-600 px-3 py-1 rounded-full text-sm font-bold mb-3"><i class="fas fa-star"></i> DJ FUNDADOR</div>
                <?php elseif ($isPro): ?>
                    <div class="inline-flex items-center gap-2 bg-red-600 px-3 py-1 rounded-full text-sm font-bold mb-3"><i class="fas fa-crown"></i> DJ PRO</div>
                <?php else: ?>
                    <div class="inline-flex items-center gap-2 bg-neutral-700 px-3 py-1 rounded-full text-sm mb-3">Modo basico</div>
                <?php endif; ?>
                <h1 class="text-2xl font-bold"><?php echo htmlspecialchars($dj['name']); ?></h1>
                <p class="text-neutral-400"><?php echo htmlspecialchars($dj['genre'] ?? ''); ?> <?php echo $dj['city'] ? ' - ' . htmlspecialchars($dj['city']) : ''; ?></p>
                <div class="flex flex-col gap-2 mt-4">
                    <?php if ($isPro && $instagram): ?>
                        <a href="https://instagram.com/<?php echo ltrim(htmlspecialchars($instagram), '@'); ?>" target="_blank" class="bg-neutral-800 hover:bg-neutral-700 rounded px-4 py-2 text-center"><i class="fab fa-instagram mr-2"></i>Seguir en Instagram</a>
                    <?php endif; ?>
                    <a href="https://wa.me/?text=<?php echo rawurlencode('Mira el perfil de ' . $dj['name'] . ' en Panda Truck: ' . $shareUrl); ?>" target="_blank" class="bg-green-700 hover:bg-green-800 rounded px-4 py-2 text-center"><i class="fab fa-whatsapp mr-2"></i>Compartir</a>
                </div>
            </div>

            <div>
                <div class="bg-neutral-900 rounded-lg p-5 mb-5">
                    <h2 class="font-bold text-lg mb-2">Biografia</h2>
                    <p class="text-neutral-300 leading-relaxed"><?php echo nl2br(htmlspecialchars($isPro ? $bio : substr($bio, 0, 180))); ?></p>
                    <?php if (!$isPro): ?>
                        <p class="mt-4 text-sm text-neutral-400">Este perfil esta en modo basico.</p>
                    <?php endif; ?>
                </div>

                <?php if ($isPro): ?>
                <div class="grid grid-cols-3 gap-3 mb-5">
                    <div class="bg-neutral-900 rounded p-4"><p class="text-xs text-neutral-400">Reproducciones</p><p class="text-2xl font-bold"><?php echo number_format((int)$stats['total_plays']); ?></p></div>
                    <div class="bg-neutral-900 rounded p-4"><p class="text-xs text-neutral-400">Descargas</p><p class="text-2xl font-bold"><?php echo number_format((int)$stats['total_downloads']); ?></p></div>
                    <div class="bg-neutral-900 rounded p-4"><p class="text-xs text-neutral-400">Visitas</p><p class="text-2xl font-bold"><?php echo number_format($visits); ?></p></div>
                </div>
                <?php endif; ?>

                <h2 class="font-bold text-xl mb-3">Mixes</h2>
                <div class="space-y-3">
                    <?php foreach ($mixes as $mix): ?>
                    <div class="bg-neutral-900 rounded-lg p-3 flex gap-3 items-center">
                        <img src="<?php echo htmlspecialchars($mix['cover'] ?: 'assets/img/default-cover.jpg'); ?>" class="w-16 h-16 object-cover rounded" onerror="this.src='assets/img/default-cover.jpg'">
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold truncate"><?php echo htmlspecialchars($mix['title']); ?></h3>
                            <p class="text-sm text-neutral-400"><?php echo htmlspecialchars($mix['genre']); ?></p>
                        </div>
                        <a href="player/index.php?id=<?php echo (int)$mix['id']; ?>" class="w-10 h-10 bg-red-600 rounded-full flex items-center justify-center"><i class="fas fa-play"></i></a>
                        <a href="api/download_mix.php?id=<?php echo (int)$mix['id']; ?>" class="w-10 h-10 bg-neutral-800 rounded-full flex items-center justify-center"><i class="fas fa-download"></i></a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
