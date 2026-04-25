<?php
// maintenance.php - Página de mantenimiento para Panda Truck Reloaded
session_start();

// Verificar si el usuario es administrador (puede saltarse el mantenimiento)
$isAdmin = false;
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    $isAdmin = ($_SESSION['user_role'] === 'superadmin' || $_SESSION['user_role'] === 'admin');
}

// Si es administrador, puede continuar al sitio
if ($isAdmin && isset($_GET['skip'])) {
    header('Location: index.php');
    exit;
}

// Obtener tiempo estimado de la configuración (opcional)
$estimated_time = "30 minutos";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Mantenimiento - Panda Truck Reloaded</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            padding: 1rem;
        }
        
        .maintenance-container {
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        
        /* Logo y animación */
        .logo {
            margin-bottom: 2rem;
        }
        
        .logo img {
            width: 120px;
            height: auto;
            filter: drop-shadow(0 0 20px rgba(225, 38, 29, 0.3));
        }
        
        /* Ícono de herramientas animado */
        .tools-icon {
            font-size: 5rem;
            color: #e1261d;
            margin-bottom: 1.5rem;
            animation: spin 3s ease-in-out infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            50% { transform: rotate(15deg); }
            100% { transform: rotate(0deg); }
        }
        
        /* Título */
        h1 {
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #ffffff 0%, #e1261d 100%);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 1rem;
        }
        
        /* Descripción */
        .description {
            font-size: 1.1rem;
            color: #9ca3af;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        
        /* Tarjeta de tiempo */
        .time-card {
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(225, 38, 29, 0.3);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .time-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #e1261d;
            margin-bottom: 0.5rem;
        }
        
        .time-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
        }
        
        /* Barra de progreso */
        .progress-bar {
            width: 100%;
            height: 4px;
            background: #2d2d2d;
            border-radius: 4px;
            margin-top: 1rem;
            overflow: hidden;
        }
        
        .progress-fill {
            width: 0%;
            height: 100%;
            background: linear-gradient(90deg, #e1261d, #ff6b6b);
            border-radius: 4px;
            animation: progress 2s ease-in-out infinite alternate;
        }
        
        @keyframes progress {
            0% { width: 10%; }
            100% { width: 90%; }
        }
        
        /* Redes sociales */
        .social-links {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .social-link {
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .social-link:hover {
            background: #e1261d;
            transform: translateY(-5px);
        }
        
        /* Botón para administradores */
        .admin-link {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: transparent;
            border: 1px solid #e1261d;
            color: #e1261d;
            text-decoration: none;
            border-radius: 2rem;
            font-size: 0.8rem;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }
        
        .admin-link:hover {
            background: #e1261d;
            color: white;
        }
        
        /* Nota para admins */
        .admin-note {
            margin-top: 1.5rem;
            font-size: 0.7rem;
            color: #4a4a4a;
        }
        
        /* Partículas de fondo (opcional) */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
        
        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: rgba(225, 38, 29, 0.3);
            border-radius: 50%;
            animation: float 8s infinite linear;
        }
        
        @keyframes float {
            0% {
                transform: translateY(100vh) scale(1);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) scale(0.5);
                opacity: 0;
            }
        }
        
        /* Responsive */
        @media (max-width: 640px) {
            h1 {
                font-size: 1.8rem;
            }
            .description {
                font-size: 0.9rem;
            }
            .tools-icon {
                font-size: 3.5rem;
            }
            .social-link {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Partículas decorativas -->
    <div class="particles" id="particles"></div>
    
    <div class="maintenance-container">
        <!-- Logo -->
        <div class="logo">
            <img src="assets/img/logo.png" alt="Panda Truck Reloaded" onerror="this.src='https://via.placeholder.com/120x120?text=PTR'">
        </div>
        
        <!-- Ícono animado -->
        <div class="tools-icon">
            <i class="fas fa-headphones"></i>
        </div>
        
        <!-- Título -->
        <h1>¡Volvemos pronto!</h1>
        
        <!-- Descripción -->
        <p class="description">
            Estamos afinando los parlantes, actualizando la música<br>
            y mejorando tu experiencia en Panda Truck Reloaded.
        </p>
        
        <!-- Tarjeta de tiempo -->
        <div class="time-card">
            <div class="time-label">
                <i class="fas fa-clock"></i> TIEMPO ESTIMADO
            </div>
            <div class="time-value">
                <?php echo $estimated_time; ?>
            </div>
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
        </div>
        
        <!-- Redes Sociales -->
        <div class="social-links">
            <a href="https://www.instagram.com/pandatruck507" target="_blank" class="social-link" title="Instagram">
                <i class="fab fa-instagram"></i>
            </a>
            <a href="https://www.facebook.com/pandatruck507" target="_blank" class="social-link" title="Facebook">
                <i class="fab fa-facebook-f"></i>
            </a>
            <a href="https://wa.me/50762115209" target="_blank" class="social-link" title="WhatsApp">
                <i class="fab fa-whatsapp"></i>
            </a>
            <a href="https://www.tiktok.com/@pandatruck507" target="_blank" class="social-link" title="TikTok">
                <i class="fab fa-tiktok"></i>
            </a>
            <a href="https://www.youtube.com/c/pandatruck507" target="_blank" class="social-link" title="YouTube">
                <i class="fab fa-youtube"></i>
            </a>
        </div>
        
        <!-- Enlace para administradores -->
        <a href="dashboard.php" class="admin-link">
            <i class="fas fa-lock"></i> Acceso administradores
        </a>
        
        <div class="admin-note">
            <i class="fas fa-shield-alt"></i> Solo personal autorizado
        </div>
    </div>
    
    <script>
        // Generar partículas aleatorias
        function createParticles() {
            const container = document.getElementById('particles');
            const particleCount = 50;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                // Posición aleatoria
                particle.style.left = Math.random() * 100 + '%';
                
                // Tamaño aleatorio
                const size = Math.random() * 4 + 1;
                particle.style.width = size + 'px';
                particle.style.height = size + 'px';
                
                // Duración aleatoria
                const duration = Math.random() * 5 + 3;
                particle.style.animationDuration = duration + 's';
                
                // Retraso aleatorio
                const delay = Math.random() * 5;
                particle.style.animationDelay = delay + 's';
                
                container.appendChild(particle);
            }
        }
        
        // Actualizar barra de progreso simulada
        let progress = 0;
        function updateProgress() {
            const fill = document.querySelector('.progress-fill');
            if (fill) {
                progress = Math.min(progress + Math.random() * 3, 95);
                fill.style.width = progress + '%';
            }
        }
        
        // Inicializar
        createParticles();
        setInterval(updateProgress, 2000);
        
        // Verificar cada 30 segundos si el mantenimiento terminó
        setInterval(function() {
            fetch('api/check_maintenance.php')
                .then(res => res.json())
                .then(data => {
                    if (!data.maintenance) {
                        location.reload();
                    }
                })
                .catch(() => {});
        }, 30000);
    </script>
</body>
</html>