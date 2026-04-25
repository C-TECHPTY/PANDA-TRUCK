<?php
// player/video.php - Reproductor con contador de reproducciones
require_once '../includes/config.php';

$db = getDB();
$video_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Obtener video específico si hay ID
if ($video_id > 0) {
    $stmt = $db->prepare("SELECT * FROM videos WHERE id = :id AND active = 1");
    $stmt->bindValue(':id', $video_id);
    $stmt->execute();
    $current_video = $stmt->fetch(PDO::FETCH_ASSOC);
} else {
    $current_video = null;
}

// Obtener todos los videos activos para la lista
$stmt = $db->query("SELECT * FROM videos WHERE active = 1 ORDER BY id DESC");
$all_videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Si no hay video seleccionado y hay videos, usar el primero
if (!$current_video && count($all_videos) > 0) {
    $current_video = $all_videos[0];
}

// IMPORTANTE: Registrar reproducción para MP4
if ($current_video && $current_video['type'] !== 'youtube') {
    // Iniciar sesión solo si no está activa
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    // Usar una cookie para evitar contar la misma reproducción múltiples veces en la misma sesión
    $session_key = 'video_played_' . $current_video['id'];
    if (!isset($_SESSION[$session_key])) {
        $_SESSION[$session_key] = true;
        
        $stmt = $db->prepare("UPDATE videos SET plays = plays + 1 WHERE id = :id");
        $stmt->bindValue(':id', $current_video['id']);
        $stmt->execute();
    }
}

// Extraer ID de YouTube
function getYoutubeId($url) {
    preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&]+)/', $url, $matches);
    return $matches[1] ?? '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $current_video ? htmlspecialchars($current_video['title']) : 'Videos'; ?> - Panda Truck Video</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .video-player {
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
        }
        .video-card:hover {
            transform: translateY(-4px);
            transition: all 0.3s ease;
        }
        .active-video {
            border-left: 3px solid #e1261d;
            background: rgba(225, 38, 29, 0.1);
        }
        .playlist-scroll {
            max-height: 500px;
            overflow-y: auto;
        }
        .playlist-scroll::-webkit-scrollbar {
            width: 6px;
        }
        .playlist-scroll::-webkit-scrollbar-track {
            background: #1f1f1f;
            border-radius: 3px;
        }
        .playlist-scroll::-webkit-scrollbar-thumb {
            background: #e1261d;
            border-radius: 3px;
        }
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
        }
        .toast.show {
            transform: translateX(-50%) translateY(0);
        }
        .btn-download {
            background-color: #e1261d;
            transition: all 0.2s;
        }
        .btn-download:hover {
            background-color: #c81e16;
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="bg-neutral-950 text-white">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-neutral-900/90 border-b border-neutral-800 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 py-3 flex justify-between items-center">
                <a href="../index.php" class="flex items-center gap-2 hover:text-primary transition">
                    <i class="fas fa-arrow-left"></i>
                    <span>Volver</span>
                </a>
                <div class="flex items-center gap-2">
                    <img src="../assets/img/logo.png" class="h-8">
                    <span class="font-bold">Panda Truck <span class="text-primary">Video</span></span>
                </div>
                <a href="../GuíaDJs.php" class="text-xs bg-primary px-3 py-1 rounded-full">Sube tu video</a>
            </div>
        </header>
        
        <main class="max-w-7xl mx-auto px-4 py-8">
            <?php if ($current_video): ?>
            <!-- Reproductor Principal -->
            <div class="grid lg:grid-cols-3 gap-8">
                <!-- Video Player -->
                <div class="lg:col-span-2">
                    <div class="video-player rounded-2xl overflow-hidden border border-neutral-800">
                        <div class="aspect-video bg-black relative">
                            <?php if ($current_video['type'] === 'youtube'): ?>
                                <?php $youtube_id = getYoutubeId($current_video['url']); ?>
                                <?php if ($youtube_id): ?>
                                <iframe class="w-full h-full" 
                                        src="https://www.youtube.com/embed/<?php echo $youtube_id; ?>?autoplay=1&rel=0&modestbranding=1&showinfo=0&controls=1&enablejsapi=1"
                                        frameborder="0" 
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        allowfullscreen
                                        id="youtube-player">
                                </iframe>
                                <?php else: ?>
                                <div class="flex items-center justify-center h-full bg-neutral-900">
                                    <div class="text-center">
                                        <i class="fas fa-exclamation-triangle text-4xl text-yellow-500 mb-2"></i>
                                        <p>Video no disponible</p>
                                        <a href="<?php echo htmlspecialchars($current_video['url']); ?>" target="_blank" class="text-primary text-sm mt-2 inline-block">Ver en YouTube</a>
                                    </div>
                                </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <video id="video-player" controls autoplay class="w-full h-full" onplay="countPlay(<?php echo $current_video['id']; ?>)">
                                    <source src="<?php echo htmlspecialchars($current_video['url']); ?>" type="video/mp4">
                                    Tu navegador no soporta video.
                                </video>
                            <?php endif; ?>
                        </div>
                        <div class="p-5">
                            <div class="flex flex-wrap justify-between items-start gap-4">
                                <div class="flex-1">
                                    <h1 class="text-xl font-bold"><?php echo htmlspecialchars($current_video['title']); ?></h1>
                                    <div class="flex flex-wrap items-center gap-4 mt-2 text-neutral-400 text-sm">
                                        <span><i class="fas fa-user-musician mr-1"></i> <?php echo htmlspecialchars($current_video['dj']); ?></span>
                                        <?php if ($current_video['type'] !== 'youtube'): ?>
                                        <span><i class="fas fa-play mr-1"></i> <span id="video-plays"><?php echo number_format($current_video['plays']); ?></span> reproducciones</span>
                                        <?php endif; ?>
                                        <?php if ($current_video['duration']): ?>
                                        <span><i class="fas fa-clock mr-1"></i> <?php echo $current_video['duration']; ?></span>
                                        <?php endif; ?>
                                        <span class="px-2 py-0.5 rounded-full text-xs <?php echo $current_video['type'] === 'youtube' ? 'bg-red-600/20 text-red-400' : 'bg-green-600/20 text-green-400'; ?>">
                                            <?php echo $current_video['type'] === 'youtube' ? 'YouTube' : 'MP4'; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- BOTÓN DE DESCARGA - SOLO PARA MP4 -->
                                <?php if ($current_video['type'] === 'mp4'): ?>
                                <div class="relative">
                                    <a href="../api/download_video.php?id=<?php echo $current_video['id']; ?>" 
                                       class="btn-download px-4 py-2 rounded-lg transition flex items-center gap-2 text-white"
                                       onclick="showToast('📥 Descargando video...')">
                                        <i class="fas fa-download"></i> Descargar Video
                                    </a>
                                </div>
                                <?php else: ?>
                                <div class="px-4 py-2 bg-neutral-700 rounded-lg text-neutral-400 cursor-not-allowed flex items-center gap-2">
                                    <i class="fas fa-download"></i> No disponible
                                    <span class="text-xs">(YouTube)</span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($current_video['type'] === 'youtube'): ?>
                            <div class="mt-4 p-3 bg-neutral-800/50 rounded-lg border border-neutral-700">
                                <p class="text-sm text-neutral-300">
                                    <i class="fas fa-info-circle text-primary mr-2"></i>
                                    Este video es de YouTube y no está disponible para descarga directa.
                                    <a href="<?php echo htmlspecialchars($current_video['url']); ?>" target="_blank" class="text-primary hover:underline">Ver en YouTube →</a>
                                </p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de Videos -->
                <div class="lg:col-span-1">
                    <div class="bg-neutral-900 rounded-2xl border border-neutral-800 overflow-hidden">
                        <div class="p-4 border-b border-neutral-800 flex justify-between items-center">
                            <h3 class="font-bold flex items-center gap-2">
                                <i class="fas fa-list"></i> Lista de Videos
                                <span class="text-xs text-neutral-500">(<?php echo count($all_videos); ?> videos)</span>
                            </h3>
                            <a href="video.php" class="text-xs text-primary hover:underline">Ver todos</a>
                        </div>
                        <div class="playlist-scroll">
                            <?php foreach ($all_videos as $video): ?>
                            <a href="video.php?id=<?php echo $video['id']; ?>" 
                               class="video-card block p-3 hover:bg-neutral-800 transition border-l-3 <?php echo ($current_video && $current_video['id'] == $video['id']) ? 'active-video' : 'border-l-transparent'; ?>">
                                <div class="flex gap-3">
                                    <div class="w-20 h-12 rounded-lg overflow-hidden bg-neutral-800 flex-shrink-0">
                                        <img src="<?php echo htmlspecialchars($video['cover'] ?? '../assets/img/default-video.jpg'); ?>" 
                                             class="w-full h-full object-cover"
                                             onerror="this.src='../assets/img/default-video.jpg'">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-sm truncate"><?php echo htmlspecialchars($video['title']); ?></div>
                                        <div class="text-xs text-neutral-400"><?php echo htmlspecialchars($video['dj']); ?></div>
                                        <div class="flex justify-between mt-1 text-xs text-neutral-500">
                                            <span><i class="fas fa-play"></i> <span id="play-<?php echo $video['id']; ?>"><?php echo number_format($video['plays']); ?></span></span>
                                            <span><?php echo $video['duration'] ?? '00:00'; ?></span>
                                        </div>
                                    </div>
                                    <?php if ($video['type'] === 'youtube'): ?>
                                    <i class="fab fa-youtube text-red-500 text-sm"></i>
                                    <?php endif; ?>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php else: ?>
            <!-- No hay videos -->
            <div class="text-center py-20">
                <i class="fas fa-video text-6xl text-neutral-700 mb-4"></i>
                <h2 class="text-2xl font-bold">No hay videos disponibles</h2>
                <p class="text-neutral-400 mt-2">Próximamente se agregarán nuevos videos.</p>
                <a href="../index.php" class="mt-6 inline-block px-6 py-3 bg-primary rounded-lg">Volver al inicio</a>
            </div>
            <?php endif; ?>
        </main>
        
        <!-- Footer -->
        <footer class="border-t border-neutral-800 mt-12 py-6 text-center text-neutral-500 text-sm">
            <p><?php echo SITE_TITLE; ?> - La casa de los DJs en Panamá</p>
        </footer>
    </div>
    
    <!-- Toast para notificaciones -->
    <div id="toast" class="toast"></div>
    
    <script>
        // Función para mostrar notificaciones
        function showToast(message, isError = false, duration = 3000) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.background = isError ? '#dc2626' : '#e1261d';
            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, duration);
        }
        
        // Función para contar reproducciones de video MP4
        let playCounted = false;
        
        function countPlay(videoId) {
            if (playCounted) return;
            playCounted = true;
            
            fetch('../api/actualizar_estadisticas_video.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    id: videoId, 
                    action: 'play' 
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const playSpan = document.getElementById('video-plays');
                    if (playSpan) {
                        playSpan.textContent = data.plays.toLocaleString();
                    }
                    // Actualizar también en la lista
                    const listPlaySpan = document.getElementById(`play-${videoId}`);
                    if (listPlaySpan) {
                        listPlaySpan.textContent = data.plays.toLocaleString();
                    }
                }
            })
            .catch(err => console.error('Error:', err));
        }
        
        // Actualizar estadísticas cada 30 segundos
        function updateStats() {
            fetch('../api/get_stats.php')
                .then(res => res.json())
                .then(data => {
                    if (data.videos) {
                        for (const [id, stats] of Object.entries(data.videos)) {
                            const playSpan = document.getElementById(`play-${id}`);
                            if (playSpan) {
                                playSpan.textContent = stats.plays.toLocaleString();
                            }
                            if (id == <?php echo $current_video ? $current_video['id'] : 0; ?> && document.getElementById('video-plays')) {
                                document.getElementById('video-plays').textContent = stats.plays.toLocaleString();
                            }
                        }
                    }
                })
                .catch(err => console.error('Error:', err));
        }
        
        setInterval(updateStats, 30000);
        
        console.log('✅ Reproductor de video inicializado');
    </script>
</body>
</html>