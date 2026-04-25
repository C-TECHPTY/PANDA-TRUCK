<?php
// dj/perfil.php - Perfil de DJ con diseño elegante
require_once '../includes/config.php';

$db = getDB();

$dj_name = $_GET['dj'] ?? '';
if (!$dj_name) {
    header('Location: ../index.php');
    exit;
}

// Obtener información del DJ
$stmt = $db->prepare("SELECT * FROM djs WHERE name = :name AND active = 1");
$stmt->bindParam(':name', $dj_name);
$stmt->execute();
$dj = $stmt->fetch();

if (!$dj) {
    header('Location: ../index.php');
    exit;
}

// Obtener mixes del DJ
$stmt = $db->prepare("SELECT * FROM mixes WHERE dj = :dj AND active = 1 ORDER BY date DESC");
$stmt->bindParam(':dj', $dj_name);
$stmt->execute();
$mixes = $stmt->fetchAll();

$mix_count = count($mixes);
$is_superpack = $mix_count >= 4;

// ============================================
// ESTADÍSTICAS DEL DJ USANDO statistics (CORREGIDO)
// ============================================
$stmt = $db->prepare("
    SELECT 
        COALESCE(SUM(s.plays), 0) as total_plays,
        COALESCE(SUM(s.downloads), 0) as total_downloads
    FROM mixes m
    LEFT JOIN statistics s ON m.id = s.item_id AND s.item_type = 'mix'
    WHERE m.dj = :dj AND m.active = 1
");
$stmt->bindParam(':dj', $dj_name);
$stmt->execute();
$stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($dj['name']); ?> - Panda Truck Reloaded</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #e1261d;
            --primary-hover: #c81e16;
        }
        .text-primary { color: var(--primary); }
        .bg-primary { background-color: var(--primary); }
        .bg-primary:hover { background-color: var(--primary-hover); }
        .border-primary { border-color: var(--primary); }
        .mix-card:hover { transform: translateY(-5px); transition: 0.3s; }
        
        /* Estilo elegante para la biografía */
        .bio-card {
            background: linear-gradient(135deg, rgba(225, 38, 29, 0.05) 0%, rgba(225, 38, 29, 0.02) 100%);
            border-left: 4px solid var(--primary);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1.5rem;
            backdrop-filter: blur(4px);
        }
        .bio-text {
            font-size: 0.95rem;
            line-height: 1.7;
            color: #d4d4d8;
            font-weight: 400;
            letter-spacing: 0.01em;
        }
        .bio-quote {
            font-size: 1.5rem;
            color: var(--primary);
            opacity: 0.5;
            margin-right: 0.5rem;
            vertical-align: middle;
        }
        .social-icon {
            transition: all 0.3s ease;
        }
        .social-icon:hover {
            transform: translateY(-3px);
            color: var(--primary) !important;
        }
        .stat-card {
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(225, 38, 29, 0.2);
            border-radius: 16px;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="bg-neutral-950 text-white">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Botón volver -->
        <div class="mb-6">
            <a href="../index.php" class="inline-flex items-center gap-2 text-neutral-400 hover:text-primary transition group">
                <i class="fas fa-arrow-left group-hover:-translate-x-1 transition"></i> 
                <span>Volver al inicio</span>
            </a>
        </div>
        
        <!-- Información del DJ - Diseño mejorado -->
        <div class="relative overflow-hidden rounded-3xl mb-8">
            <!-- Fondo decorativo -->
            <div class="absolute inset-0 bg-gradient-to-r from-primary/20 via-transparent to-transparent"></div>
            <div class="absolute top-0 right-0 w-64 h-64 bg-primary/5 rounded-full blur-3xl"></div>
            
            <div class="relative p-6 md:p-8">
                <div class="flex flex-col md:flex-row gap-6 items-center md:items-start">
                    <!-- Avatar con efecto glow -->
                    <div class="relative">
                        <div class="absolute inset-0 bg-primary rounded-full blur-xl opacity-50"></div>
                        <div class="relative w-32 h-32 rounded-full bg-neutral-800 overflow-hidden border-4 border-primary shadow-2xl">
                            <img src="<?php echo htmlspecialchars($dj['avatar'] ?? '../assets/img/default-avatar.jpg'); ?>" 
                                 class="w-full h-full object-cover"
                                 onerror="this.src='../assets/img/default-avatar.jpg'">
                        </div>
                    </div>
                    
                    <div class="text-center md:text-left flex-1">
                        <div class="flex items-center gap-3 flex-wrap justify-center md:justify-start">
                            <h1 class="text-3xl md:text-4xl font-bold bg-gradient-to-r from-white to-neutral-400 bg-clip-text text-transparent">
                                <?php echo htmlspecialchars($dj['name']); ?>
                            </h1>
                            <?php if ($is_superpack): ?>
                            <span class="px-3 py-1 bg-primary/20 text-primary rounded-full text-sm font-semibold animate-pulse">
                                🔥 SUPER PACK
                            </span>
                            <?php endif; ?>
                        </div>
                        <p class="text-neutral-400 mt-2">
                            <i class="fas fa-music text-primary mr-1"></i> <?php echo htmlspecialchars($dj['genre'] ?? 'DJ'); ?> 
                            <span class="mx-2">•</span>
                            <i class="fas fa-map-marker-alt text-primary mr-1"></i> <?php echo htmlspecialchars($dj['city'] ?? 'Panamá'); ?>
                        </p>
                        
                        <!-- Estadísticas - Cards elegantes (AHORA USA statistics) -->
                        <div class="flex gap-4 mt-5 justify-center md:justify-start">
                            <div class="stat-card px-4 py-2 text-center min-w-[80px]">
                                <div class="text-2xl font-bold text-primary"><?php echo $mix_count; ?></div>
                                <div class="text-xs text-neutral-400">Mixes</div>
                            </div>
                            <div class="stat-card px-4 py-2 text-center min-w-[80px]">
                                <div class="text-2xl font-bold text-primary"><?php echo number_format($stats['total_plays'] ?? 0); ?></div>
                                <div class="text-xs text-neutral-400">Reproducciones</div>
                            </div>
                            <div class="stat-card px-4 py-2 text-center min-w-[80px]">
                                <div class="text-2xl font-bold text-primary"><?php echo number_format($stats['total_downloads'] ?? 0); ?></div>
                                <div class="text-xs text-neutral-400">Descargas</div>
                            </div>
                        </div>
                        
                        <!-- Biografía elegante -->
                        <?php 
                        $bio = trim($dj['bio'] ?? '');
                        if (!empty($bio) && $bio !== 'NULL'): 
                        ?>
                        <div class="bio-card mt-5">
                            <div class="flex items-start">
                                <i class="fas fa-quote-left bio-quote"></i>
                                <div class="bio-text">
                                    <?php echo nl2br(htmlspecialchars($bio)); ?>
                                </div>
                            </div>
                            <div class="mt-3 flex items-center gap-2 text-xs text-neutral-500">
                                <i class="fas fa-microphone-alt text-primary"></i>
                                <span>DJ en Panda Truck Reloaded</span>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="mt-5 p-4 bg-neutral-800/30 rounded-xl border border-dashed border-neutral-700 text-center">
                            <i class="fas fa-user-astronaut text-neutral-500 text-2xl mb-2 block"></i>
                            <p class="text-neutral-500 text-sm">
                                <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($dj['name']); ?> aún no ha compartido su biografía.
                            </p>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Redes Sociales -->
                        <?php if (!empty($dj['socials']) && $dj['socials'] !== 'NULL'): ?>
                        <div class="mt-5 flex gap-3 justify-center md:justify-start">
                            <?php 
                            $socials = json_decode($dj['socials'], true);
                            if ($socials && is_array($socials)):
                                foreach ($socials as $platform => $link):
                                    if (!empty($link)):
                                        $icon = match(strtolower($platform)) {
                                            'instagram' => 'fab fa-instagram',
                                            'facebook' => 'fab fa-facebook',
                                            'twitter' => 'fab fa-twitter',
                                            'youtube' => 'fab fa-youtube',
                                            'tiktok' => 'fab fa-tiktok',
                                            'spotify' => 'fab fa-spotify',
                                            default => 'fas fa-link'
                                        };
                            ?>
                            <a href="<?php echo htmlspecialchars($link); ?>" target="_blank" 
                               class="social-icon w-9 h-9 rounded-full bg-neutral-800 flex items-center justify-center text-neutral-400 hover:bg-primary hover:text-white transition-all duration-300">
                                <i class="<?php echo $icon; ?> text-sm"></i>
                            </a>
                            <?php 
                                    endif;
                                endforeach;
                            endif;
                            ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Lista de mixes -->
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold flex items-center gap-2">
                <i class="fas fa-headphones text-primary"></i> 
                Mixes de <?php echo htmlspecialchars($dj['name']); ?>
            </h2>
            <?php if ($is_superpack): ?>
            <a href="superpack.php?dj=<?php echo urlencode($dj['name']); ?>" 
               class="text-sm bg-primary/20 hover:bg-primary/30 text-primary px-4 py-2 rounded-full transition flex items-center gap-2">
                <i class="fas fa-box-open"></i> Ver Super Pack Completo
                <i class="fas fa-arrow-right text-xs"></i>
            </a>
            <?php endif; ?>
        </div>
        
        <?php if (count($mixes) > 0): ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            <?php foreach ($mixes as $mix): ?>
            <div class="mix-card bg-neutral-900 rounded-xl overflow-hidden border border-neutral-800 hover:border-primary transition-all duration-300 group">
                <div class="aspect-square relative overflow-hidden">
                    <img src="<?php echo htmlspecialchars($mix['cover'] ?? '../assets/img/default-cover.jpg'); ?>" 
                         alt="<?php echo htmlspecialchars($mix['title']); ?>" 
                         class="w-full h-full object-cover group-hover:scale-110 transition duration-500"
                         onerror="this.src='../assets/img/default-cover.jpg'">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent opacity-0 group-hover:opacity-100 transition duration-300 flex items-center justify-center gap-3">
                        <a href="../player/index.php?id=<?php echo $mix['id']; ?>" 
                           class="w-12 h-12 rounded-full bg-primary flex items-center justify-center text-xl hover:scale-110 transition transform">
                            ▶
                        </a>
                        <a href="../api/download.php?id=<?php echo $mix['id']; ?>" 
                           class="w-12 h-12 rounded-full bg-neutral-800 flex items-center justify-center text-xl hover:bg-primary transition transform hover:scale-110">
                            ⬇️
                        </a>
                    </div>
                    <div class="absolute top-2 right-2 px-2 py-1 rounded-full bg-black/70 text-xs font-mono">
                        <?php echo $mix['duration'] ?: '00:00'; ?>
                    </div>
                </div>
                <div class="p-4">
                    <h3 class="font-semibold truncate group-hover:text-primary transition"><?php echo htmlspecialchars($mix['title']); ?></h3>
                    <p class="text-sm text-neutral-400 mt-1"><?php echo htmlspecialchars($mix['genre']); ?></p>
                    <div class="flex justify-between items-center mt-3 text-xs text-neutral-500">
                        <span><i class="fas fa-play text-primary mr-1"></i> <span id="play-<?php echo $mix['id']; ?>"><?php echo number_format($mix['plays'] ?? 0); ?></span></span>
                        <span><i class="fas fa-download text-primary mr-1"></i> <span id="dl-<?php echo $mix['id']; ?>"><?php echo number_format($mix['downloads'] ?? 0); ?></span></span>
                        <span><i class="fas fa-database"></i> <?php echo $mix['sizeMB']; ?> MB</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="bg-neutral-900 rounded-2xl p-12 text-center border border-neutral-800">
            <div class="text-6xl mb-4">🎧</div>
            <h3 class="text-xl font-semibold mb-2">No hay mixes disponibles</h3>
            <p class="text-neutral-400">Este DJ aún no tiene mixes publicados en la plataforma.</p>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Actualizar estadísticas en tiempo real (usando API unificada)
        function updateStats() {
            fetch('../api/get_stats.php')
                .then(res => res.json())
                .then(data => {
                    if (data.mixes) {
                        for (const [id, stats] of Object.entries(data.mixes)) {
                            const playSpan = document.getElementById(`play-${id}`);
                            const dlSpan = document.getElementById(`dl-${id}`);
                            if (playSpan) playSpan.textContent = stats.plays.toLocaleString();
                            if (dlSpan) dlSpan.textContent = stats.downloads.toLocaleString();
                        }
                    }
                })
                .catch(err => console.error('Error:', err));
        }
        
        updateStats();
        setInterval(updateStats, 30000);
    </script>
</body>
</html>