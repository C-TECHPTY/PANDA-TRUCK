<?php
// albumes.php - Catálogo de álbumes completos con descarga ZIP directa
require_once 'includes/config.php';
require_once 'includes/track_visit.php';

$db = getDB();
trackVisit('albumes');

// Obtener todos los álbumes activos
$stmt = $db->query("SELECT * FROM albumes WHERE active = 1 ORDER BY year DESC, id DESC");
$albumes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas
$stmt = $db->query("SELECT COUNT(*) as total FROM albumes WHERE active = 1");
$total_albumes = $stmt->fetch()['total'];

$stmt = $db->query("SELECT SUM(download_count) as total FROM albumes");
$total_downloads = $stmt->fetch()['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Álbumes - Panda Truck Reloaded</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
        .border-primary { border-color: var(--primary); }
        
        .album-card {
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #1a1a1a 0%, #0f0f0f 100%);
        }
        .album-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            border-color: var(--primary);
        }
        .track-list {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease-out;
            background: rgba(0,0,0,0.3);
            border-radius: 12px;
        }
        .track-list.open {
            max-height: 500px;
            transition: max-height 0.5s ease-in;
        }
        .track-item {
            transition: all 0.2s;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .track-item:hover {
            background: rgba(225,38,29,0.1);
            padding-left: 16px;
        }
        .btn-download-album {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            transition: all 0.3s;
        }
        .btn-download-album:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 25px -5px rgba(16,185,129,0.3);
        }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
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
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .loading-spinner {
            animation: spin 1s linear infinite;
        }
    </style>
</head>
<body class="bg-neutral-950 text-white">
    <!-- Header -->
    <header class="sticky top-0 z-40 bg-neutral-900/90 backdrop-blur border-b border-neutral-800">
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="index.php" class="flex items-center gap-2">
                <img src="assets/img/logo.png" alt="Panda Truck" class="h-10">
                <span class="text-xl font-bold hidden sm:inline">Panda Truck <span class="text-primary">Reloaded</span></span>
            </a>
            
            <nav class="hidden md:flex items-center gap-6">
                <a href="index.php#mixes" class="hover:text-primary transition">Mixes</a>
                <a href="index.php#videos" class="hover:text-primary transition">Videos</a>
                <a href="albumes.php" class="text-primary transition">Álbumes</a>
                <a href="index.php#superpacks" class="hover:text-primary transition">Super Packs</a>
                <a href="index.php#top-djs" class="hover:text-primary transition">Top DJs</a>
                <a href="GuíaDJs.php" class="px-4 py-2 bg-primary rounded-lg hover:bg-primary-hover transition">Sube tu mix</a>
                <a href="player/index.php" class="px-4 py-2 bg-neutral-800 rounded-lg hover:bg-primary transition flex items-center gap-2">
                    <i class="fas fa-headphones"></i> Reproductor
                </a>
            </nav>
            
            <button id="mobileBtn" class="md:hidden p-2 rounded-xl border border-neutral-800">☰</button>
        </div>
        
        <div id="mobileMenu" class="md:hidden hidden border-t border-neutral-800">
            <div class="max-w-7xl mx-auto px-4 py-3 space-y-2">
                <a href="index.php#mixes" class="block px-3 py-2 rounded-lg hover:bg-neutral-900">Mixes</a>
                <a href="index.php#videos" class="block px-3 py-2 rounded-lg hover:bg-neutral-900">Videos</a>
                <a href="albumes.php" class="block px-3 py-2 rounded-lg bg-primary text-center">Álbumes</a>
                <a href="index.php#superpacks" class="block px-3 py-2 rounded-lg hover:bg-neutral-900">Super Packs</a>
                <a href="index.php#top-djs" class="block px-3 py-2 rounded-lg hover:bg-neutral-900">Top DJs</a>
                <a href="GuíaDJs.php" class="block px-3 py-2 rounded-lg bg-primary text-center">Sube tu mix</a>
                <a href="player/index.php" class="block px-3 py-2 rounded-lg bg-neutral-800 text-center">🎧 Reproductor</a>
            </div>
        </div>
    </header>
    
    <main class="max-w-7xl mx-auto px-4 py-8">
        <!-- Hero Section -->
        <section class="bg-gradient-to-r from-primary/20 to-transparent rounded-2xl p-8 mb-12 text-center md:text-left">
            <div class="grid md:grid-cols-2 gap-8 items-center">
                <div>
                    <h1 class="text-4xl md:text-5xl font-bold mb-4">
                        Álbumes <span class="text-primary">Completos</span>
                    </h1>
                    <p class="text-neutral-300 mb-6">
                        Descarga discos completos en formato ZIP. Todos los temas de tus artistas favoritos en un solo archivo.
                    </p>
                    <div class="flex gap-4 justify-center md:justify-start">
                        <a href="#albumes" class="px-6 py-3 bg-primary rounded-xl hover:bg-primary-hover transition">Explorar Álbumes</a>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-2">
                    <div class="bg-neutral-800 rounded-xl p-4 text-center">
                        <div class="text-3xl font-bold text-primary"><?php echo $total_albumes; ?></div>
                        <div class="text-xs text-neutral-400">Álbumes</div>
                    </div>
                    <div class="bg-neutral-800 rounded-xl p-4 text-center">
                        <div class="text-3xl font-bold text-primary"><?php echo number_format($total_downloads); ?></div>
                        <div class="text-xs text-neutral-400">Descargas</div>
                    </div>
                    <div class="bg-neutral-800 rounded-xl p-4 text-center">
                        <div class="text-3xl font-bold text-primary">🎵</div>
                        <div class="text-xs text-neutral-400">Discos Completos</div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Álbumes Grid -->
        <section id="albumes" class="mb-12">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold">📀 Discografía</h2>
                <span class="text-sm text-neutral-400"><?php echo $total_albumes; ?> álbumes disponibles</span>
            </div>
            
            <?php if (count($albumes) > 0): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach ($albumes as $album): 
                    // Obtener canciones del álbum
                    $stmt = $db->prepare("SELECT * FROM canciones WHERE album_id = :id ORDER BY track_number ASC");
                    $stmt->bindValue(':id', $album['id']);
                    $stmt->execute();
                    $canciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
                ?>
                <div class="album-card rounded-2xl overflow-hidden border border-neutral-800 hover:border-primary transition-all duration-300">
                    <!-- Portada del álbum -->
                    <div class="relative group">
                        <img src="<?php echo htmlspecialchars($album['cover'] ?? 'assets/img/default-album.jpg'); ?>" 
                             alt="<?php echo htmlspecialchars($album['title']); ?>"
                             class="w-full aspect-square object-cover"
                             onerror="this.src='assets/img/default-album.jpg'">
                        <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-all duration-300 flex items-center justify-center gap-4">
                            <button onclick="toggleTrackList(<?php echo $album['id']; ?>)" 
                                    class="w-12 h-12 rounded-full bg-neutral-800 hover:bg-primary transition flex items-center justify-center text-xl">
                                <i class="fas fa-list"></i>
                            </button>
                            <!-- Botón de descarga - Usa ZIP directo si existe, sino usa API -->
                            <?php if (!empty($album['zip_url'])): ?>
                                <a href="<?php echo htmlspecialchars($album['zip_url']); ?>" 
                                   class="w-12 h-12 rounded-full bg-green-600 hover:bg-green-700 transition flex items-center justify-center text-xl"
                                   download>
                                    <i class="fas fa-download"></i>
                                </a>
                            <?php else: ?>
                                <a href="api/download_album.php?id=<?php echo $album['id']; ?>" 
                                   class="w-12 h-12 rounded-full bg-green-600 hover:bg-green-700 transition flex items-center justify-center text-xl"
                                   download>
                                    <i class="fas fa-download"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="absolute top-3 right-3 bg-black/70 backdrop-blur px-2 py-1 rounded-full text-xs">
                            <i class="fas fa-music mr-1"></i> <?php echo count($canciones); ?> temas
                        </div>
                    </div>
                    
                    <!-- Información del álbum -->
                    <div class="p-5">
                        <div class="flex items-start justify-between mb-2">
                            <h3 class="text-xl font-bold group-hover:text-primary transition truncate"><?php echo htmlspecialchars($album['title']); ?></h3>
                        </div>
                        <p class="text-neutral-400 text-sm mb-2">
                            <i class="fas fa-user-musician mr-1"></i> <?php echo htmlspecialchars($album['artist']); ?>
                        </p>
                        
                        <!-- DESCRIPCIÓN DEL ÁLBUM -->
                        <?php if (!empty($album['description'])): ?>
                        <div class="mt-2 mb-3 text-sm text-neutral-400 line-clamp-2">
                            <i class="fas fa-align-left mr-1 text-primary"></i> 
                            <?php echo nl2br(htmlspecialchars($album['description'])); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex flex-wrap gap-2 mb-4">
                            <span class="text-xs bg-neutral-800 px-2 py-1 rounded-full">
                                <i class="far fa-calendar-alt mr-1"></i> <?php echo $album['year']; ?>
                            </span>
                            <span class="text-xs bg-neutral-800 px-2 py-1 rounded-full">
                                <i class="fas fa-tag mr-1"></i> <?php echo htmlspecialchars($album['genre']); ?>
                            </span>
                            <span class="text-xs bg-neutral-800 px-2 py-1 rounded-full">
                                <i class="fas fa-download mr-1"></i> <?php echo number_format($album['download_count']); ?> descargas
                            </span>
                        </div>
                        
                        <!-- Botón de descarga principal - Usa ZIP directo si existe, sino usa API -->
                        <?php if (!empty($album['zip_url'])): ?>
                            <a href="<?php echo htmlspecialchars($album['zip_url']); ?>" 
                               class="btn-download-album w-full py-2 rounded-lg text-white font-semibold flex items-center justify-center gap-2"
                               download>
                                <i class="fas fa-file-archive"></i> Descargar ZIP (<?php echo count($canciones); ?> temas)
                            </a>
                        <?php else: ?>
                            <a href="api/download_album.php?id=<?php echo $album['id']; ?>" 
                               class="btn-download-album w-full py-2 rounded-lg text-white font-semibold flex items-center justify-center gap-2"
                               download>
                                <i class="fas fa-file-archive"></i> Descargar ZIP (<?php echo count($canciones); ?> temas)
                            </a>
                        <?php endif; ?>
                        
                        <!-- Lista de canciones (oculta inicialmente) -->
                        <div id="tracklist-<?php echo $album['id']; ?>" class="track-list mt-4">
                            <div class="p-3 space-y-1">
                                <h4 class="text-sm font-semibold text-primary mb-2 flex items-center gap-2">
                                    <i class="fas fa-list-ul"></i> Lista de Canciones
                                </h4>
                                <?php foreach ($canciones as $cancion): ?>
                                <div class="track-item flex justify-between items-center py-2 px-2 rounded-lg text-sm">
                                    <div class="flex items-center gap-3">
                                        <span class="text-neutral-500 w-6"><?php echo str_pad($cancion['track_number'], 2, '0', STR_PAD_LEFT); ?></span>
                                        <span class="text-neutral-300"><?php echo htmlspecialchars($cancion['title']); ?></span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="text-neutral-500 text-xs"><?php echo $cancion['duration']; ?></span>
                                        <a href="<?php echo htmlspecialchars($cancion['url']); ?>" 
                                           class="text-neutral-500 hover:text-primary transition"
                                           download
                                           onclick="event.stopPropagation()">
                                            <i class="fas fa-download text-xs"></i>
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="bg-neutral-900 rounded-2xl p-12 text-center border border-neutral-800">
                <i class="fas fa-compact-disc text-6xl text-neutral-700 mb-4"></i>
                <h3 class="text-xl font-semibold mb-2">Próximamente</h3>
                <p class="text-neutral-400">Estamos preparando álbumes completos para descargar.</p>
            </div>
            <?php endif; ?>
        </section>
        
        <!-- Banner de promoción -->
        <section class="bg-gradient-to-r from-primary/20 to-transparent rounded-2xl p-8 text-center">
            <h3 class="text-2xl font-bold mb-2">¿Eres DJ o Productor?</h3>
            <p class="text-neutral-300 mb-4">¿Tienes un álbum completo para compartir? Contáctanos y lo subiremos a nuestra plataforma.</p>
            <a href="GuíaDJs.php#contacto" class="inline-block px-6 py-3 bg-primary rounded-xl hover:bg-primary-hover transition">
                <i class="fab fa-whatsapp mr-2"></i> Enviar mi álbum
            </a>
        </section>
    </main>
    
    <!-- Footer -->
    <footer class="bg-neutral-900 border-t border-neutral-800 mt-12 py-8 text-center text-neutral-500 text-sm">
        <p>Panda Truck Reloaded - La casa de los DJs en Panamá</p>
        <p class="mt-2">© <?php echo date('Y'); ?> Todos los derechos reservados</p>
    </footer>
    
    <div id="toast" class="toast"></div>
    
    <script>
        // Función para mostrar/ocultar lista de canciones
        function toggleTrackList(albumId) {
            const tracklist = document.getElementById(`tracklist-${albumId}`);
            if (tracklist.classList.contains('open')) {
                tracklist.classList.remove('open');
            } else {
                tracklist.classList.add('open');
            }
        }
        
        // Función para mostrar notificaciones
        function showToast(message, isError = false, duration = 3000) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.background = isError ? '#dc2626' : '#e1261d';
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), duration);
        }
        
        // Menú móvil
        const mobileBtn = document.getElementById('mobileBtn');
        const mobileMenu = document.getElementById('mobileMenu');
        if (mobileBtn && mobileMenu) {
            mobileBtn.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
        }
        
        // Interceptar clics en los enlaces de descarga para mostrar notificación
        document.querySelectorAll('.btn-download-album, .bg-green-600').forEach(btn => {
            btn.addEventListener('click', (e) => {
                setTimeout(() => {
                    showToast('📥 Descarga iniciada', false, 2000);
                }, 500);
            });
        });
        
        console.log('✅ Álbumes cargados correctamente');
    </script>
</body>
</html>
