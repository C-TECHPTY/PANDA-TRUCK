<?php
// lista_djs.php - Página completa con todos los DJs y buscador
require_once 'includes/config.php';
require_once 'includes/track_visit.php';
trackVisit('djs_list');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Todos los DJs - Panda Truck Reloaded</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #e1261d; --primary-hover: #c81e16; }
        .text-primary { color: var(--primary); }
        .bg-primary { background-color: var(--primary); }
        .bg-primary:hover { background-color: var(--primary-hover); }
        .border-primary { border-color: var(--primary); }
        .dj-card:hover { transform: translateY(-5px); transition: 0.3s; }
        .loading-spinner {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 3px solid rgba(225,38,29,0.3);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .pagination-btn {
            transition: all 0.2s ease;
        }
        .pagination-btn:hover:not(:disabled) {
            background-color: var(--primary);
            color: white;
        }
        .pagination-btn.active {
            background-color: var(--primary);
            color: white;
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
            <a href="index.php" class="text-neutral-400 hover:text-primary transition">
                <i class="fas fa-arrow-left mr-2"></i> Volver
            </a>
        </div>
    </header>
    
    <main class="max-w-7xl mx-auto px-4 py-8 pb-20">
        <!-- Título y buscador -->
        <div class="mb-8">
            <h1 class="text-3xl md:text-4xl font-bold mb-4 flex items-center gap-3">
                <i class="fas fa-headphones text-primary"></i>
                Todos los DJs
            </h1>
            <p class="text-neutral-400 mb-6">Descubre todos los DJs que forman parte de la familia Panda Truck Reloaded</p>
            
            <!-- Buscador -->
            <div class="flex flex-col sm:flex-row gap-4 items-center">
                <div class="relative flex-1 max-w-md">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-neutral-500"></i>
                    <input type="text" 
                           id="searchInput" 
                           placeholder="Buscar por nombre, género o ciudad..." 
                           class="w-full pl-10 pr-4 py-3 bg-neutral-900 rounded-xl border border-neutral-700 focus:border-primary focus:outline-none transition">
                </div>
                <div class="text-sm text-neutral-500" id="resultsCount"></div>
            </div>
        </div>
        
        <!-- Grid de DJs -->
        <div id="djsContainer" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <div class="col-span-full flex justify-center py-12">
                <div class="loading-spinner"></div>
            </div>
        </div>
        
        <!-- Paginación -->
        <div id="pagination" class="flex justify-center gap-2 mt-8 flex-wrap"></div>
    </main>
    
    <!-- Footer simplificado -->
    <footer class="bg-neutral-900 border-t border-neutral-800 mt-12 py-8">
        <div class="max-w-7xl mx-auto px-4 text-center text-neutral-500 text-sm">
            © <?php echo date('Y'); ?> <?php echo SITE_TITLE; ?> - La casa de los DJs en Panamá
        </div>
    </footer>
    
    <div id="toast" class="fixed bottom-5 left-1/2 -translate-x-1/2 bg-primary text-white px-5 py-2 rounded-full text-sm z-50 transition-all duration-300 opacity-0 pointer-events-none"></div>
    
    <script>
        let currentPage = 1;
        let currentSearch = '';
        let isLoading = false;
        
        function showToast(message, isError = false) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.background = isError ? '#dc2626' : '#e1261d';
            toast.style.opacity = '1';
            toast.style.transform = 'translateX(-50%) translateY(0)';
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(-50%) translateY(20px)';
            }, 3000);
        }
        
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function formatNumber(num) {
            if (!num) return '0';
            return num.toLocaleString();
        }
        
        async function loadDJs() {
            if (isLoading) return;
            isLoading = true;
            
            const container = document.getElementById('djsContainer');
            container.innerHTML = `
                <div class="col-span-full flex justify-center py-12">
                    <div class="loading-spinner"></div>
                </div>
            `;
            
            try {
                const url = `api/get_all_djs.php?page=${currentPage}&limit=12&search=${encodeURIComponent(currentSearch)}`;
                const response = await fetch(url);
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.error || 'Error al cargar DJs');
                }
                
                const djs = data.djs;
                const total = data.total;
                const totalPages = data.total_pages;
                
                // Actualizar contador de resultados
                const resultsCount = document.getElementById('resultsCount');
                if (resultsCount) {
                    resultsCount.textContent = `${total} DJ${total !== 1 ? 's' : ''} encontrado${total !== 1 ? 's' : ''}`;
                }
                
                if (djs.length === 0) {
                    container.innerHTML = `
                        <div class="col-span-full text-center py-12">
                            <i class="fas fa-search text-6xl text-neutral-700 mb-4"></i>
                            <p class="text-neutral-400 text-lg">No se encontraron DJs</p>
                            <p class="text-neutral-500 text-sm mt-2">Intenta con otra búsqueda</p>
                        </div>
                    `;
                } else {
                    container.innerHTML = djs.map(dj => `
                        <a href="dj/perfil.php?dj=${encodeURIComponent(dj.name)}" class="group">
                            <div class="dj-card bg-neutral-900 rounded-2xl overflow-hidden border border-neutral-800 hover:border-primary transition-all duration-300">
                                <div class="relative">
                                    <div class="aspect-square overflow-hidden bg-neutral-800">
                                        <img src="${escapeHtml(dj.avatar || 'assets/img/default-avatar.jpg')}" 
                                             alt="${escapeHtml(dj.name)}"
                                             class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                                             onerror="this.src='assets/img/default-avatar.jpg'">
                                    </div>
                                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent p-4">
                                        <div class="flex gap-2 text-xs">
                                            <span class="bg-primary/80 px-2 py-1 rounded-full">
                                                <i class="fas fa-music mr-1"></i> ${dj.total_mixes || 0} mixes
                                            </span>
                                            <span class="bg-black/60 px-2 py-1 rounded-full">
                                                <i class="fas fa-download"></i> ${formatNumber(dj.total_downloads)}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <h3 class="font-bold text-lg group-hover:text-primary transition truncate">
                                        ${escapeHtml(dj.name)}
                                    </h3>
                                    <div class="flex items-center gap-2 mt-1 text-sm text-neutral-400">
                                        <i class="fas fa-music text-primary text-xs"></i>
                                        <span>${escapeHtml(dj.genre || 'Variado')}</span>
                                        <span class="w-1 h-1 rounded-full bg-neutral-600"></span>
                                        <i class="fas fa-map-marker-alt text-primary text-xs"></i>
                                        <span>${escapeHtml(dj.city || 'Panamá')}</span>
                                    </div>
                                    ${dj.bio ? `
                                        <p class="text-xs text-neutral-500 mt-2 line-clamp-2">${escapeHtml(dj.bio.substring(0, 100))}${dj.bio.length > 100 ? '...' : ''}</p>
                                    ` : ''}
                                </div>
                            </div>
                        </a>
                    `).join('');
                }
                
                // Renderizar paginación
                renderPagination(currentPage, totalPages);
                
            } catch (error) {
                console.error('Error:', error);
                container.innerHTML = `
                    <div class="col-span-full text-center py-12">
                        <i class="fas fa-exclamation-triangle text-6xl text-red-500 mb-4"></i>
                        <p class="text-neutral-400">Error al cargar los DJs</p>
                        <button onclick="loadDJs()" class="mt-4 px-4 py-2 bg-primary rounded-lg hover:bg-primary-hover transition">
                            Reintentar
                        </button>
                    </div>
                `;
                showToast('Error al cargar los DJs', true);
            }
            
            isLoading = false;
        }
        
        function renderPagination(currentPage, totalPages) {
            const paginationContainer = document.getElementById('pagination');
            if (!paginationContainer) return;
            
            if (totalPages <= 1) {
                paginationContainer.innerHTML = '';
                return;
            }
            
            let pages = [];
            const maxVisible = 5;
            
            if (totalPages <= maxVisible) {
                for (let i = 1; i <= totalPages; i++) pages.push(i);
            } else {
                if (currentPage <= 3) {
                    pages = [1, 2, 3, 4, '...', totalPages];
                } else if (currentPage >= totalPages - 2) {
                    pages = [1, '...', totalPages - 3, totalPages - 2, totalPages - 1, totalPages];
                } else {
                    pages = [1, '...', currentPage - 1, currentPage, currentPage + 1, '...', totalPages];
                }
            }
            
            let html = `
                <button class="pagination-btn px-3 py-2 rounded-lg bg-neutral-800 hover:bg-primary transition ${currentPage === 1 ? 'opacity-50 cursor-not-allowed' : ''}" 
                        onclick="goToPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>
                    <i class="fas fa-chevron-left"></i>
                </button>
            `;
            
            for (let page of pages) {
                if (page === '...') {
                    html += `<span class="px-3 py-2 text-neutral-500">...</span>`;
                } else {
                    html += `
                        <button class="pagination-btn px-3 py-2 rounded-lg transition ${page === currentPage ? 'bg-primary text-white' : 'bg-neutral-800 hover:bg-primary/50'}" 
                                onclick="goToPage(${page})">
                            ${page}
                        </button>
                    `;
                }
            }
            
            html += `
                <button class="pagination-btn px-3 py-2 rounded-lg bg-neutral-800 hover:bg-primary transition ${currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : ''}" 
                        onclick="goToPage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>
                    <i class="fas fa-chevron-right"></i>
                </button>
            `;
            
            paginationContainer.innerHTML = html;
        }
        
        function goToPage(page) {
            if (page === currentPage) return;
            currentPage = page;
            loadDJs();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        // Buscador con debounce
        let searchTimeout;
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentSearch = e.target.value;
                    currentPage = 1;
                    loadDJs();
                }, 500);
            });
        }
        
        // Cargar inicial
        loadDJs();
    </script>
</body>
</html>
