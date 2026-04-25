<?php
// ==================== VERIFICACIÓN DE MANTENIMIENTO ====================
session_start();

// Verificar si el usuario es administrador
$isAdmin = false;
if (isset($_SESSION['user_role'])) {
    $isAdmin = ($_SESSION['user_role'] === 'superadmin' || $_SESSION['user_role'] === 'admin');
}

// Si NO es administrador, verificar modo mantenimiento
if (!$isAdmin) {
    try {
        require_once 'includes/config.php';
        $db = getDB();
        $stmt = $db->query("SELECT setting_value FROM system_settings WHERE setting_key = 'maintenance_mode'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['setting_value'] == '1') {
            header('Location: maintenance.php');
            exit;
        }
    } catch (Exception $e) {
        // Si hay error, continuar normalmente
    }
}
// ==================== FIN VERIFICACIÓN ====================

// index.php - Página principal COMPLETA con Radio Online y Publicidad Sidebar
require_once 'includes/config.php';

$db = getDB();

// Obtener mixes destacados (últimos 9)
$stmt = $db->prepare("SELECT * FROM mixes WHERE active = 1 ORDER BY id DESC LIMIT 9");
$stmt->execute();
$mixes_destacados = $stmt->fetchAll();

// Obtener estadísticas generales iniciales
$stmt = $db->query("SELECT COUNT(*) as total FROM mixes WHERE active = 1");
$total_mixes = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM djs WHERE active = 1");
$total_djs = $stmt->fetch()['total'];

$stmt = $db->query("SELECT COUNT(*) as total FROM videos WHERE active = 1");
$total_videos = $stmt->fetch()['total'] ?? 0;

$stmt = $db->query("SELECT SUM(downloads) as total FROM statistics");
$total_downloads = $stmt->fetch()['total'] ?? 0;

$stmt = $db->query("SELECT SUM(plays) as total FROM statistics");
$total_plays = $stmt->fetch()['total'] ?? 0;

// Obtener URL de la radio
$stmt = $db->query("SELECT config_value FROM configuration WHERE config_key = 'radio_url'");
$radio = $stmt->fetch();
$radio_url = $radio['config_value'] ?? 'https://stream.zeno.fm/vjsa6jiwafavv';

$stmt = $db->query("SELECT config_value FROM configuration WHERE config_key = 'radio_name'");
$radio_name_row = $stmt->fetch();
$radio_name = $radio_name_row['config_value'] ?? 'Panda Truck Radio';

// Obtener configuración del video hero
$stmt = $db->query("SELECT hero_type, hero_video_url, hero_video_poster, hero_video_title, youtube_id, twitch_channel 
                    FROM player_config WHERE id = 1");
$hero = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hero) {
    $hero = [
        'hero_type' => 'mp4',
        'hero_video_url' => 'https://panda-truck-video.b-cdn.net/AIRES%20DE%20MI%20TIERRA.mp4',
        'hero_video_poster' => 'https://f005.backblazeb2.com/file/mixes-mp3/portadas/video+portada2.png',
        'hero_video_title' => 'AIRES DE MI TIERRA',
        'youtube_id' => '',
        'twitch_channel' => ''
    ];
}

// Obtener banners activos para sidebar (sin fechas)
$sidebar_banners = [];

try {
    $stmt = $db->prepare("SELECT * FROM banners 
                          WHERE active = 1 
                          AND type = 'sidebar'
                          ORDER BY position ASC, id ASC");
    $stmt->execute();
    $sidebar_banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $sidebar_banners = [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo SITE_TITLE; ?> - La Casa de los DJs en Panamá</title>
    <meta name="description" content="<?php echo SITE_DESCRIPTION; ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            --primary: #e1261d;
            --primary-hover: #c81e16;
        }
        .text-primary { color: var(--primary); }
        .bg-primary { background-color: var(--primary); }
        .bg-primary:hover { background-color: var(--primary-hover); }
        .hover\:bg-primary-hover:hover { background-color: var(--primary-hover); }
        .border-primary { border-color: var(--primary); }
        
        .toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: #e1261d;
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            font-size: 14px;
            z-index: 1000;
            transition: transform 0.3s ease;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        .toast.show {
            transform: translateX(-50%) translateY(0);
        }
        .mix-card:hover { transform: translateY(-5px); transition: 0.3s; }
        .video-card:hover { transform: translateY(-5px); transition: 0.3s; }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        .live-pulse {
            animation: pulse 1.5s infinite;
        }
        
        .radio-player {
            background: linear-gradient(135deg, rgba(0,0,0,0.8) 0%, rgba(225,38,29,0.1) 100%);
            backdrop-filter: blur(10px);
        }
        .radio-controls button {
            transition: all 0.2s ease;
        }
        .radio-controls button:hover {
            transform: scale(1.05);
        }
        .volume-slider {
            width: 80px;
            height: 4px;
            -webkit-appearance: none;
            background: #333;
            border-radius: 2px;
            outline: none;
        }
        .volume-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary);
            cursor: pointer;
        }
        
        .footer-link {
            transition: all 0.3s ease;
            display: inline-block;
        }
        .footer-link:hover {
            color: var(--primary);
            transform: translateX(5px);
        }
        .social-icon {
            transition: all 0.3s ease;
        }
        .social-icon:hover {
            background-color: var(--primary);
            transform: translateY(-3px);
        }
        
        .main-layout {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }
        
        @media (min-width: 1024px) {
            .main-layout {
                flex-direction: row;
                align-items: flex-start;
            }
            .content-main {
                flex: 3;
            }
            .sidebar-ads {
                flex: 1;
                position: sticky;
                top: 100px;
            }
        }
        
        .ad-banner {
            transition: transform 0.2s ease, opacity 0.2s ease;
        }
        .ad-banner:hover {
            transform: translateY(-2px);
            opacity: 0.95;
        }
        
        @media (max-width: 768px) {
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
                text-align: center;
            }
            .footer-logo {
                justify-content: center;
            }
            .social-icons {
                justify-content: center;
            }
            .footer-links {
                text-align: center;
            }
            .footer-links ul li a {
                justify-content: center;
            }
        }
        
        @media (min-width: 769px) and (max-width: 1024px) {
            .footer-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 2rem;
            }
            .footer-brand {
                grid-column: span 2;
            }
        }
    </style>
</head>
<body class="bg-neutral-950 text-white">
    <!-- Header -->
    <header class="sticky top-0 z-40 bg-neutral-900/90 backdrop-blur border-b border-neutral-800">
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-2">
                <img src="<?php echo SITE_LOGO; ?>" alt="Panda Truck" class="h-10">
                <span class="text-xl font-bold hidden sm:inline"><?php echo SITE_TITLE; ?></span>
            </a>
            
            <nav class="hidden md:flex items-center gap-6">
                <a href="#mixes" class="hover:text-primary transition">Mixes</a>
                <a href="#videos" class="hover:text-primary transition">Videos</a>
                <a href="albumes.php" class="hover:text-primary transition">Álbumes</a>
                <a href="#superpacks" class="hover:text-primary transition">Super Packs</a>
                <a href="#top-djs" class="hover:text-primary transition">Top DJs</a>
                <a href="GuíaDJs.php" class="px-4 py-2 bg-primary rounded-lg hover:bg-primary-hover transition">Sube tu mix</a>
                <a href="player/index.php" class="px-4 py-2 bg-neutral-800 rounded-lg hover:bg-primary transition flex items-center gap-2">
                    <i class="fas fa-headphones"></i> Reproductor
                </a>
                <a href="player/video.php" class="px-4 py-2 bg-neutral-800 rounded-lg hover:bg-primary transition flex items-center gap-2">
                    <i class="fas fa-video"></i> Videos
                </a>
            </nav>
            
            <button id="mobileBtn" class="md:hidden p-2 rounded-xl border border-neutral-800">☰</button>
        </div>
        
        <div id="mobileMenu" class="md:hidden hidden border-t border-neutral-800">
            <div class="max-w-7xl mx-auto px-4 py-3 space-y-2">
                <a href="#mixes" class="block px-3 py-2 rounded-lg hover:bg-neutral-900">Mixes</a>
                <a href="#videos" class="block px-3 py-2 rounded-lg hover:bg-neutral-900">Videos</a>
                <a href="albumes.php" class="block px-3 py-2 rounded-lg hover:bg-neutral-900">Álbumes</a>
                <a href="#superpacks" class="block px-3 py-2 rounded-lg hover:bg-neutral-900">Super Packs</a>
                <a href="#top-djs" class="block px-3 py-2 rounded-lg hover:bg-neutral-900">Top DJs</a>
                <a href="GuíaDJs.php" class="block px-3 py-2 rounded-lg bg-primary text-center">Sube tu mix</a>
                <a href="player/index.php" class="block px-3 py-2 rounded-lg bg-neutral-800 text-center">🎧 Reproductor</a>
                <a href="player/video.php" class="block px-3 py-2 rounded-lg bg-neutral-800 text-center">🎬 Videos</a>
            </div>
        </div>
    </header>
    
    <main class="max-w-7xl mx-auto px-4 py-8 pb-20">
        <!-- Hero Section con video multi-plataforma -->
        <section class="bg-gradient-to-r from-primary/20 to-transparent rounded-2xl p-8 mb-12">
            <div class="grid md:grid-cols-2 gap-8 items-center">
                <div>
                    <h1 class="text-4xl md:text-5xl font-bold mb-4">
                        La Casa de los <span class="text-primary">DJs</span> en Panamá
                    </h1>
                    <p class="text-neutral-300 mb-6">
                        <?php echo SITE_DESCRIPTION; ?>
                    </p>
                    <div class="flex flex-wrap gap-4">
                        <a href="#mixes" class="px-6 py-3 bg-primary rounded-xl hover:bg-primary-hover transition">Explorar Mixes</a>
                        <a href="player/video.php" class="px-6 py-3 bg-neutral-800 rounded-xl hover:bg-primary transition flex items-center gap-2">
                            <i class="fas fa-video"></i> Ver Videos
                        </a>
                    </div>
                </div>
                <div class="bg-neutral-800 rounded-2xl p-4">
                    <div class="aspect-video bg-black rounded-lg overflow-hidden relative" id="hero-video-container">
                        <?php if ($hero['hero_type'] === 'youtube' && !empty($hero['youtube_id'])): ?>
                            <iframe id="hero-video" 
                                    class="w-full h-full"
                                    src="https://www.youtube.com/embed/<?php echo $hero['youtube_id']; ?>?autoplay=1&rel=0&modestbranding=1&showinfo=0&controls=1&mute=1"
                                    frameborder="0" 
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen>
                            </iframe>
                        <?php elseif ($hero['hero_type'] === 'twitch' && !empty($hero['twitch_channel'])): ?>
                            <iframe id="hero-video"
                                    class="w-full h-full"
                                    src="https://player.twitch.tv/?channel=<?php echo $hero['twitch_channel']; ?>&parent=localhost&autoplay=true"
                                    frameborder="0"
                                    allowfullscreen>
                            </iframe>
                        <?php else: ?>
                            <video id="hero-video" poster="<?php echo htmlspecialchars($hero['hero_video_poster']); ?>" controls class="w-full h-full" autoplay muted>
                                <source src="<?php echo htmlspecialchars($hero['hero_video_url']); ?>" type="video/mp4">
                                Tu navegador no soporta video.
                            </video>
                        <?php endif; ?>
                    </div>
                    <div class="mt-2 text-center">
                        <p class="text-xs text-neutral-500"><?php echo htmlspecialchars($hero['hero_video_title']); ?></p>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- RADIO ONLINE SECTION -->
        <section id="radio" class="mb-12">
            <div class="radio-player rounded-2xl p-6 border border-primary/30">
                <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-full bg-primary/20 flex items-center justify-center">
                            <div class="w-10 h-10 rounded-full bg-primary flex items-center justify-center animate-pulse">
                                <i class="fas fa-broadcast-tower text-white text-xl"></i>
                            </div>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold"><?php echo htmlspecialchars($radio_name); ?></h3>
                            <p class="text-sm text-neutral-400">Escucha la mejor música 24/7</p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                                </span>
                                <span class="text-xs text-neutral-500">EN VIVO</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex flex-col items-center gap-3">
                        <div class="flex items-center gap-4 radio-controls">
                            <button id="radioPlayBtn" class="w-12 h-12 rounded-full bg-primary hover:bg-primary-hover transition flex items-center justify-center text-xl shadow-lg">
                                <i id="radioPlayIcon" class="fas fa-play"></i>
                            </button>
                            <button id="radioStopBtn" class="w-10 h-10 rounded-full bg-neutral-800 hover:bg-neutral-700 transition flex items-center justify-center">
                                <i class="fas fa-stop"></i>
                            </button>
                            <div class="flex items-center gap-2 ml-2">
                                <i class="fas fa-volume-up text-neutral-400 text-sm"></i>
                                <input type="range" id="radioVolume" class="volume-slider" min="0" max="100" value="70">
                            </div>
                        </div>
                        <div id="radioStatus" class="text-xs text-neutral-500 flex items-center gap-2">
                            <i class="fas fa-circle text-neutral-600 text-[8px]"></i>
                            Detenido - Haz clic en play para escuchar
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Stats Banner -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-12">
            <div class="bg-neutral-900 rounded-xl p-4 text-center border border-neutral-800">
                <div class="text-2xl font-bold text-primary" id="total-mixes"><?php echo number_format($total_mixes); ?></div>
                <div class="text-sm text-neutral-400">Mixes</div>
            </div>
            <div class="bg-neutral-900 rounded-xl p-4 text-center border border-neutral-800">
                <div class="text-2xl font-bold text-primary" id="total-djs"><?php echo number_format($total_djs); ?></div>
                <div class="text-sm text-neutral-400">DJs Activos</div>
            </div>
            <div class="bg-neutral-900 rounded-xl p-4 text-center border border-neutral-800">
                <div class="text-2xl font-bold text-primary" id="total-videos"><?php echo number_format($total_videos); ?></div>
                <div class="text-sm text-neutral-400">Videos</div>
            </div>
            <div class="bg-neutral-900 rounded-xl p-4 text-center border border-neutral-800">
                <div class="text-2xl font-bold text-primary" id="total-downloads"><?php echo number_format($total_downloads); ?></div>
                <div class="text-sm text-neutral-400">Descargas</div>
            </div>
            <div class="bg-neutral-900 rounded-xl p-4 text-center border border-neutral-800">
                <div class="text-2xl font-bold text-primary" id="total-plays"><?php echo number_format($total_plays); ?></div>
                <div class="text-sm text-neutral-400">Reproducciones</div>
            </div>
        </div>
        
        <!-- Layout con Sidebar Publicidad -->
        <div class="main-layout">
            <!-- Contenido principal -->
            <div class="content-main">
                <!-- Mixes Destacados -->
                <section id="mixes" class="mb-12">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold">🎵 Últimos Mixes</h2>
                        <a href="mixes.php" class="text-primary hover:text-primary-hover transition">Ver todos →</a>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($mixes_destacados as $mix): ?>
                        <div class="mix-card bg-neutral-900 rounded-xl overflow-hidden border border-neutral-800 hover:border-primary transition group" data-mix-id="<?php echo $mix['id']; ?>">
                            <div class="aspect-square relative overflow-hidden">
                                <img src="<?php echo htmlspecialchars($mix['cover'] ?? 'assets/img/default-cover.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($mix['title']); ?>" 
                                     class="w-full h-full object-cover group-hover:scale-105 transition"
                                     onerror="this.src='assets/img/default-cover.jpg'">
                                <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-3">
                                    <a href="player/index.php?id=<?php echo $mix['id']; ?>" 
                                       class="w-12 h-12 rounded-full bg-primary flex items-center justify-center text-xl hover:scale-110 transition">
                                        ▶
                                    </a>
                                    <a href="api/download_mix.php?id=<?php echo $mix['id']; ?>" 
                                       class="download-btn w-12 h-12 rounded-full bg-neutral-800 flex items-center justify-center text-xl hover:bg-primary transition"
                                       title="Descargar mix">
                                        ⬇️
                                    </a>
                                </div>
                                <div class="absolute top-2 right-2 px-2 py-1 rounded-full bg-black/70 text-xs"><?php echo $mix['duration'] ?: '00:00'; ?></div>
                            </div>
                            <div class="p-4">
                                <h3 class="font-semibold truncate"><?php echo htmlspecialchars($mix['title']); ?></h3>
                                <p class="text-sm text-neutral-400 mt-1">Por <?php echo htmlspecialchars($mix['dj']); ?></p>
                                <div class="flex justify-between items-center mt-3 text-xs text-neutral-400">
                                    <span>▶️ <span class="play-count" id="play-<?php echo $mix['id']; ?>"><?php echo number_format($mix['plays'] ?? 0); ?></span></span>
                                    <span>⬇️ <span class="download-count" id="dl-<?php echo $mix['id']; ?>"><?php echo number_format($mix['downloads'] ?? 0); ?></span></span>
                                    <span><?php echo $mix['sizeMB'] ?? 0; ?> MB</span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                
                <!-- Videos Destacados -->
                <section id="videos" class="mb-12">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold">🎬 Videos Destacados</h2>
                        <a href="player/video.php" class="text-primary hover:text-primary-hover transition">Ver todos →</a>
                    </div>
                    <div id="videos-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div class="col-span-full text-center py-8 text-neutral-500">
                            <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                            <p>Cargando videos...</p>
                        </div>
                    </div>
                </section>
                
                <!-- Super Packs Section -->
                <section id="superpacks" class="mb-12">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold">🔥 Super Packs</h2>
                        <span class="text-sm text-neutral-400">DJs con 4 o más mixes</span>
                    </div>
                    <div id="superpacks-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <div class="col-span-full text-center py-8 text-neutral-500">
                            <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                            <p>Cargando Super Packs...</p>
                        </div>
                    </div>
                </section>
                
                <!-- Top DJs Section -->
                <section id="top-djs">
                    <h2 class="text-2xl font-bold mb-6">🏆 Top DJs de la Semana</h2>
                    <div id="top-djs-container" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                        <div class="col-span-full text-center py-8 text-neutral-500">
                            <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                            <p>Cargando Top DJs...</p>
                        </div>
                    </div>
                </section>
            </div>
            
            <!-- Sidebar Publicidad -->
            <?php if (!empty($sidebar_banners)): ?>
            <aside class="sidebar-ads">
                <div class="bg-neutral-900/50 rounded-2xl p-4 border border-primary/20 sticky top-24">
                    <h3 class="text-lg font-semibold mb-4 text-center flex items-center justify-center gap-2">
                        <i class="fas fa-ad text-primary"></i> Publicidad
                    </h3>
                    <div class="space-y-4">
                        <?php foreach ($sidebar_banners as $banner): ?>
                        <a href="<?php echo htmlspecialchars($banner['url']); ?>" 
                           class="ad-banner block group"
                           target="_blank"
                           onclick="registrarClickBanner(<?php echo $banner['id']; ?>)">
                            <img src="<?php echo htmlspecialchars($banner['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($banner['name']); ?>"
                                 class="w-full rounded-xl shadow-lg group-hover:shadow-primary/20 transition-all duration-300"
                                 style="max-width: 300px; margin: 0 auto;">
                            <p class="text-center text-xs text-neutral-500 mt-2 group-hover:text-primary transition">
                                <?php echo htmlspecialchars($banner['name']); ?>
                            </p>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- FOOTER MEJORADO Y ORGANIZADO -->
    <footer class="bg-neutral-900 border-t border-neutral-800 mt-12">
        <div class="max-w-7xl mx-auto px-4 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12 footer-grid">
                <div class="footer-brand">
                    <div class="flex items-center gap-2 mb-4 footer-logo justify-center md:justify-start">
                        <img src="<?php echo SITE_LOGO; ?>" alt="Panda Truck" class="h-12">
                        <span class="text-xl font-bold"><?php echo SITE_TITLE; ?></span>
                    </div>
                    <p class="text-neutral-400 text-sm leading-relaxed text-center md:text-left">
                        <?php echo FOOTER_TEXT; ?>
                    </p>
                    <div class="flex gap-4 mt-6 social-icons justify-center md:justify-start">
                        <a href="#" class="w-10 h-10 rounded-full bg-neutral-800 flex items-center justify-center text-neutral-400 hover:bg-primary hover:text-white transition-all social-icon">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-neutral-800 flex items-center justify-center text-neutral-400 hover:bg-primary hover:text-white transition-all social-icon">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-neutral-800 flex items-center justify-center text-neutral-400 hover:bg-primary hover:text-white transition-all social-icon">
                            <i class="fab fa-youtube"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-neutral-800 flex items-center justify-center text-neutral-400 hover:bg-primary hover:text-white transition-all social-icon">
                            <i class="fab fa-tiktok"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-neutral-800 flex items-center justify-center text-neutral-400 hover:bg-primary hover:text-white transition-all social-icon">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                </div>
                
                <div class="footer-links">
                    <h3 class="text-white font-semibold mb-4 text-center md:text-left">Enlaces Rápidos</h3>
                    <ul class="space-y-2">
                        <li><a href="#mixes" class="footer-link text-neutral-400 hover:text-primary text-sm block text-center md:text-left"><i class="fas fa-music w-5 text-primary mr-2"></i> Mixes Destacados</a></li>
                        <li><a href="#videos" class="footer-link text-neutral-400 hover:text-primary text-sm block text-center md:text-left"><i class="fas fa-video w-5 text-primary mr-2"></i> Videos</a></li>
                        <li><a href="albumes.php" class="footer-link text-neutral-400 hover:text-primary text-sm block text-center md:text-left"><i class="fas fa-compact-disc w-5 text-primary mr-2"></i> Álbumes</a></li>
                        <li><a href="#superpacks" class="footer-link text-neutral-400 hover:text-primary text-sm block text-center md:text-left"><i class="fas fa-box-open w-5 text-primary mr-2"></i> Super Packs</a></li>
                        <li><a href="#top-djs" class="footer-link text-neutral-400 hover:text-primary text-sm block text-center md:text-left"><i class="fas fa-trophy w-5 text-primary mr-2"></i> Top DJs</a></li>
                        <li><a href="player/index.php" class="footer-link text-neutral-400 hover:text-primary text-sm block text-center md:text-left"><i class="fas fa-headphones w-5 text-primary mr-2"></i> Reproductor</a></li>
                        <li><a href="player/video.php" class="footer-link text-neutral-400 hover:text-primary text-sm block text-center md:text-left"><i class="fas fa-film w-5 text-primary mr-2"></i> Videos</a></li>
                    </ul>
                </div>
                
                <div class="footer-links">
                    <h3 class="text-white font-semibold mb-4 text-center md:text-left">Para DJs</h3>
                    <ul class="space-y-2">
                        <li><a href="GuíaDJs.php" class="footer-link text-neutral-400 hover:text-primary text-sm block text-center md:text-left"><i class="fas fa-book-open w-5 text-primary mr-2"></i> Guía para DJs</a></li>
                        <li><a href="GuíaDJs.php#requisitos" class="footer-link text-neutral-400 hover:text-primary text-sm block text-center md:text-left"><i class="fas fa-check-circle w-5 text-primary mr-2"></i> Requisitos</a></li>
                        <li><a href="GuíaDJs.php#branding" class="footer-link text-neutral-400 hover:text-primary text-sm block text-center md:text-left"><i class="fas fa-palette w-5 text-primary mr-2"></i> Branding</a></li>
                        <li><a href="GuíaDJs.php#contacto" class="footer-link text-neutral-400 hover:text-primary text-sm block text-center md:text-left"><i class="fas fa-envelope w-5 text-primary mr-2"></i> Envía tu mix</a></li>
                    </ul>
                </div>
                
                <div class="footer-links">
                    <h3 class="text-white font-semibold mb-4 text-center md:text-left">Contacto</h3>
                    <ul class="space-y-3">
                        <li class="flex items-center gap-3 justify-center md:justify-start">
                            <i class="fab fa-whatsapp text-primary w-5"></i>
                            <a href="https://wa.me/<?php echo GUIA_WHATSAPP; ?>" class="text-neutral-400 hover:text-primary text-sm transition">+<?php echo GUIA_WHATSAPP; ?></a>
                        </li>
                        <li class="flex items-center gap-3 justify-center md:justify-start">
                            <i class="far fa-envelope text-primary w-5"></i>
                            <a href="mailto:info@pandatruckreloaded.com" class="text-neutral-400 hover:text-primary text-sm transition">info@pandatruckreloaded.com</a>
                        </li>
                        <li class="flex items-center gap-3 justify-center md:justify-start">
                            <i class="fas fa-map-marker-alt text-primary w-5"></i>
                            <span class="text-neutral-400 text-sm">Panamá, República de Panamá</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-neutral-800 pt-8">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                    <p class="text-neutral-500 text-sm">
                        © <?php echo date('Y'); ?> <?php echo SITE_TITLE; ?>. Todos los derechos reservados.
                    </p>
                    <div class="flex gap-6 text-xs text-neutral-500">
                        <a href="#" class="hover:text-primary transition">Términos y Condiciones</a>
                        <a href="#" class="hover:text-primary transition">Política de Privacidad</a>
                        <a href="#" class="hover:text-primary transition">Contacto</a>
                    </div>
                </div>
                <p class="text-center text-neutral-600 text-xs mt-4">
                    <i class="fas fa-music mr-1"></i> La casa de los DJs en Panamá | Descarga música gratis | Mixes de DJs panameños
                </p>
            </div>
        </div>
    </footer>
    
    <div id="toast" class="toast"></div>
    
    <script>
        function registrarClickBanner(bannerId) {
            fetch('api/registrar_click_banner.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + bannerId
            }).catch(err => console.log('Error registrando click:', err));
        }
        
        // ==================== RADIO ====================
        const radioAudio = new Audio('<?php echo $radio_url; ?>');
        const radioPlayBtn = document.getElementById('radioPlayBtn');
        const radioStopBtn = document.getElementById('radioStopBtn');
        const radioVolume = document.getElementById('radioVolume');
        const radioPlayIcon = document.getElementById('radioPlayIcon');
        const radioStatus = document.getElementById('radioStatus');
        
        let isPlaying = false;
        radioAudio.volume = 0.7;
        
        function updateRadioUI() {
            if (isPlaying) {
                radioPlayIcon.className = 'fas fa-pause';
                radioStatus.innerHTML = '<i class="fas fa-circle text-green-500 text-[8px] animate-pulse"></i> Reproduciendo en vivo';
                radioStatus.classList.add('text-green-400');
                radioStatus.classList.remove('text-neutral-500');
            } else {
                radioPlayIcon.className = 'fas fa-play';
                radioStatus.innerHTML = '<i class="fas fa-circle text-neutral-600 text-[8px]"></i> Detenido - Haz clic en play para escuchar';
                radioStatus.classList.remove('text-green-400');
                radioStatus.classList.add('text-neutral-500');
            }
        }
        
        radioPlayBtn.addEventListener('click', () => {
            if (isPlaying) {
                radioAudio.pause();
                isPlaying = false;
            } else {
                radioAudio.play().catch(e => {
                    console.log('Error al reproducir:', e);
                    radioStatus.innerHTML = '<i class="fas fa-exclamation-triangle text-yellow-500"></i> Error al cargar la radio.';
                });
                isPlaying = true;
            }
            updateRadioUI();
        });
        
        radioStopBtn.addEventListener('click', () => {
            radioAudio.pause();
            radioAudio.currentTime = 0;
            isPlaying = false;
            updateRadioUI();
        });
        
        radioVolume.addEventListener('input', (e) => {
            radioAudio.volume = e.target.value / 100;
        });
        
        radioAudio.addEventListener('playing', () => { isPlaying = true; updateRadioUI(); });
        radioAudio.addEventListener('pause', () => { isPlaying = false; updateRadioUI(); });
        
        // ==================== FUNCIONES AUXILIARES ====================
        function showToast(message, isError = false, duration = 3000) {
            const toast = document.getElementById('toast');
            if (!toast) return;
            toast.textContent = message;
            toast.style.background = isError ? '#dc2626' : '#e1261d';
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), duration);
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // ==================== ESTADÍSTICAS TOTALES ====================
        function updateTotalStats() {
            fetch('api/get_stats.php')
                .then(res => res.json())
                .then(data => {
                    const totalMixes = document.getElementById('total-mixes');
                    const totalDjs = document.getElementById('total-djs');
                    const totalVideos = document.getElementById('total-videos');
                    const totalDownloads = document.getElementById('total-downloads');
                    const totalPlays = document.getElementById('total-plays');
                    
                    if (totalMixes) totalMixes.textContent = (data.total_mixes || 0).toLocaleString();
                    if (totalDjs) totalDjs.textContent = (data.total_djs || 0).toLocaleString();
                    if (totalVideos) totalVideos.textContent = (data.total_videos || 0).toLocaleString();
                    if (totalDownloads) totalDownloads.textContent = (data.total_downloads || 0).toLocaleString();
                    if (totalPlays) totalPlays.textContent = (data.total_plays || 0).toLocaleString();
                    
                    if (data.mixes) {
                        for (const [id, stats] of Object.entries(data.mixes)) {
                            const playSpan = document.getElementById(`play-${id}`);
                            const dlSpan = document.getElementById(`dl-${id}`);
                            if (playSpan) playSpan.textContent = stats.plays.toLocaleString();
                            if (dlSpan) dlSpan.textContent = stats.downloads.toLocaleString();
                        }
                    }
                })
                .catch(err => console.error('Error cargando estadísticas:', err));
        }
        
        // ==================== VIDEOS ====================
        function loadVideos() {
            const container = document.getElementById('videos-container');
            if (!container) return;
            
            container.innerHTML = `
                <div class="col-span-full text-center py-8 text-neutral-500">
                    <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                    <p>Cargando videos...</p>
                </div>
            `;
            
            fetch('api/get_videos.php')
                .then(res => res.json())
                .then(videos => {
                    if (videos && videos.length > 0) {
                        const videosOrdenados = videos.sort((a, b) => b.id - a.id);
                        const videosLimitados = videosOrdenados.slice(0, 9);
                        
                        container.innerHTML = videosLimitados.map(video => `
                            <a href="player/video.php?id=${video.id}" class="group">
                                <div class="video-card bg-neutral-900 rounded-xl overflow-hidden border border-neutral-800 hover:border-primary transition">
                                    <div class="aspect-video relative">
                                        <img src="${video.cover || 'assets/img/default-video.jpg'}" 
                                             class="w-full h-full object-cover group-hover:scale-105 transition"
                                             onerror="this.src='assets/img/default-video.jpg'">
                                        <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center">
                                            <div class="w-12 h-12 rounded-full bg-primary flex items-center justify-center">
                                                <i class="fas fa-play text-white"></i>
                                            </div>
                                        </div>
                                        <div class="absolute top-2 right-2 bg-black/70 text-xs px-2 py-1 rounded-full">${video.duration || '00:00'}</div>
                                    </div>
                                    <div class="p-4">
                                        <h3 class="font-semibold truncate group-hover:text-primary">${escapeHtml(video.title)}</h3>
                                        <p class="text-sm text-neutral-400 mt-1">${escapeHtml(video.dj)}</p>
                                        <div class="flex justify-between mt-2 text-xs text-neutral-500">
                                            <span><i class="fas fa-play"></i> ${(video.plays || 0).toLocaleString()}</span>
                                            <span>${video.type === 'youtube' ? 'YouTube' : (video.type === 'bunny' ? 'Bunny.net' : 'MP4')}</span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        `).join('');
                    } else {
                        container.innerHTML = `
                            <div class="col-span-full text-center py-8 text-neutral-500">
                                <i class="fas fa-video text-4xl mb-2"></i>
                                <p>No hay videos disponibles</p>
                                <p class="text-sm mt-2">Pronto agregaremos nuevos videos</p>
                            </div>
                        `;
                    }
                })
                .catch(err => {
                    console.error('Error cargando videos:', err);
                    container.innerHTML = `
                        <div class="col-span-full text-center py-8 text-neutral-500">
                            <i class="fas fa-exclamation-triangle text-4xl mb-2"></i>
                            <p>Error al cargar videos</p>
                            <p class="text-sm mt-2">Intenta recargar la página</p>
                        </div>
                    `;
                });
        }
        
        // ==================== TOP DJS ====================
        function loadTopDJs() {
            const container = document.getElementById('top-djs-container');
            if (!container) return;
            
            container.innerHTML = `
                <div class="col-span-full text-center py-8 text-neutral-500">
                    <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                    <p>Cargando Top DJs...</p>
                </div>
            `;
            
            fetch('api/get_stats.php')
                .then(res => res.json())
                .then(data => {
                    if (data.top_djs && data.top_djs.length > 0) {
                        container.innerHTML = data.top_djs.map((dj, index) => `
                            <a href="dj/perfil.php?dj=${encodeURIComponent(dj.name)}" class="group">
                                <div class="bg-neutral-900 rounded-xl p-4 text-center border border-neutral-800 hover:border-primary transition">
                                    <div class="relative inline-block">
                                        <div class="w-20 h-20 rounded-full bg-neutral-800 mx-auto mb-3 overflow-hidden">
                                            <img src="${dj.avatar || 'assets/img/default-avatar.jpg'}" 
                                                 alt="${escapeHtml(dj.name)}" 
                                                 class="w-full h-full object-cover group-hover:scale-110 transition"
                                                 onerror="this.src='assets/img/default-avatar.jpg'">
                                        </div>
                                        ${index === 0 ? '<div class="absolute -top-2 -right-2 text-2xl">👑</div>' : ''}
                                    </div>
                                    <h4 class="font-semibold text-sm group-hover:text-primary transition truncate">${escapeHtml(dj.name)}</h4>
                                    <p class="text-xs text-neutral-400 mt-1"><i class="fas fa-download"></i> ${(dj.total_downloads || 0).toLocaleString()} descargas</p>
                                    <span class="mt-3 inline-block text-xs text-primary opacity-0 group-hover:opacity-100 transition">Ver perfil →</span>
                                </div>
                            </a>
                        `).join('');
                    } else {
                        container.innerHTML = `
                            <div class="col-span-full text-center py-8 text-neutral-500">
                                <i class="fas fa-chart-line text-4xl mb-2"></i>
                                <p>No hay datos de DJs disponibles</p>
                            </div>
                        `;
                    }
                })
                .catch(err => {
                    console.error('Error cargando Top DJs:', err);
                    container.innerHTML = `
                        <div class="col-span-full text-center py-8 text-neutral-500">
                            <i class="fas fa-exclamation-triangle text-4xl mb-2"></i>
                            <p>Error al cargar Top DJs</p>
                        </div>
                    `;
                });
        }
        
        // ==================== SUPER PACKS ====================
        function loadSuperPacks() {
            const container = document.getElementById('superpacks-container');
            if (!container) return;
            
            container.innerHTML = `
                <div class="col-span-full text-center py-8 text-neutral-500">
                    <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                    <p>Cargando Super Packs...</p>
                </div>
            `;
            
            fetch('api/get_stats.php')
                .then(res => res.json())
                .then(data => {
                    if (data.super_packs && data.super_packs.length > 0) {
                        container.innerHTML = data.super_packs.map(pack => `
                            <a href="dj/superpack.php?dj=${encodeURIComponent(pack.dj)}" class="block group">
                                <div class="bg-gradient-to-r from-primary/10 to-transparent rounded-xl p-6 border border-primary/30 hover:border-primary transition">
                                    <div class="flex items-center gap-4">
                                        <div class="text-4xl group-hover:scale-110 transition">📦</div>
                                        <div>
                                            <h3 class="text-xl font-bold group-hover:text-primary transition">${escapeHtml(pack.dj)}</h3>
                                            <p class="text-sm text-neutral-400">${pack.mix_count} mixes exclusivos</p>
                                            <p class="text-xs text-neutral-500 mt-1">📅 ${pack.last_mix_date ? new Date(pack.last_mix_date).toLocaleDateString() : 'Reciente'}</p>
                                        </div>
                                    </div>
                                    <div class="mt-4 flex justify-between items-center">
                                        <span class="text-sm text-primary group-hover:underline">Ver Super Pack →</span>
                                        <span class="text-xs bg-primary/20 px-2 py-1 rounded-full">🔥 SUPER PACK</span>
                                    </div>
                                </div>
                            </a>
                        `).join('');
                    } else {
                        container.innerHTML = `
                            <div class="col-span-full text-center py-8 text-neutral-500">
                                <i class="fas fa-box-open text-4xl mb-2"></i>
                                <p>Próximamente nuevos Super Packs</p>
                                <p class="text-sm mt-2">DJs con 4 o más mixes aparecerán aquí</p>
                            </div>
                        `;
                    }
                })
                .catch(err => {
                    console.error('Error cargando Super Packs:', err);
                    container.innerHTML = `
                        <div class="col-span-full text-center py-8 text-neutral-500">
                            <i class="fas fa-exclamation-triangle text-4xl mb-2"></i>
                            <p>Error al cargar Super Packs</p>
                        </div>
                    `;
                });
        }
        
        // ==================== MENÚ MÓVIL ====================
        const mobileBtn = document.getElementById('mobileBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        if (mobileBtn && mobileMenu) {
            mobileBtn.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
        }
        
        // ==================== VIDEO HERO ====================
        const heroVideo = document.getElementById('hero-video');
        if (heroVideo && heroVideo.tagName === 'VIDEO') {
            heroVideo.muted = true;
            heroVideo.play().catch(e => console.log('Video autoplay blocked'));
        }
        
        // ==================== SMOOTH SCROLL ====================
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
        
        // ==================== INICIALIZAR ====================
        updateTotalStats();
        loadVideos();
        loadTopDJs();
        loadSuperPacks();
        
        setInterval(() => {
            updateTotalStats();
            loadVideos();
            loadTopDJs();
            loadSuperPacks();
        }, 30000);
        
        console.log('✅ Panda Truck Reloaded - Inicializado correctamente');
        console.log('📻 Radio URL: <?php echo $radio_url; ?>');
    </script>
</body>
</html>