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

$stmt = $db->prepare("SELECT COUNT(*) AS total FROM mixes WHERE dj = :dj AND active = 1");
$stmt->execute([':dj' => $dj['name']]);
$totalMixCount = (int)($stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

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

$visits = 0;
try {
    $stmt = $db->prepare("SELECT COUNT(*) AS visits FROM site_visits WHERE dj_id = :dj_id");
    $stmt->execute([':dj_id' => $dj['id']]);
    $visits = (int)($stmt->fetch(PDO::FETCH_ASSOC)['visits'] ?? 0);
} catch (PDOException $e) {
    $visits = 0;
}

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
    <style>
        :root { --pt-red: #e1261d; }
        body {
            background:
                radial-gradient(circle at 15% 0%, rgba(225, 38, 29, 0.18), transparent 28rem),
                linear-gradient(180deg, #080808 0%, #121212 45%, #070707 100%);
        }
        .panel {
            background: rgba(18, 18, 18, 0.84);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 18px 70px rgba(0, 0, 0, 0.35);
        }
        .mix-row:hover img { transform: scale(1.06); }
    </style>
</head>
<body class="text-white">
    <main class="max-w-7xl mx-auto px-4 py-6 md:px-8 md:py-10">
        <div class="flex items-center justify-between gap-4 mb-6">
            <a href="index.php" class="inline-flex items-center gap-2 text-sm text-neutral-400 hover:text-red-500 transition">
                <i class="fas fa-arrow-left"></i> Volver al inicio
            </a>
            <a href="dj-pro.php" class="hidden sm:inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold hover:bg-red-700 transition">
                <i class="fas fa-bolt"></i> DJ PRO
            </a>
        </div>

        <section class="panel overflow-hidden rounded-2xl">
            <div class="grid lg:grid-cols-[340px_1fr]">
                <aside class="border-b border-white/10 bg-black/30 p-5 lg:border-b-0 lg:border-r lg:p-6">
                    <div class="relative">
                        <img src="<?php echo htmlspecialchars($photo); ?>" alt="<?php echo htmlspecialchars($dj['name']); ?>" class="aspect-square w-full rounded-xl object-cover shadow-2xl" onerror="this.src='assets/img/default-avatar.jpg'">
                        <div class="absolute bottom-3 left-3 right-3 flex flex-wrap gap-2">
                <?php if ($isFounder): ?>
                            <span class="inline-flex items-center gap-2 rounded-full bg-amber-500 px-3 py-1 text-xs font-black text-black"><i class="fas fa-star"></i> DJ FUNDADOR</span>
                <?php elseif ($isPro): ?>
                            <span class="inline-flex items-center gap-2 rounded-full bg-red-600 px-3 py-1 text-xs font-black"><i class="fas fa-crown"></i> DJ PRO</span>
                <?php else: ?>
                            <span class="inline-flex items-center gap-2 rounded-full bg-neutral-800 px-3 py-1 text-xs font-bold text-neutral-200">Modo basico</span>
                <?php endif; ?>
                        </div>
                    </div>

                    <h1 class="mt-5 text-3xl font-black tracking-tight md:text-4xl"><?php echo htmlspecialchars($dj['name']); ?></h1>
                    <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-neutral-400">
                        <?php if (!empty($dj['genre'])): ?><span><i class="fas fa-music mr-1 text-red-500"></i><?php echo htmlspecialchars($dj['genre']); ?></span><?php endif; ?>
                        <?php if (!empty($dj['city'])): ?><span><i class="fas fa-location-dot mr-1 text-red-500"></i><?php echo htmlspecialchars($dj['city']); ?></span><?php endif; ?>
                    </p>

                    <div class="mt-5 grid grid-cols-3 gap-2">
                        <div class="rounded-lg bg-neutral-950/70 p-3 text-center">
                            <p class="text-xl font-black"><?php echo number_format($totalMixCount); ?></p>
                            <p class="text-[11px] uppercase text-neutral-500">Mixes</p>
                        </div>
                        <div class="rounded-lg bg-neutral-950/70 p-3 text-center">
                            <p class="text-xl font-black"><?php echo number_format((int)$stats['total_plays']); ?></p>
                            <p class="text-[11px] uppercase text-neutral-500">Plays</p>
                        </div>
                        <div class="rounded-lg bg-neutral-950/70 p-3 text-center">
                            <p class="text-xl font-black"><?php echo number_format((int)$stats['total_downloads']); ?></p>
                            <p class="text-[11px] uppercase text-neutral-500">Descargas</p>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-col gap-2">
                    <?php if ($isPro && $instagram): ?>
                        <a href="https://instagram.com/<?php echo ltrim(htmlspecialchars($instagram), '@'); ?>" target="_blank" class="rounded-lg bg-neutral-800 px-4 py-3 text-center font-semibold hover:bg-neutral-700 transition"><i class="fab fa-instagram mr-2"></i>Seguir en Instagram</a>
                    <?php endif; ?>
                        <a href="https://wa.me/?text=<?php echo rawurlencode('Mira el perfil de ' . $dj['name'] . ' en Panda Truck: ' . $shareUrl); ?>" target="_blank" class="rounded-lg bg-green-700 px-4 py-3 text-center font-semibold hover:bg-green-800 transition"><i class="fab fa-whatsapp mr-2"></i>Compartir perfil</a>
                    </div>
                </aside>

                <div class="p-5 md:p-7">
                    <div class="mb-6 rounded-xl border border-white/10 bg-neutral-950/60 p-5">
                        <div class="mb-2 flex items-center justify-between gap-3">
                            <h2 class="text-lg font-black">Perfil del DJ</h2>
                            <?php if ($isPro): ?>
                                <span class="text-xs font-semibold text-neutral-400"><?php echo number_format($visits); ?> visitas</span>
                            <?php endif; ?>
                        </div>
                        <p class="max-w-3xl text-sm leading-7 text-neutral-300">
                            <?php echo nl2br(htmlspecialchars($isPro ? ($bio ?: 'Este DJ forma parte de Panda Truck Reloaded.') : substr($bio ?: 'Este perfil esta en modo basico dentro de Panda Truck Reloaded.', 0, 180))); ?>
                        </p>
                    <?php if (!$isPro): ?>
                        <p class="mt-4 rounded-lg border border-neutral-700 bg-neutral-900 p-3 text-sm text-neutral-400">Este perfil esta en modo basico. Solo se muestran algunos mixes publicos.</p>
                    <?php endif; ?>
                    </div>

                    <div class="mb-4 flex items-center justify-between gap-3">
                        <h2 class="text-xl font-black">Mixes disponibles</h2>
                        <?php if (!$isPro): ?><span class="text-xs text-neutral-500">Vista limitada</span><?php endif; ?>
                    </div>
                    <div class="space-y-3">
                    <?php if (count($mixes) === 0): ?>
                    <div class="rounded-xl border border-dashed border-white/10 bg-neutral-950/70 p-8 text-center text-neutral-400">
                        <i class="fas fa-headphones mb-3 text-3xl text-neutral-600"></i>
                        <p>Este DJ aun no tiene mixes publicados.</p>
                    </div>
                    <?php endif; ?>
                    <?php foreach ($mixes as $mix): ?>
                    <article class="mix-row flex items-center gap-3 rounded-xl border border-white/10 bg-neutral-950/70 p-3 transition hover:border-red-500/70">
                        <div class="h-16 w-16 shrink-0 overflow-hidden rounded-lg bg-neutral-800 md:h-20 md:w-20">
                            <img src="<?php echo htmlspecialchars($mix['cover'] ?: 'assets/img/default-cover.jpg'); ?>" class="h-full w-full object-cover transition duration-300" onerror="this.src='assets/img/default-cover.jpg'">
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate font-bold"><?php echo htmlspecialchars($mix['title']); ?></h3>
                            <p class="mt-1 text-sm text-neutral-400"><?php echo htmlspecialchars($mix['genre']); ?><?php echo !empty($mix['duration']) ? ' / ' . htmlspecialchars($mix['duration']) : ''; ?></p>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <a href="player/index.php?id=<?php echo (int)$mix['id']; ?>" class="flex h-10 w-10 items-center justify-center rounded-full bg-red-600 hover:bg-red-700 transition" title="Reproducir"><i class="fas fa-play"></i></a>
                            <a href="api/download_mix.php?id=<?php echo (int)$mix['id']; ?>" class="flex h-10 w-10 items-center justify-center rounded-full bg-neutral-800 hover:bg-neutral-700 transition" title="Descargar"><i class="fas fa-download"></i></a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
