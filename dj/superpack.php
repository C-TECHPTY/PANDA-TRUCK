<?php
// dj/superpack.php - Super Pack con descarga en ZIP
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

// Obtener todos los mixes del DJ
$stmt = $db->prepare("SELECT * FROM mixes WHERE dj = :dj AND active = 1 ORDER BY date DESC");
$stmt->bindParam(':dj', $dj_name);
$stmt->execute();
$mixes = $stmt->fetchAll();

$mix_count = count($mixes);

// Verificar que tenga al menos 4 mixes (Super Pack)
if ($mix_count < 4) {
    header('Location: ../index.php');
    exit;
}

// Estadísticas totales
$stmt = $db->prepare("SELECT SUM(plays) as total_plays, SUM(downloads) as total_downloads 
                      FROM mixes WHERE dj = :dj AND active = 1");
$stmt->bindParam(':dj', $dj_name);
$stmt->execute();
$stats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Super Pack - <?php echo htmlspecialchars($dj_name); ?> - Panda Truck Reloaded</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .checkbox-select {
            accent-color: #e1261d;
            width: 20px;
            height: 20px;
            cursor: pointer;
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
            white-space: nowrap;
        }
        .toast.show {
            transform: translateX(-50%) translateY(0);
        }
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #fff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.6s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .btn-loading {
            opacity: 0.7;
            cursor: wait;
        }
    </style>
</head>
<body class="bg-neutral-950 text-white">
    <div class="max-w-7xl mx-auto px-4 py-8 pb-32">
        <!-- Header con botón volver -->
        <div class="flex items-center gap-4 mb-8">
            <a href="../index.php#superpacks" class="text-2xl hover:text-primary transition">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold">🔥 Super Pack</h1>
                <p class="text-neutral-400">Colección completa de <?php echo htmlspecialchars($dj_name); ?></p>
            </div>
        </div>
        
        <!-- Banner del DJ -->
        <div class="bg-gradient-to-r from-primary/20 to-transparent rounded-2xl p-6 mb-8">
            <div class="flex flex-col md:flex-row gap-6 items-center md:items-start">
                <div class="w-28 h-28 rounded-full bg-neutral-800 overflow-hidden border-4 border-primary">
                    <img src="<?php echo htmlspecialchars($dj['avatar'] ?? '../assets/img/default-avatar.jpg'); ?>" 
                         class="w-full h-full object-cover"
                         onerror="this.src='../assets/img/default-avatar.jpg'">
                </div>
                <div class="text-center md:text-left">
                    <h2 class="text-2xl font-bold"><?php echo htmlspecialchars($dj_name); ?></h2>
                    <p class="text-neutral-400"><?php echo $dj['genre'] ?? 'DJ'; ?> • <?php echo $dj['city'] ?? 'Panamá'; ?></p>
                    <div class="flex gap-6 mt-4 justify-center md:justify-start">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-primary"><?php echo $mix_count; ?></div>
                            <div class="text-xs text-neutral-400">Mixes Exclusivos</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-primary"><?php echo number_format($stats['total_plays'] ?? 0); ?></div>
                            <div class="text-xs text-neutral-400">Reproducciones</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-primary"><?php echo number_format($stats['total_downloads'] ?? 0); ?></div>
                            <div class="text-xs text-neutral-400">Descargas Totales</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Controles de selección masiva -->
        <div class="flex flex-wrap gap-3 mb-6">
            <button id="selectAllBtn" class="px-4 py-2 bg-primary rounded-lg hover:bg-primary-hover transition flex items-center gap-2">
                <i class="fas fa-check-square"></i> Seleccionar todos
            </button>
            <button id="downloadZipBtn" class="px-4 py-2 bg-green-600 rounded-lg hover:bg-green-700 transition flex items-center gap-2">
                <i class="fas fa-file-archive"></i> Descargar en ZIP (<span id="selectedCount">0</span>)
            </button>
            <button id="deselectAllBtn" class="px-4 py-2 bg-neutral-700 rounded-lg hover:bg-neutral-600 transition flex items-center gap-2">
                <i class="fas fa-times-circle"></i> Deseleccionar todos
            </button>
        </div>
        
        <!-- Lista de mixes -->
        <div class="bg-neutral-900 rounded-xl overflow-hidden">
            <div class="p-4 border-b border-neutral-800 flex justify-between items-center">
                <h3 class="font-semibold">📦 Colección Completa (<?php echo $mix_count; ?> mixes)</h3>
                <button id="downloadAllZipBtn" class="text-sm px-3 py-1 bg-primary/20 hover:bg-primary/30 rounded-lg transition flex items-center gap-2">
                    <i class="fas fa-download"></i> Descargar TODO el Pack en ZIP
                </button>
            </div>
            <div class="divide-y divide-neutral-800">
                <?php foreach ($mixes as $mix): ?>
                <div class="mix-item p-4 hover:bg-neutral-800/50 transition flex items-center justify-between">
                    <div class="flex items-center gap-4 flex-1 min-w-0">
                        <input type="checkbox" class="mix-checkbox checkbox-select" 
                               data-id="<?php echo $mix['id']; ?>"
                               data-url="<?php echo htmlspecialchars($mix['url']); ?>"
                               data-title="<?php echo htmlspecialchars($mix['title']); ?>"
                               data-dj="<?php echo htmlspecialchars($mix['dj']); ?>"
                               data-cover="<?php echo htmlspecialchars($mix['cover'] ?? ''); ?>"
                               data-duration="<?php echo htmlspecialchars($mix['duration'] ?? '00:00'); ?>">
                        <div class="w-16 h-16 rounded-lg overflow-hidden flex-shrink-0 bg-neutral-800">
                            <img src="<?php echo htmlspecialchars($mix['cover'] ?? '../assets/img/default-cover.jpg'); ?>" 
                                 class="w-full h-full object-cover"
                                 onerror="this.src='../assets/img/default-cover.jpg'">
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-semibold"><?php echo htmlspecialchars($mix['title']); ?></div>
                            <div class="text-sm text-neutral-400"><?php echo htmlspecialchars($mix['genre']); ?></div>
                            <div class="flex gap-4 mt-1 text-xs text-neutral-500">
                                <span><i class="fas fa-clock"></i> <?php echo $mix['duration'] ?? '00:00'; ?></span>
                                <span><i class="fas fa-database"></i> <?php echo $mix['sizeMB'] ?? 0; ?> MB</span>
                                <span><i class="fas fa-play"></i> <span id="play-<?php echo $mix['id']; ?>"><?php echo number_format($mix['plays'] ?? 0); ?></span></span>
                                <span><i class="fas fa-download"></i> <span id="dl-<?php echo $mix['id']; ?>"><?php echo number_format($mix['downloads'] ?? 0); ?></span></span>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <a href="../player/index.php?id=<?php echo $mix['id']; ?>" 
                           class="px-3 py-2 bg-primary/20 rounded-lg hover:bg-primary transition">
                            <i class="fas fa-play"></i>
                        </a>
                        <button class="single-download-btn px-3 py-2 bg-neutral-700 rounded-lg hover:bg-primary transition"
                                data-id="<?php echo $mix['id']; ?>"
                                data-url="<?php echo htmlspecialchars($mix['url']); ?>"
                                data-title="<?php echo htmlspecialchars($mix['title']); ?>"
                                data-dj="<?php echo htmlspecialchars($mix['dj']); ?>">
                            <i class="fas fa-download"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div id="toast" class="toast"></div>
    
    <script>
        const checkboxes = document.querySelectorAll('.mix-checkbox');
        const selectAllBtn = document.getElementById('selectAllBtn');
        const deselectAllBtn = document.getElementById('deselectAllBtn');
        const downloadZipBtn = document.getElementById('downloadZipBtn');
        const downloadAllZipBtn = document.getElementById('downloadAllZipBtn');
        const selectedCountSpan = document.getElementById('selectedCount');
        
        let isDownloading = false;
        
        function updateSelectedCount() {
            const selected = document.querySelectorAll('.mix-checkbox:checked').length;
            selectedCountSpan.textContent = selected;
        }
        
        function showToast(message, isError = false, duration = 4000) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.background = isError ? '#dc2626' : '#e1261d';
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), duration);
        }
        
        async function updateDownloadCount(mixId) {
            try {
                await fetch('../api/actualizar_estadisticas.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ itemId: mixId, itemType: 'mix', action: 'download' })
                });
                const dlSpan = document.getElementById(`dl-${mixId}`);
                if (dlSpan) {
                    let current = parseInt(dlSpan.textContent.replace(/,/g, '')) || 0;
                    dlSpan.textContent = (current + 1).toLocaleString();
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
        
        function downloadMix(url, mixId, title, dj) {
            const link = document.createElement('a');
            link.href = url;
            link.download = `${title} - ${dj}.mp3`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            updateDownloadCount(mixId);
            showToast(`📥 Descargando: ${title}`, false, 2000);
        }
        
        function downloadZip(mixIds, djName) {
            if (isDownloading) {
                showToast('⏳ Ya hay una descarga en proceso, espera un momento', true);
                return;
            }
            
            if (mixIds.length === 0) {
                showToast('⚠️ Selecciona al menos un mix para descargar', true);
                return;
            }
            
            isDownloading = true;
            downloadZipBtn.classList.add('btn-loading');
            
            showToast(`🔄 Preparando ZIP con ${mixIds.length} mixes... Esto puede tomar unos segundos`, false, 5000);
            
            const zipUrl = `../api/descargar_zip.php?ids=${mixIds.join(',')}&dj=${encodeURIComponent(djName)}`;
            
            const link = document.createElement('a');
            link.href = zipUrl;
            link.download = `${djName}_superpack_${new Date().toISOString().slice(0,10)}.zip`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            setTimeout(() => {
                isDownloading = false;
                downloadZipBtn.classList.remove('btn-loading');
            }, 5000);
            
            showToast(`✅ Iniciando descarga de ${mixIds.length} mixes en ZIP`, false, 3000);
        }
        
        checkboxes.forEach(cb => cb.addEventListener('change', updateSelectedCount));
        
        selectAllBtn.addEventListener('click', () => {
            checkboxes.forEach(cb => cb.checked = true);
            updateSelectedCount();
            showToast('✅ Todos los mixes seleccionados');
        });
        
        deselectAllBtn.addEventListener('click', () => {
            checkboxes.forEach(cb => cb.checked = false);
            updateSelectedCount();
            showToast('❌ Selección eliminada');
        });
        
        downloadZipBtn.addEventListener('click', () => {
            const selected = Array.from(document.querySelectorAll('.mix-checkbox:checked')).map(cb => cb.dataset.id);
            const djName = '<?php echo addslashes($dj_name); ?>';
            downloadZip(selected, djName);
        });
        
        if (downloadAllZipBtn) {
            downloadAllZipBtn.addEventListener('click', () => {
                const allIds = Array.from(document.querySelectorAll('.mix-checkbox')).map(cb => cb.dataset.id);
                const djName = '<?php echo addslashes($dj_name); ?>';
                
                if (confirm(`¿Descargar TODOS los ${allIds.length} mixes del Super Pack de ${djName} en un archivo ZIP?`)) {
                    downloadZip(allIds, djName);
                }
            });
        }
        
        document.querySelectorAll('.single-download-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const url = btn.dataset.url;
                const id = btn.dataset.id;
                const title = btn.dataset.title;
                const dj = btn.dataset.dj;
                downloadMix(url, id, title, dj);
            });
        });
        
        updateSelectedCount();
    </script>
</body>
</html>