<?php
// player/index.php - Reproductor de audio con auto-reproducción y diseño responsive
require_once '../includes/config.php';

$db = getDB();

$mix_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Obtener el mix específico si hay ID
if ($mix_id > 0) {
    $stmt = $db->prepare("SELECT * FROM mixes WHERE id = :id AND active = 1");
    $stmt->bindValue(':id', $mix_id);
    $stmt->execute();
    $current_mix = $stmt->fetch();
} else {
    $current_mix = null;
}

// Obtener todos los mixes para la playlist
$stmt = $db->prepare("SELECT * FROM mixes WHERE active = 1 ORDER BY date DESC");
$stmt->execute();
$all_mixes = $stmt->fetchAll();

// Si no hay mix seleccionado, usar el primero
if (!$current_mix && count($all_mixes) > 0) {
    $current_mix = $all_mixes[0];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title><?php echo $current_mix ? htmlspecialchars($current_mix['title']) : 'Reproductor'; ?> - Panda Truck Reloaded</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #e1261d;
            --primary-hover: #c81e16;
        }
        body { 
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
            min-height: 100vh;
        }
        
        /* Reproductor principal responsive */
        .player-card {
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(10px);
            border-radius: 1.5rem;
            overflow: hidden;
            border: 1px solid rgba(225, 38, 29, 0.3);
        }
        
        .cover-container {
            position: relative;
            background: linear-gradient(135deg, #1a1a1a, #0a0a0a);
        }
        
        .cover-image {
            width: 100%;
            aspect-ratio: 1 / 1;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .cover-image:hover {
            transform: scale(1.02);
        }
        
        /* Controles táctiles mejorados */
        .control-btn {
            transition: all 0.2s ease;
            -webkit-tap-highlight-color: transparent;
        }
        
        .control-btn:active {
            transform: scale(0.95);
        }
        
        /* Sliders táctiles */
        .time-slider, .volume-slider {
            -webkit-appearance: none;
            height: 4px;
            background: #404040;
            border-radius: 2px;
            outline: none;
            cursor: pointer;
        }
        
        .time-slider::-webkit-slider-thumb, .volume-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: var(--primary);
            cursor: pointer;
            box-shadow: 0 0 4px rgba(225, 38, 29, 0.5);
        }
        
        /* Playlist responsive */
        .playlist-container {
            max-height: 400px;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .playlist-container::-webkit-scrollbar {
            width: 4px;
        }
        
        .playlist-container::-webkit-scrollbar-track {
            background: #1f1f1f;
            border-radius: 2px;
        }
        
        .playlist-container::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 2px;
        }
        
        .playlist-item {
            transition: all 0.2s ease;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }
        
        .playlist-item:active {
            background: rgba(225, 38, 29, 0.2);
        }
        
        .playlist-item.active {
            background: rgba(225, 38, 29, 0.2);
            border-left: 3px solid var(--primary);
        }
        
        /* Animaciones */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .playing-pulse {
            animation: pulse 2s infinite;
        }
        
        .auto-play-badge {
            position: fixed;
            bottom: 100px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0,0,0,0.9);
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 13px;
            z-index: 100;
            backdrop-filter: blur(8px);
            white-space: nowrap;
            animation: fadeOut 3s forwards;
            pointer-events: none;
            border: 1px solid var(--primary);
        }
        
        @keyframes fadeOut {
            0% { opacity: 1; transform: translateX(-50%) translateY(0); }
            70% { opacity: 1; }
            100% { opacity: 0; transform: translateX(-50%) translateY(-20px); display: none; }
        }
        
        /* Estilos específicos para móvil */
        @media (max-width: 768px) {
            .player-card {
                border-radius: 1rem;
            }
            
            .control-btn {
                width: 44px !important;
                height: 44px !important;
            }
            
            #play-btn {
                width: 60px !important;
                height: 60px !important;
            }
            
            .playlist-item {
                padding: 12px;
            }
            
            .playlist-item .w-12 {
                width: 44px;
                height: 44px;
            }
            
            .auto-play-badge {
                bottom: 80px;
                font-size: 11px;
                white-space: nowrap;
                padding: 8px 16px;
            }
            
            .time-slider::-webkit-slider-thumb, .volume-slider::-webkit-slider-thumb {
                width: 18px;
                height: 18px;
            }
        }
        
        @media (max-width: 480px) {
            .playlist-container {
                max-height: 350px;
            }
            
            .control-btn {
                width: 40px !important;
                height: 40px !important;
                font-size: 0.9rem !important;
            }
            
            #play-btn {
                width: 56px !important;
                height: 56px !important;
                font-size: 1.2rem !important;
            }
            
            .playlist-item .text-sm {
                font-size: 0.75rem;
            }
        }
        
        /* Mejora para pantallas muy pequeñas */
        @media (max-width: 380px) {
            .flex.gap-3 {
                gap: 0.5rem;
            }
            
            .control-btn {
                width: 36px !important;
                height: 36px !important;
            }
            
            #play-btn {
                width: 50px !important;
                height: 50px !important;
            }
        }
        
        /* Botón de descarga */
        .btn-download {
            background-color: var(--primary);
            transition: all 0.2s;
        }
        
        .btn-download:active {
            transform: scale(0.95);
        }
        
        /* Header sticky mejorado */
        .sticky-header {
            background: rgba(15, 15, 15, 0.95);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="text-white">
    <div class="min-h-screen flex flex-col">
        <!-- Header responsive -->
        <header class="sticky-header border-b border-neutral-800 sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-3 sm:px-4 py-2.5 sm:py-3 flex justify-between items-center">
                <a href="../index.php" class="flex items-center gap-1 sm:gap-2 hover:text-primary transition active:opacity-70">
                    <i class="fas fa-arrow-left text-sm sm:text-base"></i>
                    <span class="text-xs sm:text-sm hidden xs:inline">Volver</span>
                </a>
                <div class="flex items-center gap-1 sm:gap-2">
                    <img src="../assets/img/logo.png" class="h-6 sm:h-8" onerror="this.src='https://via.placeholder.com/32?text=P'">
                    <span class="font-bold text-sm sm:text-base">Panda Truck <span class="text-primary">Reloaded</span></span>
                </div>
                <a href="../GuíaDJs.php" class="text-xs bg-primary px-2 sm:px-3 py-1 rounded-full hover:bg-primary-hover transition active:scale-95">Sube tu mix</a>
            </div>
        </header>

        <main class="flex-1 max-w-7xl mx-auto px-3 sm:px-4 py-4 sm:py-8 w-full">
            <div class="flex flex-col lg:flex-row gap-4 sm:gap-6 lg:gap-8">
                <!-- Reproductor Principal -->
                <div class="lg:w-2/3 w-full">
                    <div class="player-card">
                        <!-- Portada -->
                        <div class="cover-container relative">
                            <img id="cover-art" src="<?php echo htmlspecialchars($current_mix['cover'] ?? '../assets/img/default-cover.jpg'); ?>" 
                                 class="cover-image w-full aspect-square object-cover"
                                 onerror="this.src='../assets/img/default-cover.jpg'">
                            <div class="absolute top-3 right-3 sm:top-4 sm:right-4">
                                <a href="../api/download_mix.php?id=<?php echo $current_mix['id']; ?>" 
                                   class="btn-download w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-black/70 backdrop-blur flex items-center justify-center text-white hover:bg-primary transition active:scale-95"
                                   title="Descargar mix">
                                    <i class="fas fa-download text-xs sm:text-sm"></i>
                                </a>
                            </div>
                            <div class="absolute bottom-3 left-3 right-3 sm:bottom-4 sm:left-4 sm:right-4">
                                <div class="bg-black/70 backdrop-blur rounded-lg p-2 sm:p-3">
                                    <h1 id="track-title" class="text-sm sm:text-base md:text-lg font-bold truncate"><?php echo htmlspecialchars($current_mix['title'] ?? 'Selecciona un mix'); ?></h1>
                                    <p id="track-artist" class="text-xs sm:text-sm text-neutral-400 truncate"><?php echo htmlspecialchars($current_mix['dj'] ?? ''); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Controles -->
                        <div class="p-4 sm:p-6">
                            <!-- Barra de tiempo -->
                            <div class="mb-3 sm:mb-4">
                                <div class="flex justify-between text-xs text-neutral-400 mb-1 sm:mb-2">
                                    <span id="current-time">0:00</span>
                                    <span id="duration"><?php echo $current_mix['duration'] ?? '0:00'; ?></span>
                                </div>
                                <input type="range" id="time-slider" class="time-slider w-full" min="0" max="100" value="0">
                            </div>
                            
                            <!-- Botones principales - Responsive -->
                            <div class="flex items-center justify-center gap-2 sm:gap-3 mb-3 sm:mb-4 flex-wrap">
                                <button id="shuffle-btn" class="control-btn w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-neutral-800 hover:bg-primary transition text-base sm:text-lg active:scale-95" title="Aleatorio">
                                    <i class="fas fa-random"></i>
                                </button>
                                <button id="prev-btn" class="control-btn w-11 h-11 sm:w-14 sm:h-14 rounded-full bg-neutral-800 hover:bg-primary transition text-lg sm:text-xl active:scale-95" title="Anterior">
                                    <i class="fas fa-backward-step"></i>
                                </button>
                                <button id="play-btn" class="control-btn w-14 h-14 sm:w-20 sm:h-20 rounded-full bg-primary hover:bg-primary-hover transition text-xl sm:text-2xl active:scale-95" title="Play/Pausa">
                                    <i class="fas fa-play"></i>
                                </button>
                                <button id="next-btn" class="control-btn w-11 h-11 sm:w-14 sm:h-14 rounded-full bg-neutral-800 hover:bg-primary transition text-lg sm:text-xl active:scale-95" title="Siguiente">
                                    <i class="fas fa-forward-step"></i>
                                </button>
                                <button id="repeat-btn" class="control-btn w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-neutral-800 hover:bg-primary transition text-base sm:text-lg active:scale-95" title="Repetir">
                                    <i class="fas fa-repeat"></i>
                                </button>
                                <a href="../api/download_mix.php?id=<?php echo $current_mix['id']; ?>" 
                                   class="control-btn w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-neutral-800 hover:bg-primary transition flex items-center justify-center text-base sm:text-lg active:scale-95"
                                   title="Descargar mix">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                            
                            <!-- Volumen -->
                            <div class="flex items-center justify-center gap-2 sm:gap-3 mt-2 sm:mt-4">
                                <i id="volume-icon" class="fas fa-volume-up text-neutral-400 text-xs sm:text-sm"></i>
                                <input type="range" id="volume-slider" min="0" max="100" value="70" class="volume-slider w-24 sm:w-32">
                            </div>
                            
                            <!-- Info adicional -->
                            <div class="mt-3 sm:mt-4 text-center text-xs text-neutral-500 flex flex-wrap justify-center gap-1">
                                <span id="track-genre"><?php echo $current_mix['genre'] ?? ''; ?></span>
                                <span class="hidden xs:inline">•</span>
                                <span id="track-size"><?php echo ($current_mix['sizeMB'] ?? 0) . ' MB'; ?></span>
                                <span class="hidden xs:inline">•</span>
                                <a href="../api/download_mix.php?id=<?php echo $current_mix['id']; ?>" class="text-primary hover:underline text-xs">
                                    <i class="fas fa-download"></i> Descargar
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Playlist -->
                <div class="lg:w-1/3 w-full">
                    <div class="bg-neutral-900 rounded-xl sm:rounded-2xl border border-neutral-800 overflow-hidden">
                        <div class="p-3 sm:p-4 border-b border-neutral-800 flex justify-between items-center sticky top-0 bg-neutral-900 z-10">
                            <h3 class="font-bold text-sm sm:text-base flex items-center gap-1 sm:gap-2">
                                <i class="fas fa-list text-xs sm:text-sm"></i> 
                                <span>Lista</span>
                                <span class="text-xs text-neutral-500">(<?php echo count($all_mixes); ?>)</span>
                            </h3>
                            <a href="../api/download_all.php" class="text-xs bg-neutral-800 px-2 py-1 rounded hover:bg-primary transition active:scale-95" title="Descargar todos los mixes">
                                <i class="fas fa-download"></i> <span class="hidden xs:inline">Todos</span>
                            </a>
                        </div>
                        <div class="playlist-container overflow-y-auto" style="max-height: calc(100vh - 450px); min-height: 300px;">
                            <?php foreach ($all_mixes as $index => $mix): ?>
                            <div class="playlist-item p-2 sm:p-3 hover:bg-neutral-800 transition cursor-pointer <?php echo ($current_mix && $current_mix['id'] == $mix['id']) ? 'active' : ''; ?>" 
                                 data-id="<?php echo $mix['id']; ?>"
                                 data-url="<?php echo htmlspecialchars($mix['url']); ?>"
                                 data-title="<?php echo htmlspecialchars($mix['title']); ?>"
                                 data-dj="<?php echo htmlspecialchars($mix['dj']); ?>"
                                 data-cover="<?php echo htmlspecialchars($mix['cover']); ?>"
                                 data-duration="<?php echo $mix['duration'] ?? '00:00'; ?>"
                                 data-genre="<?php echo htmlspecialchars($mix['genre']); ?>"
                                 data-size="<?php echo $mix['sizeMB'] ?? 0; ?>">
                                <div class="flex gap-2 sm:gap-3">
                                    <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-lg overflow-hidden bg-neutral-800 flex-shrink-0">
                                        <img src="<?php echo htmlspecialchars($mix['cover'] ?? '../assets/img/default-cover.jpg'); ?>" 
                                             class="w-full h-full object-cover"
                                             onerror="this.src='../assets/img/default-cover.jpg'">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-xs sm:text-sm truncate"><?php echo htmlspecialchars($mix['title']); ?></div>
                                        <div class="text-xs text-neutral-400 truncate"><?php echo htmlspecialchars($mix['dj']); ?></div>
                                        <div class="text-xs text-neutral-500 mt-0.5 sm:mt-1 flex items-center gap-2">
                                            <span><?php echo $mix['duration'] ?? '00:00'; ?></span>
                                            <span>•</span>
                                            <span><?php echo $mix['sizeMB'] ?? 0; ?> MB</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        <a href="../api/download_mix.php?id=<?php echo $mix['id']; ?>" 
                                           class="text-neutral-500 hover:text-primary transition p-1 sm:p-2 active:scale-95"
                                           onclick="event.stopPropagation()"
                                           title="Descargar">
                                            <i class="fas fa-download text-xs sm:text-sm"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <footer class="border-t border-neutral-800 mt-4 sm:mt-8 py-3 sm:py-4 text-center text-neutral-500 text-[10px] sm:text-xs">
            <p>Panda Truck Reloaded - La casa de los DJs en Panamá</p>
        </footer>
    </div>
    
    <style>
        /* Clase auxiliar para pantallas muy pequeñas */
        @media (min-width: 480px) {
            .xs\:inline { display: inline; }
            .xs\:hidden { display: none; }
        }
        @media (max-width: 479px) {
            .xs\:inline { display: none; }
            .xs\:hidden { display: inline; }
        }
    </style>
    
    <script>
        // Elementos del DOM
        const audio = new Audio();
        audio.preload = 'none';
        const playBtn = document.getElementById('play-btn');
        const prevBtn = document.getElementById('prev-btn');
        const nextBtn = document.getElementById('next-btn');
        const shuffleBtn = document.getElementById('shuffle-btn');
        const repeatBtn = document.getElementById('repeat-btn');
        const timeSlider = document.getElementById('time-slider');
        const currentTimeSpan = document.getElementById('current-time');
        const durationSpan = document.getElementById('duration');
        const coverArt = document.getElementById('cover-art');
        const trackTitle = document.getElementById('track-title');
        const trackArtist = document.getElementById('track-artist');
        const trackGenre = document.getElementById('track-genre');
        const trackSize = document.getElementById('track-size');
        const volumeSlider = document.getElementById('volume-slider');
        const volumeIcon = document.getElementById('volume-icon');
        
        let playlist = [];
        let currentIndex = 0;
        let isPlaying = false;
        let isShuffle = false;
        let isRepeat = false;
        
        // Cargar playlist desde PHP
        <?php 
        $playlist_json = [];
        foreach ($all_mixes as $mix) {
            $playlist_json[] = [
                'id' => $mix['id'],
                'title' => $mix['title'],
                'dj' => $mix['dj'],
                'url' => $mix['url'],
                'cover' => $mix['cover'] ?? '../assets/img/default-cover.jpg',
                'duration' => $mix['duration'] ?? '00:00',
                'genre' => $mix['genre'],
                'sizeMB' => $mix['sizeMB'] ?? 0
            ];
        }
        ?>
        const playlistData = <?php echo json_encode($playlist_json); ?>;
        
        // Inicializar
        function initPlayer() {
            playlist = playlistData;
            
            // Buscar índice actual
            const currentId = <?php echo $current_mix['id'] ?? 0; ?>;
            if (currentId > 0) {
                currentIndex = playlist.findIndex(t => t.id == currentId);
                if (currentIndex === -1) currentIndex = 0;
            } else {
                currentIndex = 0;
            }
            
            loadTrack(currentIndex);
            
            // Eventos de audio
            audio.addEventListener('timeupdate', updateProgress);
            audio.addEventListener('ended', nextTrack);
            audio.addEventListener('play', () => {
                updatePlayButton(true);
                playBtn.classList.add('playing-pulse');
                countPlay(playlist[currentIndex].id);
            });
            audio.addEventListener('pause', () => {
                updatePlayButton(false);
                playBtn.classList.remove('playing-pulse');
            });
            audio.addEventListener('loadedmetadata', () => {
                durationSpan.textContent = formatTime(audio.duration);
                timeSlider.max = Math.floor(audio.duration);
            });
            audio.addEventListener('loadeddata', () => {
                timeSlider.max = Math.floor(audio.duration);
            });
            
            // Control de volumen
            volumeSlider.addEventListener('input', (e) => {
                const value = e.target.value / 100;
                audio.volume = value;
                updateVolumeIcon(value);
            });
            audio.volume = 0.7;
            
            // Control de tiempo manual
            timeSlider.addEventListener('input', (e) => {
                const seekTime = parseFloat(e.target.value);
                audio.currentTime = seekTime;
                currentTimeSpan.textContent = formatTime(seekTime);
            });
            
            // Controles
            playBtn.addEventListener('click', togglePlay);
            prevBtn.addEventListener('click', prevTrack);
            nextBtn.addEventListener('click', nextTrack);
            shuffleBtn.addEventListener('click', toggleShuffle);
            repeatBtn.addEventListener('click', toggleRepeat);
            
            // Click en playlist
            document.querySelectorAll('.playlist-item').forEach((item) => {
                item.addEventListener('click', () => {
                    const trackId = parseInt(item.dataset.id);
                    const newIndex = playlist.findIndex(t => t.id == trackId);
                    if (newIndex !== -1) {
                        currentIndex = newIndex;
                        loadTrack(currentIndex);
                        if (!isPlaying) {
                            togglePlay();
                        } else {
                            audio.play();
                        }
                    }
                });
            });
            
            // Auto-reproducción
            const urlParams = new URLSearchParams(window.location.search);
            const mixId = urlParams.get('id');
            
            if (mixId && mixId > 0) {
                const badge = document.createElement('div');
                badge.className = 'auto-play-badge';
                badge.innerHTML = '<i class="fas fa-play-circle mr-2"></i> Reproduciendo automáticamente';
                document.body.appendChild(badge);
                
                setTimeout(() => {
                    audio.play().then(() => {
                        isPlaying = true;
                        console.log('✅ Auto-reproducción iniciada');
                    }).catch(e => {
                        console.log('Auto-play bloqueado:', e);
                        badge.innerHTML = '<i class="fas fa-volume-off mr-2"></i> Haz clic en play';
                        badge.style.background = 'rgba(225,38,29,0.9)';
                        setTimeout(() => badge.remove(), 3000);
                    });
                }, 500);
            }
        }
        
        function countPlay(mixId) {
            fetch('../api/actualizar_estadisticas.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ itemId: mixId, itemType: 'mix', action: 'play' })
            }).catch(err => console.error('Error:', err));
        }
        
        function loadTrack(index) {
            if (index < 0) index = 0;
            if (index >= playlist.length) index = 0;
            
            const track = playlist[index];
            if (!track) return;
            
            audio.src = track.url;
            audio.load();
            
            // Actualizar UI
            coverArt.src = track.cover;
            trackTitle.textContent = track.title;
            trackArtist.textContent = track.dj;
            trackGenre.textContent = track.genre;
            trackSize.textContent = track.sizeMB + ' MB';
            currentTimeSpan.textContent = '0:00';
            timeSlider.value = 0;
            
            // Actualizar clase active en playlist
            document.querySelectorAll('.playlist-item').forEach((item) => {
                const trackId = parseInt(item.dataset.id);
                if (trackId === track.id) {
                    item.classList.add('active');
                    // Scroll al elemento activo en móvil
                    if (window.innerWidth < 768) {
                        item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }
                } else {
                    item.classList.remove('active');
                }
            });
            
            // Actualizar título de página
            document.title = `${track.title} - Panda Truck Reloaded`;
            
            // Actualizar botón de descarga en el reproductor
            const downloadBtn = document.querySelector('.player-card .btn-download');
            if (downloadBtn) {
                downloadBtn.href = `../api/download_mix.php?id=${track.id}`;
            }
            const downloadLink = document.querySelector('.player-card .control-btn[title="Descargar mix"]');
            if (downloadLink) {
                downloadLink.href = `../api/download_mix.php?id=${track.id}`;
            }
        }
        
        function togglePlay() {
            if (audio.paused) {
                audio.play();
                isPlaying = true;
            } else {
                audio.pause();
                isPlaying = false;
            }
        }
        
        function nextTrack() {
            if (isShuffle) {
                let newIndex;
                do {
                    newIndex = Math.floor(Math.random() * playlist.length);
                } while (newIndex === currentIndex && playlist.length > 1);
                currentIndex = newIndex;
            } else {
                currentIndex++;
                if (currentIndex >= playlist.length) {
                    currentIndex = 0;
                }
            }
            loadTrack(currentIndex);
            if (isPlaying) audio.play();
        }
        
        function prevTrack() {
            if (audio.currentTime > 3) {
                audio.currentTime = 0;
                timeSlider.value = 0;
            } else {
                currentIndex--;
                if (currentIndex < 0) {
                    currentIndex = playlist.length - 1;
                }
                loadTrack(currentIndex);
                if (isPlaying) audio.play();
            }
        }
        
        function updateProgress() {
            const duration = audio.duration;
            const currentTime = audio.currentTime;
            
            if (!isNaN(duration) && duration > 0 && isFinite(duration)) {
                timeSlider.value = currentTime;
                currentTimeSpan.textContent = formatTime(currentTime);
                durationSpan.textContent = formatTime(duration);
            }
        }
        
        function formatTime(seconds) {
            if (isNaN(seconds) || !isFinite(seconds)) return '0:00';
            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return `${mins}:${secs.toString().padStart(2, '0')}`;
        }
        
        function updatePlayButton(playing) {
            const icon = playBtn.querySelector('i');
            if (playing) {
                icon.className = 'fas fa-pause';
            } else {
                icon.className = 'fas fa-play';
            }
        }
        
        function toggleShuffle() {
            isShuffle = !isShuffle;
            if (isShuffle) {
                shuffleBtn.classList.add('bg-primary');
                shuffleBtn.classList.remove('bg-neutral-800');
            } else {
                shuffleBtn.classList.remove('bg-primary');
                shuffleBtn.classList.add('bg-neutral-800');
            }
        }
        
        function toggleRepeat() {
            isRepeat = !isRepeat;
            if (isRepeat) {
                repeatBtn.classList.add('bg-primary');
                repeatBtn.classList.remove('bg-neutral-800');
                audio.loop = true;
            } else {
                repeatBtn.classList.remove('bg-primary');
                repeatBtn.classList.add('bg-neutral-800');
                audio.loop = false;
            }
        }
        
        function updateVolumeIcon(value) {
            if (value === 0) volumeIcon.className = 'fas fa-volume-off';
            else if (value < 0.5) volumeIcon.className = 'fas fa-volume-down';
            else volumeIcon.className = 'fas fa-volume-up';
        }
        
        // Iniciar
        initPlayer();
    </script>
</body>
</html>
