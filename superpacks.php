<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Todos los Super Packs - Panda Truck Reloaded</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #e1261d; }
        .bg-primary { background-color: var(--primary); }
        .text-primary { color: var(--primary); }
        .superpack-card:hover { transform: translateY(-5px); transition: 0.3s; }
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
            <a href="index.php#superpacks" class="text-neutral-400 hover:text-primary transition">
                <i class="fas fa-arrow-left mr-2"></i> Volver
            </a>
        </div>
    </header>
    
    <main class="max-w-7xl mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl md:text-4xl font-bold mb-2 flex items-center gap-3">
                <i class="fas fa-boxes text-primary"></i>
                Todos los Super Packs
            </h1>
            <p class="text-neutral-400">Colecciones completas de DJs con 4 o más mixes exclusivos</p>
        </div>
        
        <div id="superpacks-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="col-span-full text-center py-12">
                <div class="inline-block w-8 h-8 border-2 border-primary border-t-transparent rounded-full animate-spin"></div>
                <p class="mt-2 text-neutral-500">Cargando Super Packs...</p>
            </div>
        </div>
    </main>
    
    <footer class="bg-neutral-900 border-t border-neutral-800 mt-12 py-8">
        <div class="max-w-7xl mx-auto px-4 text-center text-neutral-500 text-sm">
            © <?php echo date('Y'); ?> <?php echo SITE_TITLE; ?> - La casa de los DJs en Panamá
        </div>
    </footer>
    
    <script>
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        async function loadSuperPacks() {
            const container = document.getElementById('superpacks-container');
            
            try {
                const response = await fetch('api/get_stats.php');
                const data = await response.json();
                
                if (data.super_packs && data.super_packs.length > 0) {
                    container.innerHTML = data.super_packs.map(pack => `
                        <a href="dj/superpack.php?dj=${encodeURIComponent(pack.dj)}" class="block group">
                            <div class="superpack-card bg-gradient-to-r from-primary/10 to-transparent rounded-xl p-6 border border-primary/30 hover:border-primary transition-all duration-300">
                                <div class="flex items-center gap-4">
                                    <div class="text-5xl group-hover:scale-110 transition duration-300">📦</div>
                                    <div class="flex-1">
                                        <h3 class="text-xl font-bold group-hover:text-primary transition">${escapeHtml(pack.dj)}</h3>
                                        <div class="flex flex-wrap gap-3 mt-2">
                                            <p class="text-sm text-neutral-400">
                                                <i class="fas fa-music text-primary"></i> ${pack.mix_count} mixes exclusivos
                                            </p>
                                            <p class="text-sm text-neutral-400">
                                                <i class="fas fa-download text-primary"></i> ${(pack.total_downloads || 0).toLocaleString()} descargas
                                            </p>
                                        </div>
                                        <p class="text-xs text-neutral-500 mt-2">
                                            📅 Último mix: ${pack.last_mix_date ? new Date(pack.last_mix_date).toLocaleDateString() : 'Reciente'}
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-4 flex justify-between items-center">
                                    <span class="text-sm text-primary group-hover:underline flex items-center gap-2">
                                        Ver colección completa <i class="fas fa-arrow-right text-xs"></i>
                                    </span>
                                    <span class="text-xs bg-primary/20 px-3 py-1 rounded-full animate-pulse">
                                        🔥 SUPER PACK
                                    </span>
                                </div>
                            </div>
                        </a>
                    `).join('');
                } else {
                    container.innerHTML = `
                        <div class="col-span-full text-center py-12">
                            <i class="fas fa-box-open text-6xl text-neutral-700 mb-4"></i>
                            <p class="text-neutral-400 text-lg">No hay Super Packs disponibles</p>
                            <p class="text-neutral-500 text-sm mt-2">Los DJs con 4 o más mixes aparecerán aquí automáticamente</p>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error:', error);
                container.innerHTML = `
                    <div class="col-span-full text-center py-12">
                        <i class="fas fa-exclamation-triangle text-6xl text-red-500 mb-4"></i>
                        <p class="text-neutral-400">Error al cargar los Super Packs</p>
                        <button onclick="loadSuperPacks()" class="mt-4 px-4 py-2 bg-primary rounded-lg hover:bg-primary-hover transition">
                            Reintentar
                        </button>
                    </div>
                `;
            }
        }
        
        loadSuperPacks();
    </script>
</body>
</html>