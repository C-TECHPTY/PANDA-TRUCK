<?php
// dashboard.php - Panel de Control COMPLETO CON DISEÑO RESPONSIVE
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth->requireLogin();

$user_role = $_SESSION['user_role'];
$username = $_SESSION['username'];
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'] ?? '';
$user_avatar = $_SESSION['user_avatar'] ?? '';

$db = getDB();

// Obtener estadísticas del sistema
$system_stats = $auth->getSystemStats();

// Obtener logs de actividad (solo para superadmin)
$activity_logs = ($user_role === 'superadmin') ? $auth->getActivityLogs(20) : [];

// Obtener DJs para asignar
$djs_list = $auth->getDJsList();

// Obtener datos
$mixes = $db->query("SELECT * FROM mixes WHERE active = 1 ORDER BY id DESC")->fetchAll();
$djs = $db->query("SELECT * FROM djs WHERE active = 1 ORDER BY id DESC")->fetchAll();
$videos = $db->query("SELECT * FROM videos WHERE active = 1 ORDER BY id DESC")->fetchAll();
$events = $db->query("SELECT * FROM events ORDER BY date DESC")->fetchAll();
$banners = $db->query("SELECT * FROM banners ORDER BY id DESC")->fetchAll();

// Obtener configuración del sistema
$settings = [];
$stmt = $db->query("SELECT setting_key, setting_value FROM system_settings");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Obtener configuración del video hero
$stmt = $db->query("SELECT hero_type, hero_video_url, hero_video_poster, hero_video_title, youtube_id, twitch_channel 
                    FROM player_config WHERE id = 1");
$hero = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hero) {
    $hero = [
        'hero_type' => 'mp4',
        'hero_video_url' => '',
        'hero_video_poster' => '',
        'hero_video_title' => 'Video Destacado',
        'youtube_id' => '',
        'twitch_channel' => ''
    ];
}

// ============================================
// ESTADÍSTICAS CORREGIDAS - USANDO statistics
// ============================================

// Estadísticas por tipo (YA ESTÁ BIEN - usa statistics)
$stats_data = $db->query("SELECT item_type, SUM(plays) as total_plays, SUM(downloads) as total_downloads FROM statistics GROUP BY item_type")->fetchAll();

// Top 10 mixes más descargados (CORREGIDO - usa statistics)
$top_mixes = $db->query("
    SELECT 
        m.id, 
        m.title, 
        m.dj, 
        m.cover,
        COALESCE(SUM(s.downloads), 0) as downloads,
        COALESCE(SUM(s.plays), 0) as plays
    FROM mixes m
    LEFT JOIN statistics s ON m.id = s.item_id AND s.item_type = 'mix'
    WHERE m.active = 1
    GROUP BY m.id
    ORDER BY downloads DESC
    LIMIT 10
")->fetchAll();

// Top 5 DJs más descargados (CORREGIDO - usa statistics)
$top_djs_stats = $db->query("
    SELECT 
        d.name as dj,
        d.avatar,
        COALESCE(SUM(s.downloads), 0) as total_downloads,
        COALESCE(SUM(s.plays), 0) as total_plays,
        COUNT(DISTINCT m.id) as total_mixes
    FROM djs d
    INNER JOIN mixes m ON d.name = m.dj AND m.active = 1
    LEFT JOIN statistics s ON m.id = s.item_id AND s.item_type = 'mix'
    WHERE d.active = 1
    GROUP BY d.id
    ORDER BY total_downloads DESC
    LIMIT 5
")->fetchAll();

// Usuarios
$users = ($user_role === 'superadmin') ? $db->query("SELECT * FROM users ORDER BY id DESC")->fetchAll() : [];

// Obtener conteo de álbumes para el sidebar
$stmt = $db->query("SELECT COUNT(*) as total FROM albumes");
$total_albumes = $stmt->fetch()['total'] ?? 0;

// Obtener conteo de banners para el sidebar
$stmt = $db->query("SELECT COUNT(*) as total FROM banners");
$total_banners = $stmt->fetch()['total'] ?? 0;

// Obtener estado actual del modo mantenimiento
$maintenance_mode = $settings['maintenance_mode'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Dashboard - Panda Truck Reloaded</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ==================== ESTILOS RESPONSIVE ==================== */
        :root {
            --primary: #e1261d;
            --primary-hover: #c81e16;
        }
        
        /* Layout base */
        .dashboard-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        /* Sidebar responsive - en móvil se convierte en menú superior */
        .sidebar {
            width: 100%;
            background: linear-gradient(180deg, #0f0f0f 0%, #1a1a1a 100%);
            border-bottom: 1px solid #2d2d2d;
            position: sticky;
            top: 0;
            z-index: 40;
        }
        
        /* Header del sidebar (logo y usuario) */
        .sidebar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #2d2d2d;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .user-avatar {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            background: #2d2d2d;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .user-details {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            font-weight: bold;
            color: var(--primary);
            font-size: 0.875rem;
        }
        
        .user-role {
            font-size: 0.7rem;
            color: #9ca3af;
        }
        
        /* Navegación horizontal en móvil */
        .nav-menu {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding: 0.5rem 0.75rem;
            gap: 0.25rem;
            scrollbar-width: thin;
        }
        
        .nav-item {
            display: inline-flex !important;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .nav-item i {
            font-size: 0.875rem;
            width: 1.25rem;
        }
        
        .nav-item:hover {
            background: rgba(225, 38, 29, 0.1);
            color: #e1261d;
        }
        
        .nav-item.active {
            background: rgba(225, 38, 29, 0.2);
            color: #e1261d;
        }
        
        /* Botón de menú móvil */
        .menu-toggle {
            background: #2d2d2d;
            border: none;
            color: white;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Main content responsive */
        .main-content {
            flex: 1;
            padding: 0.75rem;
            overflow-x: hidden;
        }
        
        /* Tarjetas de estadísticas */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #1a1a1a 0%, #0f0f0f 100%);
            border: 1px solid #2d2d2d;
            border-radius: 0.75rem;
            padding: 0.75rem;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            border-color: var(--primary);
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary);
        }
        
        .stat-label {
            font-size: 0.7rem;
            color: #9ca3af;
        }
        
        /* Tablas responsive */
        .table-container {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 1rem;
        }
        
        table {
            min-width: 500px;
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 0.5rem;
            text-align: left;
            font-size: 0.75rem;
            border-bottom: 1px solid #2d2d2d;
        }
        
        th {
            background: rgba(45, 45, 45, 0.5);
            color: #e1261d;
        }
        
        /* Thumbnail */
        .thumbnail {
            width: 2rem;
            height: 2rem;
            object-fit: cover;
            border-radius: 0.375rem;
        }
        
        /* Modales responsive */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.9);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: #1a1a1a;
            border-radius: 1rem;
            max-width: 95%;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            padding: 1rem;
            border: 1px solid rgba(225, 38, 29, 0.3);
        }
        
        /* Formularios */
        input, textarea, select {
            font-size: 16px !important;
            background: #2d2d2d;
            border: 1px solid #404040;
            border-radius: 0.5rem;
            padding: 0.5rem;
            width: 100%;
            color: white;
        }
        
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        /* Botones */
        .btn-primary {
            background: var(--primary);
            transition: all 0.2s;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            border: none;
            cursor: pointer;
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
        }
        
        /* Toast */
        .toast {
            position: fixed;
            bottom: 1rem;
            right: 1rem;
            left: auto;
            background: var(--primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            z-index: 1000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
            max-width: calc(100% - 2rem);
        }
        
        .toast.show {
            transform: translateX(0);
        }
        
        /* Badges de estado */
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.65rem;
            font-weight: 500;
            display: inline-block;
        }
        
        .status-active {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }
        
        .status-inactive {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
        
        /* Grid de álbumes */
        .albumes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
        }
        
        .album-card {
            background: rgba(0, 0, 0, 0.7);
            border-radius: 0.75rem;
            overflow: hidden;
            border: 1px solid #2d2d2d;
            transition: all 0.3s ease;
        }
        
        .album-card:hover {
            transform: translateY(-4px);
            border-color: var(--primary);
        }
        
        /* Grid de banners */
        .banners-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
        }
        
        /* Badge de mantenimiento */
        .maintenance-badge {
            background: #dc2626;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.7rem;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* ==================== MEDIA QUERIES ==================== */
        @media (min-width: 768px) {
            .dashboard-container {
                flex-direction: row;
            }
            
            .sidebar {
                width: 16rem;
                max-width: 16rem;
                border-right: 1px solid #2d2d2d;
                border-bottom: none;
                position: sticky;
                top: 0;
                height: 100vh;
                overflow-y: auto;
            }
            
            .sidebar-header {
                flex-direction: column;
                text-align: center;
                padding: 1rem;
            }
            
            .user-info {
                flex-direction: column;
                text-align: center;
            }
            
            .user-avatar {
                width: 3.5rem;
                height: 3.5rem;
                margin: 0 auto;
            }
            
            .nav-menu {
                flex-direction: column;
                overflow-x: visible;
                padding: 0.75rem;
                gap: 0.25rem;
            }
            
            .nav-item {
                width: 100%;
                white-space: normal;
            }
            
            .main-content {
                padding: 1.5rem;
                overflow-y: auto;
                height: 100vh;
            }
            
            .stats-grid {
                grid-template-columns: repeat(5, 1fr);
                gap: 1rem;
            }
            
            .stat-value {
                font-size: 2rem;
            }
            
            .stat-label {
                font-size: 0.75rem;
            }
            
            th, td {
                padding: 0.75rem;
                font-size: 0.875rem;
            }
            
            .modal-content {
                max-width: 700px;
                padding: 1.5rem;
            }
            
            .menu-toggle {
                display: none;
            }
        }
        
        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .nav-item span:not(.count) {
                display: inline;
            }
            
            .nav-item .count {
                font-size: 0.7rem;
                background: rgba(225, 38, 29, 0.2);
                padding: 0.125rem 0.375rem;
                border-radius: 1rem;
            }
            
            button, .btn-primary {
                padding: 0.5rem 0.75rem;
                font-size: 0.75rem;
            }
            
            .share-btn i {
                font-size: 0.875rem;
            }
        }
        
        /* Ocultar menú en móvil inicialmente */
        @media (max-width: 767px) {
            .nav-menu {
                display: none;
            }
            .nav-menu.show {
                display: flex;
            }
        }
        
        /* Scrollbar personalizada */
        ::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }
        
        ::-webkit-scrollbar-track {
            background: #1f1f1f;
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 4px;
        }
        
        /* Utilidades */
        .hidden {
            display: none;
        }
        
        .cursor-pointer {
            cursor: pointer;
        }
        
        .w-full {
            width: 100%;
        }
        
        .text-left {
            text-align: left;
        }
        
        .text-primary {
            color: var(--primary);
        }
        
        .border-t {
            border-top: 1px solid #2d2d2d;
        }
        
        .mt-2 {
            margin-top: 0.5rem;
        }
        
        .mb-2 {
            margin-bottom: 0.5rem;
        }
        
        .pt-2 {
            padding-top: 0.5rem;
        }
    </style>
</head>
<body class="bg-neutral-950 text-white">
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="user-info">
                    <div class="user-avatar">
                        <?php if ($user_avatar): ?>
                        <img src="<?php echo htmlspecialchars($user_avatar); ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                        <i class="fas fa-user text-2xl text-neutral-500"></i>
                        <?php endif; ?>
                    </div>
                    <div class="user-details">
                        <p class="text-neutral-400 text-xs">Bienvenido,</p>
                        <p class="user-name"><?php echo htmlspecialchars($username); ?></p>
                        <span class="user-role <?php echo $user_role === 'superadmin' ? 'text-red-400' : 'text-blue-400'; ?>">
                            <?php echo $user_role === 'superadmin' ? 'Super Administrador' : 'Administrador'; ?>
                        </span>
                    </div>
                </div>
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                    <span>Menú</span>
                </button>
            </div>
            
            <div class="nav-menu" id="navMenu">
                <div class="nav-item active" data-section="dashboard"><i class="fas fa-chart-line"></i> Dashboard</div>
                <div class="nav-item" data-section="mixes"><i class="fas fa-music"></i> Mixes <span class="count">(<?php echo count($mixes); ?>)</span></div>
                <div class="nav-item" data-section="djs"><i class="fas fa-headphones"></i> DJs <span class="count">(<?php echo count($djs); ?>)</span></div>
                <div class="nav-item" data-section="videos"><i class="fas fa-video"></i> Videos <span class="count">(<?php echo count($videos); ?>)</span></div>
                <div class="nav-item" data-section="albumes"><i class="fas fa-compact-disc"></i> Álbumes <span class="count">(<?php echo $total_albumes; ?>)</span></div>
                <div class="nav-item" data-section="events"><i class="fas fa-calendar"></i> Eventos</div>
                <div class="nav-item" data-section="banners"><i class="fas fa-ad"></i> Publicidad <span class="count">(<?php echo $total_banners; ?>)</span></div>
                <div class="nav-item" data-section="stats"><i class="fas fa-chart-bar"></i> Estadísticas</div>
                
                <a href="admin/dj_pro.php" class="nav-item block"><i class="fas fa-crown"></i> DJ PRO</a>
                <a href="admin/reports/generate_partner_report.php" class="nav-item block"><i class="fas fa-file-pdf"></i> Reporte Socios</a>

                <?php if ($user_role === 'superadmin'): ?>
                <div class="border-t my-2 pt-2"></div>
                <div class="nav-item" data-section="users"><i class="fas fa-users"></i> Usuarios</div>
                <div class="nav-item" data-section="logs"><i class="fas fa-history"></i> Logs</div>
                <div class="nav-item" data-section="settings"><i class="fas fa-cog"></i> Configuración</div>
                <?php endif; ?>
                
                <div class="border-t my-2 pt-2"></div>
                <div class="nav-item" data-section="profile"><i class="fas fa-user"></i> Mi Perfil</div>
                <div class="nav-item" data-section="guia"><i class="fas fa-book-open"></i> Guía de Admin</div>
                <div onclick="cerrarSesionManual()" class="nav-item text-red-400 hover:bg-red-500/10 cursor-pointer">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </div>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <!-- Dashboard Section -->
            <div id="dashboard-section" class="section-content">
                <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
                    <h1 class="text-xl md:text-2xl font-bold">Dashboard</h1>
                    <?php if ($maintenance_mode == 1): ?>
                    <span class="maintenance-badge">
                        <i class="fas fa-tools"></i> MODO MANTENIMIENTO ACTIVO
                    </span>
                    <?php endif; ?>
                </div>
                <div class="stats-grid">
                    <div class="stat-card"><div><p class="stat-label">Total Usuarios</p><p class="stat-value"><?php echo $system_stats['total_users']; ?></p></div></div>
                    <div class="stat-card"><div><p class="stat-label">Mixes</p><p class="stat-value"><?php echo count($mixes); ?></p></div></div>
                    <div class="stat-card"><div><p class="stat-label">DJs Activos</p><p class="stat-value"><?php echo count($djs); ?></p></div></div>
                    <div class="stat-card"><div><p class="stat-label">Descargas</p><p class="stat-value"><?php echo number_format($system_stats['total_downloads']); ?></p></div></div>
                    <div class="stat-card"><div><p class="stat-label">Actividad Hoy</p><p class="stat-value"><?php echo $system_stats['activity_today']; ?></p></div></div>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
                    <div class="bg-neutral-900 rounded-xl p-4"><h3 class="text-lg font-semibold mb-3">🎵 Top 10 Mixes más Descargados</h3><div class="space-y-2"><?php foreach ($top_mixes as $mix): ?><div class="flex justify-between items-center p-2 hover:bg-neutral-800 rounded"><div class="flex-1"><p class="font-medium text-sm truncate"><?php echo htmlspecialchars($mix['title']); ?></p><p class="text-xs text-neutral-500"><?php echo htmlspecialchars($mix['dj']); ?></p></div><div class="text-right"><p class="text-primary font-bold"><?php echo number_format($mix['downloads']); ?></p></div></div><?php endforeach; ?></div></div>
                    <div class="bg-neutral-900 rounded-xl p-4"><h3 class="text-lg font-semibold mb-3">🏆 Top 5 DJs más Descargados</h3><div class="space-y-2"><?php foreach ($top_djs_stats as $dj): ?><div class="flex justify-between items-center p-2 hover:bg-neutral-800 rounded"><div><p class="font-medium text-sm"><?php echo htmlspecialchars($dj['dj']); ?></p><p class="text-xs text-neutral-500"><?php echo $dj['total_mixes']; ?> mixes</p></div><div class="text-right"><p class="text-primary font-bold"><?php echo number_format($dj['total_downloads']); ?></p></div></div><?php endforeach; ?></div></div>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="bg-neutral-900 rounded-xl p-4"><h3 class="text-lg font-semibold mb-3">Usuarios por Rol</h3><canvas id="rolesChart" height="200"></canvas></div>
                    <div class="bg-neutral-900 rounded-xl p-4"><h3 class="text-lg font-semibold mb-3">Actividad Reciente</h3><div class="space-y-2 max-h-64 overflow-y-auto"><?php foreach (array_slice($activity_logs, 0, 10) as $log): ?><div class="flex items-center gap-2 text-sm"><div class="w-6 h-6 rounded-full bg-neutral-800 flex items-center justify-center"><i class="fas fa-user text-neutral-500 text-xs"></i></div><div class="flex-1"><p class="text-neutral-300 text-xs"><?php echo htmlspecialchars($log['action']); ?></p><p class="text-xs text-neutral-500"><?php echo htmlspecialchars($log['username'] ?? 'Sistema'); ?> • <?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></p></div></div><?php endforeach; ?></div></div>
                </div>
            </div>
            
            <!-- Mixes Section -->
            <div id="mixes-section" class="section-content hidden">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
                    <h1 class="text-xl md:text-2xl font-bold">Gestión de Mixes</h1>
                    <button onclick="openMixModal()" class="btn-primary w-full sm:w-auto"><i class="fas fa-plus mr-2"></i>Agregar Mix</button>
                </div>
                <div class="bg-neutral-900 rounded-xl overflow-hidden">
                    <div class="table-container">
                        <table class="w-full">
                            <thead>
                                <tr><th>ID</th><th>Portada</th><th>Título</th><th>DJ</th><th>Plays</th><th>Downloads</th><th>Super Pack</th><th>Acciones</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($mixes as $mix): ?>
                                <tr>
                                    <td><?php echo $mix['id']; ?></td>
                                    <td><img src="<?php echo htmlspecialchars($mix['cover']); ?>" class="thumbnail" onerror="this.src='assets/img/default-cover.jpg'"></td>
                                    <td class="max-w-[120px] truncate"><?php echo htmlspecialchars($mix['title']); ?></td>
                                    <td><?php echo htmlspecialchars($mix['dj']); ?></td>
                                    <td class="text-primary"><?php echo number_format($mix['plays']); ?></td>
                                    <td class="text-primary"><?php echo number_format($mix['downloads']); ?></td>
                                    <td><button onclick="toggleMixSuperpack(<?php echo $mix['id']; ?>, <?php echo $mix['is_superpack'] ? 1 : 0; ?>)" class="px-2 py-1 rounded text-xs <?php echo $mix['is_superpack'] ? 'bg-green-600' : 'bg-neutral-700'; ?>"><?php echo $mix['is_superpack'] ? '🔥' : 'Activar'; ?></button></td>
                                    <td>
                                        <button onclick="editMix(<?php echo $mix['id']; ?>)" class="text-blue-400 mr-1"><i class="fas fa-edit"></i></button>
                                        <button onclick="deleteMix(<?php echo $mix['id']; ?>)" class="text-red-400 mr-1"><i class="fas fa-trash"></i></button>
                                        <button onclick="shareMix(<?php echo $mix['id']; ?>, '<?php echo addslashes($mix['title']); ?>', '<?php echo addslashes($mix['dj']); ?>')" class="text-green-400"><i class="fab fa-whatsapp"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- DJs Section con ícono de audífonos -->
            <div id="djs-section" class="section-content hidden">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
                    <h1 class="text-xl md:text-2xl font-bold">Gestión de DJs</h1>
                    <button onclick="openDJModal()" class="btn-primary w-full sm:w-auto"><i class="fas fa-headphones mr-2"></i>Agregar DJ</button>
                </div>
                <div class="bg-neutral-900 rounded-xl overflow-hidden">
                    <div class="table-container">
                        <table class="w-full">
                            <thead>
                                <tr><th>ID</th><th>Avatar</th><th><i class="fas fa-headphones text-primary mr-1"></i> Nombre</th><th>Género</th><th>Ciudad</th><th>Mixes</th><th>Super Pack</th><th>Acciones</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($djs as $dj): $mixCount = $db->query("SELECT COUNT(*) as c FROM mixes WHERE dj = '{$dj['name']}' AND active = 1")->fetch()['c']; ?>
                                <tr>
                                    <td><?php echo $dj['id']; ?></td>
                                    <td><img src="<?php echo htmlspecialchars($dj['avatar']); ?>" class="thumbnail rounded-full" onerror="this.src='assets/img/default-avatar.jpg'"></td>
                                    <td><i class="fas fa-headphones text-primary text-xs mr-1"></i> <?php echo htmlspecialchars($dj['name']); ?></td>
                                    <td><?php echo htmlspecialchars($dj['genre']); ?></td>
                                    <td><?php echo htmlspecialchars($dj['city']); ?></td>
                                    <td class="text-primary"><?php echo $mixCount; ?></td>
                                    <td><button onclick="toggleDJSuperpack('<?php echo addslashes($dj['name']); ?>')" class="px-2 py-1 rounded text-xs <?php echo $mixCount >= 4 ? 'bg-green-600' : 'bg-neutral-700'; ?>"><?php echo $mixCount >= 4 ? '🔥' : 'Activar'; ?></button></td>
                                    <td>
                                        <button onclick="editDJ(<?php echo $dj['id']; ?>)" class="text-blue-400 mr-1"><i class="fas fa-edit"></i></button>
                                        <button onclick="deleteDJ(<?php echo $dj['id']; ?>)" class="text-red-400 mr-1"><i class="fas fa-trash"></i></button>
                                        <button onclick="shareDJ(<?php echo $dj['id']; ?>, '<?php echo addslashes($dj['name']); ?>')" class="text-green-400"><i class="fab fa-whatsapp"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Videos Section -->
            <div id="videos-section" class="section-content hidden">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
                    <h1 class="text-xl md:text-2xl font-bold">Gestión de Videos</h1>
                    <button onclick="openVideoModal()" class="btn-primary w-full sm:w-auto"><i class="fas fa-plus mr-2"></i>Agregar Video</button>
                </div>
                <div class="bg-neutral-900 rounded-xl overflow-hidden">
                    <div class="table-container">
                        <table class="w-full">
                            <thead>
                                <tr><th>ID</th><th>Miniatura</th><th>Título</th><th>DJ</th><th>Tipo</th><th>Reproducciones</th><th>Estado</th><th>Acciones</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($videos as $video): ?>
                                <tr>
                                    <td><?php echo $video['id']; ?></td>
                                    <td><img src="<?php echo htmlspecialchars($video['cover']); ?>" class="thumbnail" onerror="this.src='assets/img/default-video.jpg'"></td>
                                    <td class="max-w-[120px] truncate"><?php echo htmlspecialchars($video['title']); ?></td>
                                    <td><?php echo htmlspecialchars($video['dj']); ?></td>
                                    <td><?php echo $video['type']; ?></td>
                                    <td class="text-primary"><?php echo number_format($video['plays']); ?></td>
                                    <td><span class="status-badge <?php echo $video['active'] ? 'status-active' : 'status-inactive'; ?>"><?php echo $video['active'] ? 'Activo' : 'Inactivo'; ?></span></td>
                                    <td>
                                        <button onclick="editVideo(<?php echo $video['id']; ?>)" class="text-blue-400 mr-1"><i class="fas fa-edit"></i></button>
                                        <button onclick="deleteVideo(<?php echo $video['id']; ?>)" class="text-red-400 mr-1"><i class="fas fa-trash"></i></button>
                                        <button onclick="shareVideo(<?php echo $video['id']; ?>, '<?php echo addslashes($video['title']); ?>', '<?php echo addslashes($video['dj']); ?>')" class="text-green-400"><i class="fab fa-whatsapp"></i></button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- ÁLBUMES Section -->
            <div id="albumes-section" class="section-content hidden">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
                    <h1 class="text-xl md:text-2xl font-bold">Gestión de Álbumes</h1>
                    <button onclick="openAlbumModal()" class="btn-primary w-full sm:w-auto"><i class="fas fa-plus mr-2"></i>Nuevo Álbum</button>
                </div>
                <div id="albumes-list" class="albumes-grid">
                    <div class="col-span-full text-center py-8 text-neutral-500"><i class="fas fa-spinner fa-spin text-2xl mb-2"></i><p>Cargando álbumes...</p></div>
                </div>
            </div>
            
            <!-- Events Section -->
            <div id="events-section" class="section-content hidden">
                <h1 class="text-xl md:text-2xl font-bold mb-4">Gestión de Eventos</h1>
                <div class="bg-neutral-900 rounded-xl p-6 text-center"><p class="text-neutral-400">Módulo de eventos en desarrollo</p></div>
            </div>
            
            <!-- Banners Section (Publicidad) -->
            <div id="banners-section" class="section-content hidden">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
                    <h1 class="text-xl md:text-2xl font-bold">Gestión de Publicidad</h1>
                    <button onclick="openBannerModal()" class="btn-primary w-full sm:w-auto"><i class="fas fa-plus mr-2"></i>Agregar Banner</button>
                </div>
                
                <!-- Banners Sidebar -->
                <div class="bg-neutral-900 rounded-xl p-4 mb-6">
                    <h2 class="text-lg md:text-xl font-bold mb-4 flex items-center gap-2">
                        <i class="fas fa-columns text-primary"></i> Banners Sidebar (300x250 px)
                    </h2>
                    <div id="banners-sidebar-list" class="banners-grid">
                        <div class="col-span-full text-center py-8 text-neutral-500">
                            <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                            <p>Cargando banners...</p>
                        </div>
                    </div>
                </div>
                
                <!-- Banners Horizontal -->
                <div class="bg-neutral-900 rounded-xl p-4">
                    <h2 class="text-lg md:text-xl font-bold mb-4 flex items-center gap-2">
                        <i class="fas fa-arrows-alt-h text-primary"></i> Banners Horizontal (728x90 px)
                    </h2>
                    <div id="banners-horizontal-list" class="grid grid-cols-1 gap-4">
                        <div class="col-span-full text-center py-8 text-neutral-500">
                            <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                            <p>Cargando banners horizontales...</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Estadísticas Section -->
            <div id="stats-section" class="section-content hidden">
                <h1 class="text-xl md:text-2xl font-bold mb-4">Estadísticas Avanzadas</h1>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-neutral-900 rounded-xl p-4"><h3 class="text-lg font-semibold mb-3">Estadísticas por Tipo</h3><?php foreach ($stats_data as $stat): ?><div class="flex justify-between p-2"><span class="capitalize"><?php echo $stat['item_type']; ?>s</span><span class="text-primary"><?php echo number_format($stat['total_plays']); ?> plays • <?php echo number_format($stat['total_downloads']); ?> descargas</span></div><?php endforeach; ?></div>
                    <div class="bg-neutral-900 rounded-xl p-4"><h3 class="text-lg font-semibold mb-3">Resumen General</h3><div class="space-y-2"><div class="flex justify-between p-2 border-b"><span>Total de Mixes:</span><span class="text-primary"><?php echo count($mixes); ?></span></div><div class="flex justify-between p-2 border-b"><span>Total de DJs:</span><span class="text-primary"><?php echo count($djs); ?></span></div><div class="flex justify-between p-2"><span>Descargas Totales:</span><span class="text-primary"><?php echo number_format($system_stats['total_downloads']); ?></span></div></div></div>
                </div>
            </div>
            
            <!-- Users Section -->
            <div id="users-section" class="section-content hidden">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
                    <h1 class="text-xl md:text-2xl font-bold">Gestión de Usuarios</h1>
                    <button onclick="openUserModal()" class="btn-primary w-full sm:w-auto"><i class="fas fa-plus mr-2"></i>Nuevo Usuario</button>
                </div>
                <div class="bg-neutral-900 rounded-xl overflow-hidden">
                    <div class="table-container">
                        <table class="w-full">
                            <thead><tr><th>ID</th><th>Usuario</th><th>Email</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr></thead>
                            <tbody id="users-table">
                                <?php foreach ($users as $user): ?>
                                <tr><td><?php echo $user['id']; ?></td><td><?php echo htmlspecialchars($user['username']); ?></td><td><?php echo htmlspecialchars($user['email']); ?></td><td><?php echo ucfirst($user['role']); ?></td><td><span class="status-badge <?php echo $user['active'] ? 'status-active' : 'status-inactive'; ?>"><?php echo $user['active'] ? 'Activo' : 'Inactivo'; ?></span></td>
                                <td><button onclick="showChangePasswordModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" class="text-yellow-400 mr-1"><i class="fas fa-key"></i></button><button onclick="deleteUser(<?php echo $user['id']; ?>)" class="text-red-400"><i class="fas fa-trash"></i></button></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Logs Section -->
            <div id="logs-section" class="section-content hidden">
                <h1 class="text-xl md:text-2xl font-bold mb-4">Registro de Actividad</h1>
                <div class="bg-neutral-900 rounded-xl overflow-hidden"><div class="table-container"><table class="w-full"><thead><tr><th>Fecha</th><th>Usuario</th><th>Acción</th><th>Detalles</th><th>IP</th></tr></thead><tbody><?php foreach ($activity_logs as $log): ?><tr><td class="text-xs"><?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?></td><td class="text-xs"><?php echo htmlspecialchars($log['username'] ?? 'Sistema'); ?></td><td class="text-xs"><?php echo htmlspecialchars($log['action']); ?></td><td class="text-xs"><?php echo htmlspecialchars($log['details'] ?? '-'); ?></td><td class="text-xs"><?php echo htmlspecialchars($log['ip_address'] ?? '-'); ?></td></tr><?php endforeach; ?></tbody></table></div></div>
            </div>
            
            <!-- Settings Section -->
            <div id="settings-section" class="section-content hidden">
                <h1 class="text-xl md:text-2xl font-bold mb-4">Configuración del Sistema</h1>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="bg-neutral-900 rounded-xl p-4"><h3 class="text-lg font-semibold mb-3"><i class="fas fa-globe text-primary mr-2"></i>Configuración General</h3>
                        <form id="config-general-form" class="space-y-3"><div><label class="block text-sm mb-1">Título del Sitio</label><input type="text" id="site_title" class="w-full p-2 bg-neutral-800 rounded" value="<?php echo htmlspecialchars($settings['site_title'] ?? 'Panda Truck Reloaded'); ?>"></div>
                        <div><label class="block text-sm mb-1">Descripción</label><textarea id="site_description" rows="2" class="w-full p-2 bg-neutral-800 rounded"><?php echo htmlspecialchars($settings['site_description'] ?? ''); ?></textarea></div>
                        <div><label class="block text-sm mb-1">Texto Footer</label><input type="text" id="footer_text" class="w-full p-2 bg-neutral-800 rounded" value="<?php echo htmlspecialchars($settings['footer_text'] ?? ''); ?>"></div>
                        <button type="submit" class="btn-primary w-full">Guardar</button></form>
                    </div>
                    
                    <div class="bg-neutral-900 rounded-xl p-4"><h3 class="text-lg font-semibold mb-3"><i class="fas fa-radio text-primary mr-2"></i>Configuración de Radio</h3>
                        <form id="config-radio-form" class="space-y-3"><div><label class="block text-sm mb-1">URL del Stream</label><input type="url" id="radio_url" class="w-full p-2 bg-neutral-800 rounded" value="<?php echo htmlspecialchars($settings['radio_url'] ?? ''); ?>"></div>
                        <div><label class="block text-sm mb-1">Nombre de la Radio</label><input type="text" id="radio_name" class="w-full p-2 bg-neutral-800 rounded" value="<?php echo htmlspecialchars($settings['radio_name'] ?? ''); ?>"></div>
                        <button type="submit" class="btn-primary w-full">Guardar</button></form>
                    </div>
                    
                    <div class="bg-neutral-900 rounded-xl p-4"><h3 class="text-lg font-semibold mb-3"><i class="fas fa-eye text-primary mr-2"></i>Visualización</h3>
                        <form id="config-display-form" class="space-y-3">
                            <div><label class="block text-sm mb-1">Mínimo para Super Pack</label><input type="number" id="superpack_threshold" class="w-full p-2 bg-neutral-800 rounded" value="<?php echo $settings['superpack_threshold'] ?? 4; ?>"></div>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" id="maintenance_mode" <?php echo ($settings['maintenance_mode'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="text-sm">Modo Mantenimiento</label>
                                </div>
                                <span id="maintenance-status" class="text-xs px-2 py-1 rounded-full <?php echo ($settings['maintenance_mode'] ?? 0) ? 'bg-red-500/20 text-red-400' : 'bg-green-500/20 text-green-400'; ?>">
                                    <?php echo ($settings['maintenance_mode'] ?? 0) ? '🔴 ACTIVADO' : '🟢 DESACTIVADO'; ?>
                                </span>
                            </div>
                            <button type="submit" class="btn-primary w-full">Guardar Configuración</button>
                        </form>
                    </div>
                    
                    <div class="bg-neutral-900 rounded-xl p-4"><h3 class="text-lg font-semibold mb-3"><i class="fas fa-image text-primary mr-2"></i>Logo</h3>
                        <div class="text-center"><img id="logo-preview" src="<?php echo $settings['site_logo'] ?? 'assets/img/logo.png'; ?>" class="w-24 h-24 object-contain mx-auto mb-3"><button onclick="uploadLogo()" class="btn-primary">Subir Logo</button></div>
                    </div>
                    
                    <div class="bg-neutral-900 rounded-xl p-4"><h3 class="text-lg font-semibold mb-3"><i class="fas fa-book-open text-primary mr-2"></i>Guía para DJs</h3>
                        <form id="config-guia-form" class="space-y-3"><div><label class="block text-sm mb-1">Título</label><input type="text" id="guia_title" class="w-full p-2 bg-neutral-800 rounded" value="<?php echo htmlspecialchars($settings['guia_title'] ?? 'Guía para DJs'); ?>"></div>
                        <div><label class="block text-sm mb-1">WhatsApp</label><input type="text" id="guia_whatsapp" class="w-full p-2 bg-neutral-800 rounded" value="<?php echo htmlspecialchars($settings['guia_whatsapp'] ?? '50762115209'); ?>"></div>
                        <button type="submit" class="btn-primary w-full">Guardar</button></form>
                    </div>
                    
                    <div class="bg-neutral-900 rounded-xl p-4 border border-primary/30"><h3 class="text-lg font-semibold mb-3"><i class="fas fa-play-circle text-primary mr-2"></i>🎬 Video Hero</h3>
                        <form id="config-hero-form" class="space-y-3" enctype="multipart/form-data"><div><label class="block text-sm mb-1">Tipo de Video</label><select id="hero_type" class="w-full p-2 bg-neutral-800 rounded" onchange="updateHeroVideoFields()"><option value="mp4" <?php echo ($hero['hero_type'] ?? 'mp4') === 'mp4' ? 'selected' : ''; ?>>MP4</option><option value="youtube" <?php echo ($hero['hero_type'] ?? '') === 'youtube' ? 'selected' : ''; ?>>YouTube</option><option value="twitch" <?php echo ($hero['hero_type'] ?? '') === 'twitch' ? 'selected' : ''; ?>>Twitch</option></select></div>
                        <div id="hero_mp4_fields" class="<?php echo ($hero['hero_type'] ?? 'mp4') !== 'mp4' ? 'hidden' : ''; ?>"><div><label class="block text-sm mb-1">URL del Video MP4</label><input type="url" id="hero_video_url" class="w-full p-2 bg-neutral-800 rounded" value="<?php echo htmlspecialchars($hero['hero_video_url'] ?? ''); ?>"></div><div><label class="block text-sm mb-1">Imagen de Portada</label><input type="url" id="hero_video_poster" class="w-full p-2 bg-neutral-800 rounded" value="<?php echo htmlspecialchars($hero['hero_video_poster'] ?? ''); ?>"></div></div>
                        <div id="hero_youtube_fields" class="<?php echo ($hero['hero_type'] ?? '') !== 'youtube' ? 'hidden' : ''; ?>"><div><label class="block text-sm mb-1">ID de YouTube</label><input type="text" id="hero_youtube_input" class="w-full p-2 bg-neutral-800 rounded" value="<?php echo htmlspecialchars($hero['youtube_id'] ?? ''); ?>"></div></div>
                        <div id="hero_twitch_fields" class="<?php echo ($hero['hero_type'] ?? '') !== 'twitch' ? 'hidden' : ''; ?>"><div><label class="block text-sm mb-1">Canal de Twitch</label><input type="text" id="hero_twitch_channel" class="w-full p-2 bg-neutral-800 rounded" value="<?php echo htmlspecialchars($hero['twitch_channel'] ?? ''); ?>"></div></div>
                        <div><label class="block text-sm mb-1">Título del Video</label><input type="text" id="hero_video_title" class="w-full p-2 bg-neutral-800 rounded" value="<?php echo htmlspecialchars($hero['hero_video_title'] ?? 'Video Destacado'); ?>"></div>
                        <button type="submit" class="btn-primary w-full">Guardar Video Hero</button></form>
                    </div>
                </div>
                
                <div class="mt-4 bg-neutral-900 rounded-xl p-4">
                    <h3 class="text-lg font-semibold mb-3"><i class="fas fa-tools text-primary mr-2"></i>Modo Mantenimiento</h3>
                    <div class="flex flex-wrap gap-3 mb-4">
                        <button onclick="activarMantenimiento()" class="btn-primary bg-red-600 hover:bg-red-700">
                            <i class="fas fa-lock mr-2"></i> 🔴 Activar Mantenimiento
                        </button>
                        <button onclick="desactivarMantenimiento()" class="btn-primary bg-green-600 hover:bg-green-700">
                            <i class="fas fa-unlock mr-2"></i> 🟢 Desactivar Mantenimiento
                        </button>
                    </div>
                    <p class="text-xs text-neutral-500">
                        <i class="fas fa-info-circle"></i> Al activar, los usuarios normales verán la página de mantenimiento. Solo los administradores podrán acceder al sitio.
                    </p>
                </div>

                <div class="mt-4 bg-neutral-900 rounded-xl p-4"><h3 class="text-lg font-semibold mb-3"><i class="fas fa-trash-alt text-primary mr-2"></i>Otras Herramientas</h3><div class="flex flex-wrap gap-2"><button onclick="clearCache()" class="btn-primary bg-yellow-600 hover:bg-yellow-700"><i class="fas fa-broom mr-2"></i>Limpiar Caché</button><button onclick="backupDatabase()" class="btn-primary bg-green-600 hover:bg-green-700"><i class="fas fa-database mr-2"></i>Backup BD</button><button onclick="optimizeDatabase()" class="btn-primary bg-blue-600 hover:bg-blue-700"><i class="fas fa-chart-line mr-2"></i>Optimizar BD</button></div></div>
            </div>
            
            <!-- Profile Section -->
            <div id="profile-section" class="section-content hidden">
                <h1 class="text-xl md:text-2xl font-bold mb-4">Mi Perfil</h1>
                <div class="bg-neutral-900 rounded-xl p-4 max-w-md"><div class="text-center mb-4"><div class="w-20 h-20 rounded-full bg-neutral-800 mx-auto mb-2 overflow-hidden"><img src="<?php echo $user_avatar ?: 'assets/img/default-avatar.jpg'; ?>" class="w-full h-full object-cover"></div><h2 class="text-lg font-bold"><?php echo htmlspecialchars($username); ?></h2><p class="text-neutral-400 text-sm"><?php echo htmlspecialchars($user_email); ?></p></div>
                <form id="change-password-form" class="space-y-3"><div><label class="text-sm">Contraseña Actual</label><input type="password" id="current-password" class="w-full p-2 bg-neutral-800 rounded"></div><div><label class="text-sm">Nueva Contraseña</label><input type="password" id="new-password" class="w-full p-2 bg-neutral-800 rounded"></div><div><label class="text-sm">Confirmar</label><input type="password" id="confirm-password" class="w-full p-2 bg-neutral-800 rounded"></div><button type="submit" class="btn-primary w-full">Cambiar Contraseña</button></form></div>
            </div>
            
            <!-- Guía de Administración Section -->
            <div id="guia-section" class="section-content hidden">
                <div class="bg-neutral-900 rounded-xl p-4">
                    <h1 class="text-xl md:text-2xl font-bold mb-4 flex items-center gap-2">
                        <i class="fas fa-book-open text-primary"></i> Guía de Administración
                    </h1>
                    <p class="text-neutral-400 mb-4 text-sm">
                        Guía completa para administradores: cómo subir archivos a Backblaze y gestionar contenido en Panda Truck.
                    </p>
                    <iframe src="guia_admin.php" class="w-full min-h-[500px] md:min-h-[600px] rounded-lg border border-neutral-700"></iframe>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Modales -->
    <div id="itemModal" class="modal"><div class="modal-content"><div id="modalContent"></div></div></div>
    <div id="passwordModal" class="modal"><div class="modal-content p-4"><div class="flex justify-between items-center mb-3"><h3 class="text-lg font-bold">Cambiar Contraseña</h3><button onclick="closePasswordModal()" class="text-2xl hover:text-primary">&times;</button></div><form id="change-password-admin-form" class="space-y-3"><input type="hidden" id="change-user-id"><div><label>Usuario</label><input type="text" id="change-username" readonly class="w-full p-2 bg-neutral-800 rounded opacity-70"></div><div><label>Nueva Contraseña</label><input type="password" id="new-password-admin" required class="w-full p-2 bg-neutral-800 rounded"></div><div><label>Confirmar</label><input type="password" id="confirm-password-admin" required class="w-full p-2 bg-neutral-800 rounded"></div><div class="flex gap-2 pt-2"><button type="button" onclick="closePasswordModal()" class="flex-1 p-2 bg-neutral-700 rounded">Cancelar</button><button type="submit" class="flex-1 p-2 bg-primary rounded">Cambiar</button></div></form></div></div>
    <div id="toast" class="toast"></div>
    
    <script>
        // ==================== CERRAR SESIÓN ====================
        function cerrarSesionManual() {
            if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
                window.location.href = 'logout.php';
            }
        }
        
        // ==================== MENÚ MÓVIL ====================
        const menuToggle = document.getElementById('menuToggle');
        const navMenu = document.getElementById('navMenu');
        
        if (menuToggle && navMenu) {
            menuToggle.addEventListener('click', function() {
                navMenu.classList.toggle('show');
            });
        }
        
        // ==================== NAVEGACIÓN ====================
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if (this.getAttribute('onclick')) return;
                const section = this.getAttribute('data-section');
                if (!section) return;
                document.querySelectorAll('.nav-item').forEach(nav => nav.classList.remove('active'));
                this.classList.add('active');
                document.querySelectorAll('.section-content').forEach(content => content.classList.add('hidden'));
                const targetSection = document.getElementById(`${section}-section`);
                if (targetSection) targetSection.classList.remove('hidden');
                if (section === 'albumes') loadAlbumes();
                if (section === 'users') loadUsers();
                if (section === 'settings') loadSettings();
                if (section === 'banners') loadBanners();
                if (window.innerWidth < 768 && navMenu) navMenu.classList.remove('show');
            });
        });
        
        // ==================== FUNCIONES AUXILIARES ====================
        function showToast(msg, isError=false){const t=document.getElementById('toast');t.textContent=msg;t.style.background=isError?'#dc2626':'#e1261d';t.classList.add('show');setTimeout(()=>t.classList.remove('show'),3000);}
        function escapeHtml(t){if(!t)return'';const d=document.createElement('div');d.textContent=t;return d.innerHTML;}
        function closeModal(){document.getElementById('itemModal').classList.remove('show');}
        function closePasswordModal(){document.getElementById('passwordModal').classList.remove('show');}
        
        // ==================== GRÁFICO ====================
        const usersByRole = <?php echo json_encode($system_stats['users_by_role']); ?>;
        const ctx = document.getElementById('rolesChart')?.getContext('2d');
        if(ctx){ new Chart(ctx,{ type:'doughnut', data:{ labels:usersByRole.map(r=>r.role==='superadmin'?'Super Admin':r.role==='admin'?'Admin':'DJ'), datasets:[{ data:usersByRole.map(r=>r.count), backgroundColor:['#e1261d','#3b82f6','#10b981'], borderWidth:0 }] }, options:{ responsive:true, plugins:{ legend:{ position:'bottom', labels:{ color:'#fff' } } } } }); }
        
        // ==================== VIDEO HERO ====================
        function extractYouTubeId(urlOrId) { if (!urlOrId) return null; if (urlOrId.match(/^[A-Za-z0-9_-]{11}$/)) return urlOrId; const patterns = [ /(?:youtube\.com\/watch\?v=)([^&]+)/i, /(?:youtu\.be\/)([^?]+)/i, /(?:youtube\.com\/embed\/)([^?]+)/i ]; for (let pattern of patterns) { const match = urlOrId.match(pattern); if (match && match[1]) return match[1]; } return null; }
        function updateHeroVideoFields() { const type = document.getElementById('hero_type').value; const mp4Fields = document.getElementById('hero_mp4_fields'); const youtubeFields = document.getElementById('hero_youtube_fields'); const twitchFields = document.getElementById('hero_twitch_fields'); if(mp4Fields) mp4Fields.classList.add('hidden'); if(youtubeFields) youtubeFields.classList.add('hidden'); if(twitchFields) twitchFields.classList.add('hidden'); if(type === 'mp4' && mp4Fields) mp4Fields.classList.remove('hidden'); else if(type === 'youtube' && youtubeFields) youtubeFields.classList.remove('hidden'); else if(type === 'twitch' && twitchFields) twitchFields.classList.remove('hidden'); }
        document.getElementById('config-hero-form')?.addEventListener('submit', async (e) => { e.preventDefault(); const type = document.getElementById('hero_type').value; const title = document.getElementById('hero_video_title').value; const formData = new FormData(); formData.append('type', type); formData.append('hero_video_title', title); if (type === 'mp4') { formData.append('hero_video_url', document.getElementById('hero_video_url').value); formData.append('hero_video_poster', document.getElementById('hero_video_poster').value); } else if (type === 'youtube') { const youtubeInput = document.getElementById('hero_youtube_input').value; if (!youtubeInput) { showToast('ID de YouTube requerido', true); return; } const videoId = extractYouTubeId(youtubeInput); if (!videoId) { showToast('ID inválido', true); return; } formData.append('youtube_id', videoId); formData.append('hero_video_url', `https://www.youtube.com/watch?v=${videoId}`); } else if (type === 'twitch') { const twitchChannel = document.getElementById('hero_twitch_channel').value; if (!twitchChannel) { showToast('Canal de Twitch requerido', true); return; } formData.append('twitch_channel', twitchChannel); formData.append('hero_video_url', `https://twitch.tv/${twitchChannel}`); } try { const res = await fetch('api/save_hero_video.php', { method: 'POST', body: formData }); const result = await res.json(); if (result.success) { showToast('Video Hero actualizado'); setTimeout(() => location.reload(), 1000); } else { showToast(result.error || 'Error', true); } } catch(e) { showToast('Error de conexión', true); } });
        
        // ==================== CRUD MIXES ====================
        async function deleteMix(id){ if(confirm('¿Eliminar este mix?')){ try{ const res=await fetch('api/delete_mix.php',{ method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id}) }); const data=await res.json(); if(data.success){ showToast('Mix eliminado'); location.reload(); }else showToast(data.error,true); }catch(e){showToast('Error',true);} } }
        function openMixModal(data=null){ const isEdit=data?.id; const formHtml=`<div class="flex justify-between items-center mb-3"><h3 class="text-lg font-bold">${isEdit?'Editar Mix':'Agregar Mix'}</h3><button onclick="closeModal()" class="text-2xl hover:text-primary">&times;</button></div><form id="itemForm" class="space-y-3"><input type="hidden" name="id" value="${data?.id||''}"><div><label>Título *</label><input type="text" name="title" required value="${escapeHtml(data?.title||'')}" class="w-full p-2 bg-neutral-800 rounded"></div><div><label>DJ *</label><input type="text" name="dj" required value="${escapeHtml(data?.dj||'')}" class="w-full p-2 bg-neutral-800 rounded"></div><div><label>Género *</label><input type="text" name="genre" required value="${escapeHtml(data?.genre||'')}" class="w-full p-2 bg-neutral-800 rounded"></div><div><label>URL del Audio *</label><input type="url" name="url" required value="${escapeHtml(data?.url||'')}" class="w-full p-2 bg-neutral-800 rounded"></div><div><label>URL de Portada</label><input type="url" name="cover" value="${escapeHtml(data?.cover||'')}" class="w-full p-2 bg-neutral-800 rounded"></div><div><label>Duración</label><input type="text" name="duration" value="${escapeHtml(data?.duration||'')}" class="w-full p-2 bg-neutral-800 rounded"></div><div><label>Tamaño MB</label><input type="number" name="sizeMB" value="${data?.sizeMB||0}" class="w-full p-2 bg-neutral-800 rounded"></div><div><label>Fecha</label><input type="date" name="date" value="${data?.date||''}" class="w-full p-2 bg-neutral-800 rounded"></div><div><label><input type="checkbox" name="active" value="1" ${data?.active!=0?'checked':''}> Activo</label></div><div class="flex gap-2 pt-2"><button type="button" onclick="closeModal()" class="flex-1 p-2 bg-neutral-700 rounded">Cancelar</button><button type="submit" class="flex-1 p-2 bg-primary rounded">Guardar</button></div></form>`; document.getElementById('modalContent').innerHTML=formHtml; document.getElementById('itemModal').classList.add('show'); document.getElementById('itemForm').addEventListener('submit',async(e)=>{ e.preventDefault(); const form=new FormData(e.target); const formData=Object.fromEntries(form); formData.active=form.has('active')?1:0; try{ const res=await fetch('api/save_mix.php',{ method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(formData) }); const result=await res.json(); if(result.success){ showToast('Mix guardado'); closeModal(); location.reload(); }else showToast(result.error,true); }catch(e){showToast('Error',true);} }); }
        function editMix(id){ fetch('api/get_mix.php?id='+id).then(r=>r.json()).then(data=>openMixModal(data)).catch(e=>showToast('Error',true)); }
        async function toggleMixSuperpack(mixId, currentStatus){ const newStatus=currentStatus?0:1; if(confirm(`¿${newStatus?'Activar':'Desactivar'} Super Pack?`)){ try{ const res=await fetch('api/update_superpack.php',{ method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id:mixId,is_superpack:newStatus}) }); const data=await res.json(); if(data.success){ showToast('Super Pack actualizado'); location.reload(); }else showToast(data.error,true); }catch(e){showToast('Error',true);} } }
        
        // ==================== CRUD DJS ====================
        async function deleteDJ(id){ if(confirm('¿Eliminar este DJ?')){ try{ const res=await fetch('api/delete_dj.php',{ method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id}) }); const data=await res.json(); if(data.success){ showToast('DJ eliminado'); location.reload(); }else showToast(data.error,true); }catch(e){showToast('Error',true);} } }
        function openDJModal(data=null){ const isEdit=data?.id; const formHtml=`<div class="flex justify-between items-center mb-3"><h3 class="text-lg font-bold">${isEdit?'Editar DJ':'Agregar DJ'}</h3><button onclick="closeModal()" class="text-2xl hover:text-primary">&times;</button></div><form id="itemForm" class="space-y-3"><input type="hidden" name="id" value="${data?.id||''}"><div><label>Nombre *</label><input type="text" name="name" required value="${escapeHtml(data?.name||'')}" class="w-full p-2 bg-neutral-800 rounded"></div><div><label>Género</label><input type="text" name="genre" value="${escapeHtml(data?.genre||'')}" class="w-full p-2 bg-neutral-800 rounded"></div><div><label>Ciudad</label><input type="text" name="city" value="${escapeHtml(data?.city||'')}" class="w-full p-2 bg-neutral-800 rounded"></div><div><label>Biografía</label><textarea name="bio" rows="3" class="w-full p-2 bg-neutral-800 rounded">${escapeHtml(data?.bio||'')}</textarea></div><div><label>URL del Avatar</label><input type="url" name="avatar" value="${escapeHtml(data?.avatar||'')}" class="w-full p-2 bg-neutral-800 rounded"></div><div><label><input type="checkbox" name="active" value="1" ${data?.active!=0?'checked':''}> Activo</label></div><div class="flex gap-2 pt-2"><button type="button" onclick="closeModal()" class="flex-1 p-2 bg-neutral-700 rounded">Cancelar</button><button type="submit" class="flex-1 p-2 bg-primary rounded">Guardar</button></div></form>`; document.getElementById('modalContent').innerHTML=formHtml; document.getElementById('itemModal').classList.add('show'); document.getElementById('itemForm').addEventListener('submit',async(e)=>{ e.preventDefault(); const form=new FormData(e.target); const formData=Object.fromEntries(form); formData.active=form.has('active')?1:0; try{ const res=await fetch('api/save_dj.php',{ method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(formData) }); const result=await res.json(); if(result.success){ showToast('DJ guardado'); closeModal(); location.reload(); }else showToast(result.error,true); }catch(e){showToast('Error',true);} }); }
        function editDJ(id){ fetch('api/get_dj.php?id='+id).then(r=>r.json()).then(data=>openDJModal(data)).catch(e=>showToast('Error',true)); }
        async function toggleDJSuperpack(djName){ if(confirm(`¿Activar/Desactivar Super Pack para ${djName}?`)){ try{ const res=await fetch('api/toggle_superpack.php',{ method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({dj:djName}) }); const data=await res.json(); if(data.success){ showToast(data.message); location.reload(); }else showToast(data.error,true); }catch(e){showToast('Error',true);} } }
        
        // ==================== CRUD VIDEOS ====================
        async function deleteVideo(id){ if(confirm('¿Eliminar este video?')){ try{ const res=await fetch('api/delete_video.php',{ method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id}) }); const data=await res.json(); if(data.success){ showToast('Video eliminado'); location.reload(); }else showToast(data.error,true); }catch(e){showToast('Error',true);} } }
        function openVideoModal(data=null){ const isEdit=data&&data.id; const type=data?.type||'mp4'; const formHtml=`<div class="flex justify-between items-center mb-3"><h3 class="text-lg font-bold">${isEdit?'Editar Video':'Agregar Video'}</h3><button onclick="closeModal()" class="text-2xl hover:text-primary">&times;</button></div><form id="itemForm" class="space-y-3"><input type="hidden" name="id" value="${data?.id||''}"><div><label>Título *</label><input type="text" name="title" required value="${escapeHtml(data?.title||'')}" class="w-full p-2 bg-neutral-800 rounded"></div><div><label>DJ *</label><input type="text" name="dj" required value="${escapeHtml(data?.dj||'')}" class="w-full p-2 bg-neutral-800 rounded"></div><div><label>Tipo de Video</label><select name="type" class="w-full p-2 bg-neutral-800 rounded"><option value="mp4" ${type==='mp4'?'selected':''}>MP4</option><option value="youtube" ${type==='youtube'?'selected':''}>YouTube</option></select></div><div><label>URL del Video *</label><input type="url" name="url" required value="${escapeHtml(data?.url||'')}" class="w-full p-2 bg-neutral-800 rounded"></div><div><label>Miniatura</label><input type="url" name="cover" value="${escapeHtml(data?.cover||'')}" class="w-full p-2 bg-neutral-800 rounded"></div><div><label>Duración</label><input type="text" name="duration" value="${escapeHtml(data?.duration||'')}" class="w-full p-2 bg-neutral-800 rounded"></div><div><label><input type="checkbox" name="active" value="1" ${data?.active!=0?'checked':''}> Activo</label></div><div class="flex gap-2 pt-2"><button type="button" onclick="closeModal()" class="flex-1 p-2 bg-neutral-700 rounded">Cancelar</button><button type="submit" class="flex-1 p-2 bg-primary rounded">Guardar</button></div></form>`; document.getElementById('modalContent').innerHTML=formHtml; document.getElementById('itemModal').classList.add('show'); document.getElementById('itemForm').addEventListener('submit',async(e)=>{ e.preventDefault(); const form=new FormData(e.target); const formData=Object.fromEntries(form); formData.active=form.has('active')?1:0; try{ const res=await fetch('api/save_video.php',{ method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(formData) }); const result=await res.json(); if(result.success){ showToast('Video guardado'); closeModal(); location.reload(); }else showToast(result.error,true); }catch(e){showToast('Error',true);} }); }
        function editVideo(id){ fetch('api/get_video.php?id='+id).then(r=>r.json()).then(data=>openVideoModal(data)).catch(e=>showToast('Error',true)); }
        
        // ==================== ÁLBUMES ====================
        function loadAlbumes() { fetch('api/get_albumes.php').then(res=>res.json()).then(albumes=>{ const container=document.getElementById('albumes-list'); const navCount=document.querySelector('[data-section="albumes"] .count'); if(navCount) navCount.textContent=albumes.length; if(albumes.length===0){ container.innerHTML=`<div class="col-span-full text-center py-8 bg-neutral-900 rounded-xl"><i class="fas fa-compact-disc text-5xl text-neutral-600 mb-2"></i><p class="text-neutral-400">No hay álbumes creados</p><button onclick="openAlbumModal()" class="mt-3 btn-primary">Crear primer álbum</button></div>`; return; } container.innerHTML=albumes.map(album=>`<div class="album-card"><div class="relative"><img src="${album.cover || 'assets/img/default-album.jpg'}" class="w-full aspect-square object-cover" onerror="this.src='assets/img/default-album.jpg'"><div class="absolute top-2 right-2 bg-black/70 px-2 py-1 rounded-full text-xs"><i class="fas fa-music mr-1"></i> ${album.total_canciones || 0} temas</div></div><div class="p-3"><h3 class="font-bold text-base truncate">${escapeHtml(album.title)}</h3><p class="text-sm text-neutral-400">${escapeHtml(album.artist)}</p><div class="flex gap-2 mt-2 text-xs"><span class="bg-neutral-800 px-2 py-1 rounded">${album.year || 'Año?'}</span><span class="bg-neutral-800 px-2 py-1 rounded">${album.genre || 'Género?'}</span><span class="bg-neutral-800 px-2 py-1 rounded"><i class="fas fa-download"></i> ${album.download_count || 0}</span></div><div class="flex gap-2 mt-2"><button onclick="editAlbum(${album.id})" class="flex-1 text-blue-400 py-1 rounded border border-neutral-700 text-sm"><i class="fas fa-edit"></i> Editar</button><button onclick="deleteAlbum(${album.id}, '${escapeHtml(album.title)}')" class="flex-1 text-red-400 py-1 rounded border border-neutral-700 text-sm"><i class="fas fa-trash"></i> Eliminar</button></div><button onclick="openCancionesModal(${album.id}, '${escapeHtml(album.title)}')" class="w-full mt-2 bg-primary/20 hover:bg-primary/30 text-primary py-1 rounded text-sm"><i class="fas fa-list-ul"></i> Canciones (${album.total_canciones || 0})</button></div></div>`).join(''); }).catch(err=>console.error('Error cargando álbumes:',err)); }
        
        function openAlbumModal(albumId=0){ if(albumId>0){ fetch(`api/get_album.php?id=${albumId}`).then(res=>res.json()).then(data=>{ if(data.success) showAlbumForm(data.album); else showToast('Error al cargar álbum',true); }); } else { showAlbumForm(null); } }
        
        function showAlbumForm(album){ const isEdit=album!==null; const formHtml=`<div class="flex justify-between items-center mb-3"><h3 class="text-lg font-bold">${isEdit?'Editar Álbum':'Nuevo Álbum'}</h3><button onclick="closeModal()" class="text-2xl hover:text-primary">&times;</button></div><form id="albumForm" class="space-y-3"><input type="hidden" name="id" value="${isEdit?album.id:0}"><div class="grid md:grid-cols-2 gap-3"><div><label class="text-sm">Título *</label><input type="text" name="title" required value="${escapeHtml(isEdit?album.title:'')}" class="w-full p-2 bg-neutral-800 rounded"></div><div><label class="text-sm">Artista *</label><input type="text" name="artist" required value="${escapeHtml(isEdit?album.artist:'')}" class="w-full p-2 bg-neutral-800 rounded"></div></div><div class="grid md:grid-cols-2 gap-3"><div><label class="text-sm">Género</label><input type="text" name="genre" value="${escapeHtml(isEdit?album.genre:'')}" class="w-full p-2 bg-neutral-800 rounded"></div><div><label class="text-sm">Año</label><input type="number" name="year" value="${isEdit?album.year:''}" class="w-full p-2 bg-neutral-800 rounded"></div></div><div><label class="text-sm">URL Portada</label><input type="url" name="cover" value="${escapeHtml(isEdit?album.cover:'')}" class="w-full p-2 bg-neutral-800 rounded"></div><div><label class="text-sm">URL ZIP (Opcional)</label><input type="url" name="zip_url" value="${escapeHtml(isEdit?album.zip_url:'')}" class="w-full p-2 bg-neutral-800 rounded"></div><div><label class="text-sm">Descripción</label><textarea name="description" rows="2" class="w-full p-2 bg-neutral-800 rounded">${escapeHtml(isEdit?album.description:'')}</textarea></div><div class="flex items-center gap-2"><input type="checkbox" name="active" value="1" ${isEdit&&album.active==1?'checked':'checked'}><label class="text-sm">Activo</label></div><div class="flex gap-2 pt-2"><button type="button" onclick="closeModal()" class="flex-1 p-2 bg-neutral-700 rounded">Cancelar</button><button type="submit" class="flex-1 p-2 bg-primary rounded">Guardar</button></div></form>`; document.getElementById('modalContent').innerHTML=formHtml; document.getElementById('itemModal').classList.add('show'); 
        document.getElementById('albumForm').addEventListener('submit', async (e) => { e.preventDefault(); const id = document.querySelector('[name="id"]')?.value || '0'; const title = document.querySelector('[name="title"]')?.value || ''; const artist = document.querySelector('[name="artist"]')?.value || ''; const genre = document.querySelector('[name="genre"]')?.value || ''; const year = document.querySelector('[name="year"]')?.value || ''; const cover = document.querySelector('[name="cover"]')?.value || ''; const zip_url = document.querySelector('[name="zip_url"]')?.value || ''; const description = document.querySelector('[name="description"]')?.value || ''; const active = document.querySelector('[name="active"]')?.checked ? '1' : '0'; if (!title || !artist) { showToast('Título y artista son requeridos', true); return; } const formData = new FormData(); formData.append('id', id); formData.append('title', title); formData.append('artist', artist); formData.append('genre', genre); formData.append('year', year); formData.append('cover', cover); formData.append('zip_url', zip_url); formData.append('description', description); formData.append('active', active); try { const res = await fetch('api/save_album.php', { method: 'POST', body: formData }); const result = await res.json(); if (result.success) { showToast('Álbum guardado'); closeModal(); loadAlbumes(); } else { showToast(result.error, true); } } catch(e) { showToast('Error al guardar', true); } }); }
        
        function editAlbum(id){ openAlbumModal(id); }
        function deleteAlbum(id, title){ if(confirm(`¿Eliminar "${title}"? Se eliminarán sus canciones.`)){ fetch('api/delete_album.php',{ method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id:id}) }).then(res=>res.json()).then(data=>{ if(data.success){ showToast('Álbum eliminado'); loadAlbumes(); }else showToast(data.error,true); }); } }
        
        let currentCancionAlbumId=0, currentCancionAlbumTitle='';
        function openCancionesModal(albumId, albumTitle){ currentCancionAlbumId=albumId; currentCancionAlbumTitle=albumTitle; loadCanciones(albumId); }
        function loadCanciones(albumId){ fetch(`api/get_album.php?id=${albumId}`).then(res=>res.json()).then(data=>{ if(data.success){ const canciones=data.canciones||[]; const formHtml=`<div class="flex justify-between items-center mb-3"><h3 class="text-lg font-bold">Canciones: ${escapeHtml(currentCancionAlbumTitle)}</h3><button onclick="closeModal()" class="text-2xl hover:text-primary">&times;</button></div><div class="mb-3"><button onclick="openCancionModal(0)" class="btn-primary text-sm"><i class="fas fa-plus mr-1"></i>Agregar Canción</button></div><div class="space-y-2 max-h-96 overflow-y-auto">${canciones.length>0?canciones.map(c=>`<div class="bg-neutral-800 rounded-lg p-2 flex justify-between items-center"><div><span class="text-primary font-mono text-sm">${c.track_number}</span><span class="ml-2">${escapeHtml(c.title)}</span><p class="text-xs text-neutral-500">${c.duration||'--:--'} • ${c.sizeMB||0} MB</p></div><div><button onclick="openCancionModal(${c.id})" class="text-blue-400 mr-2"><i class="fas fa-edit"></i></button><button onclick="deleteCancion(${c.id}, '${escapeHtml(c.title)}')" class="text-red-400"><i class="fas fa-trash"></i></button></div></div>`).join(''):`<div class="text-center py-8 text-neutral-500"><i class="fas fa-music text-4xl mb-2"></i><p>No hay canciones</p><button onclick="openCancionModal(0)" class="mt-2 text-primary">+ Agregar primera</button></div>`}</div>`; document.getElementById('modalContent').innerHTML=formHtml; document.getElementById('itemModal').classList.add('show'); } }); }
        function openCancionModal(cancionId=0, cancionData=null){ const isEdit=cancionId>0||(cancionData&&cancionData.id); let formHtml=`<div class="flex justify-between items-center mb-3"><h3 class="text-lg font-bold">${isEdit?'Editar Canción':'Agregar Canción'}</h3><button onclick="closeModal()" class="text-2xl hover:text-primary">&times;</button></div><form id="cancionForm" class="space-y-3"><input type="hidden" name="id" value="${isEdit?(cancionData?.id||cancionId):0}"><input type="hidden" name="album_id" value="${currentCancionAlbumId}"><div><label class="text-sm">Número de pista</label><input type="number" name="track_number" value="${cancionData?.track_number||0}" class="w-full p-2 bg-neutral-800 rounded"></div><div><label class="text-sm">Título *</label><input type="text" name="title" required value="${escapeHtml(cancionData?.title||'')}" class="w-full p-2 bg-neutral-800 rounded"></div><div><label class="text-sm">Duración</label><input type="text" name="duration" value="${cancionData?.duration||''}" placeholder="mm:ss" class="w-full p-2 bg-neutral-800 rounded"></div><div><label class="text-sm">URL MP3 *</label><input type="url" name="url" required value="${escapeHtml(cancionData?.url||'')}" class="w-full p-2 bg-neutral-800 rounded"></div><div><label class="text-sm">Tamaño (MB)</label><input type="number" name="sizeMB" value="${cancionData?.sizeMB||0}" class="w-full p-2 bg-neutral-800 rounded"></div><div class="flex gap-2 pt-2"><button type="button" onclick="closeModal()" class="flex-1 p-2 bg-neutral-700 rounded">Cancelar</button><button type="submit" class="flex-1 p-2 bg-primary rounded">Guardar</button></div></form>`; document.getElementById('modalContent').innerHTML=formHtml; document.getElementById('itemModal').classList.add('show'); document.getElementById('cancionForm').addEventListener('submit',async(e)=>{ e.preventDefault(); const form=new FormData(e.target); const formData=Object.fromEntries(form); try{ const res=await fetch('api/save_cancion.php',{ method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(formData) }); const result=await res.json(); if(result.success){ showToast('Canción guardada'); closeModal(); loadCanciones(currentCancionAlbumId); }else showToast(result.error,true); }catch(e){showToast('Error',true);} }); }
        function deleteCancion(id, title){ if(confirm(`¿Eliminar "${title}"?`)){ fetch('api/delete_cancion.php',{ method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({id:id}) }).then(res=>res.json()).then(data=>{ if(data.success){ showToast('Canción eliminada'); loadCanciones(currentCancionAlbumId); }else showToast(data.error,true); }); } }
        
        // ==================== BANNERS (PUBLICIDAD) ====================
        function loadBanners() {
            fetch('api/get_banners_admin.php')
                .then(res => res.json())
                .then(banners => {
                    const sidebarContainer = document.getElementById('banners-sidebar-list');
                    const horizontalContainer = document.getElementById('banners-horizontal-list');
                    const sidebarBanners = banners.filter(b => b.type === 'sidebar');
                    const horizontalBanners = banners.filter(b => b.type === 'horizontal');
                    const countSpan = document.querySelector('[data-section="banners"] .count');
                    
                    if (countSpan) countSpan.textContent = banners.length;
                    
                    // Banners Sidebar
                    if (sidebarBanners.length === 0) {
                        sidebarContainer.innerHTML = `<div class="col-span-full text-center py-8 bg-neutral-800/30 rounded-xl"><i class="fas fa-ad text-4xl text-neutral-600 mb-2"></i><p class="text-neutral-400">No hay banners en sidebar</p><button onclick="openBannerModal()" class="mt-3 text-primary text-sm">+ Agregar banner</button></div>`;
                    } else {
                        sidebarContainer.innerHTML = sidebarBanners.map(banner => `
                            <div class="bg-neutral-800 rounded-xl overflow-hidden border border-neutral-700 hover:border-primary transition">
                                <div class="relative">
                                    <img src="${banner.image}" class="w-full aspect-[300/250] object-cover" onerror="this.src='assets/img/default-banner.jpg'">
                                    <div class="absolute top-2 right-2 flex gap-1">
                                        <span class="bg-black/70 text-xs px-2 py-1 rounded-full"><i class="fas fa-eye"></i> ${banner.impressions || 0}</span>
                                        <span class="bg-black/70 text-xs px-2 py-1 rounded-full"><i class="fas fa-mouse-pointer"></i> ${banner.clicks || 0}</span>
                                    </div>
                                </div>
                                <div class="p-3">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h3 class="font-semibold text-sm">${escapeHtml(banner.name)}</h3>
                                            <p class="text-xs text-neutral-500">Posición: ${banner.position} | ${banner.size}</p>
                                            <p class="text-xs text-neutral-500">Vigencia: ${banner.start_date || 'Siempre'} - ${banner.end_date || 'Siempre'}</p>
                                        </div>
                                        <div class="flex gap-2">
                                            <button onclick="editBanner(${banner.id})" class="text-blue-400 hover:text-blue-300"><i class="fas fa-edit"></i></button>
                                            <button onclick="deleteBanner(${banner.id}, '${escapeHtml(banner.name)}')" class="text-red-400 hover:text-red-300"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </div>
                                    <div class="mt-2 flex gap-2 items-center">
                                        <span class="text-xs ${banner.active ? 'text-green-400' : 'text-red-400'}">${banner.active ? '✅ Activo' : '❌ Inactivo'}</span>
                                        <a href="${banner.url}" target="_blank" class="text-xs text-primary hover:underline">Ver enlace →</a>
                                    </div>
                                </div>
                            </div>
                        `).join('');
                    }
                    
                    // Banners Horizontales
                    if (horizontalBanners.length === 0) {
                        horizontalContainer.innerHTML = `<div class="text-center py-8 bg-neutral-800/30 rounded-xl"><i class="fas fa-ad text-4xl text-neutral-600 mb-2"></i><p class="text-neutral-400">No hay banners horizontales</p><button onclick="openBannerModal()" class="mt-3 text-primary text-sm">+ Agregar banner horizontal</button></div>`;
                    } else {
                        horizontalContainer.innerHTML = horizontalBanners.map(banner => `
                            <div class="bg-neutral-800 rounded-xl overflow-hidden border border-neutral-700 hover:border-primary transition">
                                <div class="flex flex-col md:flex-row">
                                    <img src="${banner.image}" class="w-full md:w-48 h-24 object-cover" onerror="this.src='assets/img/default-banner.jpg'">
                                    <div class="p-3 flex-1">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h3 class="font-semibold">${escapeHtml(banner.name)}</h3>
                                                <p class="text-xs text-neutral-500">Posición: ${banner.position} | ${banner.size}</p>
                                                <p class="text-xs text-neutral-500">Vigencia: ${banner.start_date || 'Siempre'} - ${banner.end_date || 'Siempre'}</p>
                                            </div>
                                            <div class="flex gap-2">
                                                <button onclick="editBanner(${banner.id})" class="text-blue-400"><i class="fas fa-edit"></i></button>
                                                <button onclick="deleteBanner(${banner.id}, '${escapeHtml(banner.name)}')" class="text-red-400"><i class="fas fa-trash"></i></button>
                                            </div>
                                        </div>
                                        <div class="mt-2 flex gap-2">
                                            <span class="text-xs ${banner.active ? 'text-green-400' : 'text-red-400'}">${banner.active ? '✅ Activo' : '❌ Inactivo'}</span>
                                            <span class="text-xs text-neutral-500"><i class="fas fa-eye"></i> ${banner.impressions || 0} | <i class="fas fa-mouse-pointer"></i> ${banner.clicks || 0}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `).join('');
                    }
                })
                .catch(err => console.error('Error cargando banners:', err));
        }
        
        function openBannerModal(bannerId = 0) {
            if (bannerId > 0) {
                fetch(`api/get_banner.php?id=${bannerId}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.id) showBannerForm(data);
                        else showToast('Error al cargar banner', true);
                    })
                    .catch(err => {
                        console.error('Error:', err);
                        showToast('Error al cargar banner', true);
                    });
            } else {
                showBannerForm(null);
            }
        }
        
        function showBannerForm(banner) {
            const isEdit = banner !== null;
            const formHtml = `
                <div class="flex justify-between items-center mb-4 border-b border-neutral-700 pb-3">
                    <h3 class="text-lg md:text-xl font-bold">${isEdit ? 'Editar Banner' : 'Nuevo Banner'}</h3>
                    <button onclick="closeModal()" class="text-2xl hover:text-primary">&times;</button>
                </div>
                <form id="bannerForm" class="space-y-4">
                    <input type="hidden" name="id" value="${isEdit ? banner.id : 0}">
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm mb-1">Nombre del Banner *</label>
                            <input type="text" name="name" required value="${escapeHtml(isEdit ? banner.name : '')}" class="w-full p-2 bg-neutral-800 rounded">
                        </div>
                        <div>
                            <label class="block text-sm mb-1">Tipo</label>
                            <select name="type" class="w-full p-2 bg-neutral-800 rounded">
                                <option value="sidebar" ${isEdit && banner.type === 'sidebar' ? 'selected' : ''}>Sidebar (300x250 px)</option>
                                <option value="horizontal" ${isEdit && banner.type === 'horizontal' ? 'selected' : ''}>Horizontal (728x90 px)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm mb-1">URL de la Imagen (Backblaze) *</label>
                        <input type="url" name="image" required value="${escapeHtml(isEdit ? banner.image : '')}" placeholder="https://f005.backblazeb2.com/file/..." class="w-full p-2 bg-neutral-800 rounded">
                        <p class="text-xs text-neutral-500 mt-1">Tamaño recomendado: 300x250 o 728x90 píxeles</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm mb-1">URL de Destino (donde irá el clic)</label>
                        <input type="url" name="url" value="${escapeHtml(isEdit ? banner.url : '#')}" class="w-full p-2 bg-neutral-800 rounded">
                        <p class="text-xs text-neutral-500 mt-1">Ej: https://wa.me/507XXXXXXXX o https://instagram.com/...</p>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm mb-1">Posición (orden)</label>
                            <input type="number" name="position" value="${isEdit ? banner.position : 1}" class="w-full p-2 bg-neutral-800 rounded">
                        </div>
                        <div>
                            <label class="block text-sm mb-1">Tamaño</label>
                            <input type="text" name="size" value="${isEdit ? banner.size : '300x250'}" class="w-full p-2 bg-neutral-800 rounded">
                        </div>
                    </div>
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm mb-1">Fecha Inicio (opcional)</label>
                            <input type="date" name="start_date" value="${isEdit && banner.start_date ? banner.start_date : ''}" class="w-full p-2 bg-neutral-800 rounded">
                        </div>
                        <div>
                            <label class="block text-sm mb-1">Fecha Fin (opcional)</label>
                            <input type="date" name="end_date" value="${isEdit && banner.end_date ? banner.end_date : ''}" class="w-full p-2 bg-neutral-800 rounded">
                        </div>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="active" value="1" ${isEdit && banner.active == 1 ? 'checked' : 'checked'}>
                        <label>Activo (visible en el sitio)</label>
                    </div>
                    
                    <div class="flex gap-3 pt-4 border-t border-neutral-700">
                        <button type="button" onclick="closeModal()" class="flex-1 p-2 bg-neutral-700 rounded">Cancelar</button>
                        <button type="submit" class="flex-1 p-2 bg-primary rounded">Guardar Banner</button>
                    </div>
                </form>
            `;
            
            document.getElementById('modalContent').innerHTML = formHtml;
            document.getElementById('itemModal').classList.add('show');
            
            document.getElementById('bannerForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const form = new FormData(e.target);
                
                try {
                    const res = await fetch('api/save_banner.php', { method: 'POST', body: form });
                    const result = await res.json();
                    if (result.success) {
                        showToast('Banner guardado correctamente');
                        closeModal();
                        loadBanners();
                    } else {
                        showToast(result.error || 'Error al guardar', true);
                    }
                } catch(e) {
                    console.error('Error:', e);
                    showToast('Error al guardar', true);
                }
            });
        }
        
        function editBanner(id) {
            openBannerModal(id);
        }
        
        function deleteBanner(id, name) {
            if (confirm(`¿Eliminar el banner "${name}"? Esta acción no se puede deshacer.`)) {
                fetch('api/delete_banner.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        showToast('Banner eliminado correctamente');
                        loadBanners();
                    } else {
                        showToast(data.error || 'Error al eliminar', true);
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    showToast('Error de conexión al eliminar', true);
                });
            }
        }
        
        // ==================== CONFIGURACIÓN ====================
        async function saveSettings(group, data) {
            try { 
                const res = await fetch('api/save_settings.php', { 
                    method: 'POST', 
                    headers: { 'Content-Type': 'application/json' }, 
                    body: JSON.stringify({ group, data }) 
                }); 
                const result = await res.json(); 
                if (result.success) {
                    showToast('✅ Configuración guardada');
                    // Actualizar el badge de mantenimiento en el dashboard si cambió
                    if (group === 'display' && data.maintenance_mode !== undefined) {
                        const statusSpan = document.getElementById('maintenance-status');
                        if (statusSpan) {
                            if (data.maintenance_mode == 1) {
                                statusSpan.innerHTML = '🔴 ACTIVADO';
                                statusSpan.className = 'text-xs px-2 py-1 rounded-full bg-red-500/20 text-red-400';
                            } else {
                                statusSpan.innerHTML = '🟢 DESACTIVADO';
                                statusSpan.className = 'text-xs px-2 py-1 rounded-full bg-green-500/20 text-green-400';
                            }
                        }
                        // Recargar para mostrar el badge en el dashboard
                        setTimeout(() => location.reload(), 1500);
                    }
                } else {
                    showToast('❌ Error: ' + (result.error || 'No se pudo guardar'), true);
                }
            } catch(e) { 
                showToast('❌ Error de conexión', true); 
            } 
        }
        
        document.getElementById('config-general-form')?.addEventListener('submit',async(e)=>{ e.preventDefault(); const data={site_title:document.getElementById('site_title').value,site_description:document.getElementById('site_description').value,footer_text:document.getElementById('footer_text').value}; await saveSettings('general',data); });
        document.getElementById('config-radio-form')?.addEventListener('submit',async(e)=>{ e.preventDefault(); const data={radio_url:document.getElementById('radio_url').value,radio_name:document.getElementById('radio_name').value}; await saveSettings('radio',data); });
        document.getElementById('config-display-form')?.addEventListener('submit',async(e)=>{ e.preventDefault(); const data={superpack_threshold:document.getElementById('superpack_threshold').value,maintenance_mode:document.getElementById('maintenance_mode').checked?'1':'0'}; await saveSettings('display',data); });
        document.getElementById('config-guia-form')?.addEventListener('submit',async(e)=>{ e.preventDefault(); const data={guia_title:document.getElementById('guia_title').value,guia_whatsapp:document.getElementById('guia_whatsapp').value}; await saveSettings('guia',data); });
        
        // ==================== LOGO ====================
        function uploadLogo(){ const input=document.createElement('input'); input.type='file'; input.accept='image/*'; input.onchange=e=>{ const file=e.target.files[0]; const formData=new FormData(); formData.append('image',file); formData.append('type','logo'); const preview=document.getElementById('logo-preview'); if(preview){ const reader=new FileReader(); reader.onload=function(e){preview.src=e.target.result;}; reader.readAsDataURL(file); } fetch('api/upload_image.php',{method:'POST',body:formData}).then(r=>r.json()).then(data=>{ if(data.success){showToast('Logo actualizado');setTimeout(()=>location.reload(),1000);} else showToast(data.error,true); }); }; input.click(); }
        
        // ==================== USUARIOS ====================
        function showChangePasswordModal(id, username) { document.getElementById('change-user-id').value = id; document.getElementById('change-username').value = username; document.getElementById('new-password-admin').value = ''; document.getElementById('confirm-password-admin').value = ''; document.getElementById('passwordModal').classList.add('show'); }
        function deleteUser(id){ if(confirm('¿Desactivar este usuario?')){ fetch('api/delete_user.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})}).then(r=>r.json()).then(data=>{if(data.success){showToast('Usuario desactivado');location.reload();}else showToast(data.error,true);}).catch(e=>showToast('Error',true)); } }
        function loadUsers() { fetch('api/get_users.php').then(res=>res.json()).then(users=>{ const tbody=document.getElementById('users-table'); if(tbody && users.length>0){ tbody.innerHTML=users.map(user=>`<tr class="border-b"><td class="p-2">${user.id}<\/td><td class="p-2">${escapeHtml(user.username)}<\/td><td class="p-2">${escapeHtml(user.email)}<\/td><td class="p-2">${user.role==='superadmin'?'Super Admin':user.role==='admin'?'Admin':user.role}<\/td><td class="p-2"><span class="status-badge ${user.active?'status-active':'status-inactive'}">${user.active?'Activo':'Inactivo'}</span><\/td><td class="p-2"><button onclick="showChangePasswordModal(${user.id},'${escapeHtml(user.username)}')" class="text-yellow-400 mr-1"><i class="fas fa-key"><\/i><\/button><button onclick="deleteUser(${user.id})" class="text-red-400"><i class="fas fa-trash"><\/i><\/button><\/td><\/tr>`).join(''); } }).catch(err=>console.error('Error cargando usuarios:',err)); }
        function loadSettings() { fetch('api/get_settings.php').then(res=>res.json()).then(settings=>{ if(settings.site_title) document.getElementById('site_title').value=settings.site_title; if(settings.site_description) document.getElementById('site_description').value=settings.site_description; if(settings.footer_text) document.getElementById('footer_text').value=settings.footer_text; if(settings.radio_url) document.getElementById('radio_url').value=settings.radio_url; if(settings.radio_name) document.getElementById('radio_name').value=settings.radio_name; if(settings.superpack_threshold) document.getElementById('superpack_threshold').value=settings.superpack_threshold; if(settings.maintenance_mode) document.getElementById('maintenance_mode').checked=settings.maintenance_mode==1; if(settings.guia_title) document.getElementById('guia_title').value=settings.guia_title; if(settings.guia_whatsapp) document.getElementById('guia_whatsapp').value=settings.guia_whatsapp; }).catch(err=>console.error('Error cargando configuración:',err)); }
        
        // ==================== CAMBIAR CONTRASEÑA ====================
        document.getElementById('change-password-form')?.addEventListener('submit',async(e)=>{ e.preventDefault(); const current=document.getElementById('current-password').value; const newPass=document.getElementById('new-password').value; const confirm=document.getElementById('confirm-password').value; if(newPass!==confirm)return showToast('Las contraseñas no coinciden',true); if(newPass.length<6)return showToast('Mínimo 6 caracteres',true); try{ const res=await fetch('api/change_password.php',{ method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify({current,new_password:newPass}) }); const result=await res.json(); if(result.success){ showToast('Contraseña actualizada'); document.getElementById('change-password-form').reset(); }else showToast(result.error,true); }catch(e){showToast('Error',true);} });
        document.getElementById('change-password-admin-form')?.addEventListener('submit', async (e) => { e.preventDefault(); const userId = document.getElementById('change-user-id').value; const newPassword = document.getElementById('new-password-admin').value; const confirm = document.getElementById('confirm-password-admin').value; if (newPassword !== confirm) return showToast('Las contraseñas no coinciden', true); if (newPassword.length < 6) return showToast('Mínimo 6 caracteres', true); try { const res = await fetch('api/change_password.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ user_id: userId, new_password: newPassword }) }); const result = await res.json(); if (result.success) { showToast('Contraseña actualizada'); closePasswordModal(); } else showToast(result.error, true); } catch(e) { showToast('Error de conexión', true); } });
        
        // ==================== MANTENIMIENTO ====================
        function clearCache(){ if(confirm('¿Limpiar caché?')){ fetch('api/clear_cache.php',{method:'POST'}).then(r=>r.json()).then(data=>{if(data.success)showToast('Caché limpiado');else showToast('Error',true);}); } }
        function backupDatabase(){ if(confirm('¿Crear backup?')){ window.location.href='api/backup.php'; } }
        function optimizeDatabase(){ if(confirm('¿Optimizar BD?')){ fetch('api/optimize.php',{method:'POST'}).then(r=>r.json()).then(data=>{if(data.success)showToast('BD optimizada');else showToast('Error',true);}); } }
        
        // ==================== COMPARTIR ====================
        function shareMix(id, title, dj) { const url = `${window.location.origin}/panda-truck-v2/player/index.php?id=${id}`; const message = `🎵 *NUEVO MIX EN PANDA TRUCK RELOADED!* 🎵\n\n🎧 *${title}*\n🎚️ Por: *${dj}*\n\n🔗 Escúchalo aquí: ${url}`; window.open(`https://wa.me/?text=${encodeURIComponent(message)}`, '_blank'); }
        function shareVideo(id, title, dj) { const url = `${window.location.origin}/panda-truck-v2/player/video.php?id=${id}`; const message = `🎬 *NUEVO VIDEO EN PANDA TRUCK RELOADED!* 🎬\n\n🎥 *${title}*\n🎚️ Por: *${dj}*\n\n🔗 Míralo aquí: ${url}`; window.open(`https://wa.me/?text=${encodeURIComponent(message)}`, '_blank'); }
        function shareDJ(id, name) { const url = `${window.location.origin}/panda-truck-v2/dj/perfil.php?dj=${encodeURIComponent(name)}`; const message = `🎧 *DJ ${name} en Panda Truck Reloaded!* 🎧\n\n🎵 Escucha todos sus mixes aquí:\n${url}`; window.open(`https://wa.me/?text=${encodeURIComponent(message)}`, '_blank'); }
        
        // ==================== EVENTOS (Placeholder) ====================
        function openEventModal(){showToast('En desarrollo',true);}
        function editEvent(id){showToast('En desarrollo',true);}
        function deleteEvent(id){showToast('En desarrollo',true);}
        function openUserModal(){showToast('En desarrollo',true);}
        
        // ==================== INICIALIZAR ====================
        loadAlbumes();
        if (document.getElementById('users-section')) loadUsers();
        if (document.getElementById('settings-section')) loadSettings();
        loadBanners();


        // ==================== ACTIVAR/DESACTIVAR MANTENIMIENTO ====================
        function activarMantenimiento() {
            if(confirm('⚠️ ¿ACTIVAR modo mantenimiento?\n\nLos usuarios normales verán la página de mantenimiento.\nSolo los administradores podrán acceder al sitio.\n\n¿Continuar?')) {
                fetch('api/set_maintenance.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ mode: 1 })
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        showToast('✅ Modo mantenimiento ACTIVADO');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast('❌ Error: ' + (data.error || 'No se pudo activar'), true);
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    showToast('❌ Error de conexión', true);
                });
            }
        }

        function desactivarMantenimiento() {
            if(confirm('✅ ¿DESACTIVAR modo mantenimiento?\n\nEl sitio volverá a la normalidad para todos los usuarios.\n\n¿Continuar?')) {
                fetch('api/set_maintenance.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ mode: 0 })
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        showToast('✅ Modo mantenimiento DESACTIVADO');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showToast('❌ Error: ' + (data.error || 'No se pudo desactivar'), true);
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    showToast('❌ Error de conexión', true);
                });
            }
        }
    </script>
</body>
</html>
