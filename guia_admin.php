<?php
// guia_admin.php - Guía de usuario para administradores
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Verificar que el usuario esté logueado y sea admin
$auth->requireLogin();
if (!$auth->isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

$user_role = $_SESSION['user_role'];
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Guía de Usuario - Panda Truck Reloaded</title>
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
        .guide-card {
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(225, 38, 29, 0.2);
            transition: all 0.3s ease;
        }
        .guide-card:hover {
            border-color: var(--primary);
            transform: translateY(-4px);
        }
        .step-number {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
        }
        .code-block {
            background: #1e1e1e;
            border-left: 3px solid var(--primary);
            padding: 12px;
            border-radius: 8px;
            font-family: monospace;
            font-size: 12px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .credential-box {
            background: linear-gradient(135deg, #1a1a1a 0%, #0f0f0f 100%);
            border: 1px solid var(--primary);
            border-radius: 12px;
            padding: 16px;
        }
        .copy-btn {
            cursor: pointer;
            transition: all 0.2s;
        }
        .copy-btn:hover {
            transform: scale(1.05);
            color: var(--primary);
        }
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #10b981;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            z-index: 1000;
            transform: translateX(400px);
            transition: transform 0.3s ease;
        }
        .toast.show {
            transform: translateX(0);
        }
        @media (max-width: 768px) {
            .guide-card {
                margin-bottom: 1rem;
            }
            .code-block {
                font-size: 10px;
            }
        }
    </style>
</head>
<body class="text-white">
    <!-- Header -->
    <header class="sticky top-0 z-40 bg-neutral-900/90 backdrop-blur border-b border-neutral-800">
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="dashboard.php" class="flex items-center gap-2">
                <img src="assets/img/logo.png" alt="Panda Truck" class="h-10">
                <span class="text-xl font-bold hidden sm:inline">Panda Truck <span class="text-primary">Reloaded</span></span>
            </a>
            <div class="flex items-center gap-4">
                <span class="text-sm text-neutral-400 hidden md:inline">Bienvenido, <?php echo htmlspecialchars($username); ?></span>
                <!-- Botón Volver al Dashboard ELIMINADO -->
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8">
        <!-- Hero -->
        <section class="text-center mb-12">
            <div class="inline-block px-4 py-2 bg-primary/20 rounded-full mb-4">
                <i class="fas fa-book-open text-primary mr-2"></i> Guía de Usuario
            </div>
            <h1 class="text-3xl md:text-4xl font-bold mb-4">Guía para Administradores</h1>
            <p class="text-neutral-400 max-w-2xl mx-auto">
                Aprende a subir contenido a Backblaze y gestionar tu plataforma Panda Truck Reloaded
            </p>
        </section>

        <!-- Credenciales -->
        <section class="mb-12">
            <div class="guide-card rounded-2xl p-6">
                <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                    <i class="fas fa-key text-primary"></i> Tus Credenciales de Acceso
                </h2>
                <div class="grid md:grid-cols-2 gap-6">
                    <div class="credential-box">
                        <h3 class="font-semibold mb-3 flex items-center gap-2">
                            <i class="fab fa-backblaze text-primary"></i> Backblaze B2
                        </h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between items-center p-2 bg-neutral-800 rounded">
                                <span class="text-neutral-400">KeyID:</span>
                                <span class="font-mono" id="backblaze-keyid">005205be2fa8c250000000003</span>
                                <i class="fas fa-copy copy-btn text-neutral-400 hover:text-primary" onclick="copyToClipboard('backblaze-keyid')"></i>
                            </div>
                            <div class="flex justify-between items-center p-2 bg-neutral-800 rounded">
                                <span class="text-neutral-400">ApplicationKey:</span>
                                <span class="font-mono text-xs break-all" id="backblaze-appkey">K005o97SP8IJ0Uac0PJZZjTPSs1DuCc</span>
                                <i class="fas fa-copy copy-btn text-neutral-400 hover:text-primary" onclick="copyToClipboard('backblaze-appkey')"></i>
                            </div>
                            <div class="flex justify-between items-center p-2 bg-neutral-800 rounded">
                                <span class="text-neutral-400">Bucket:</span>
                                <span class="font-mono">DJIMMY-PANDA</span>
                                <i class="fas fa-copy copy-btn text-neutral-400 hover:text-primary" onclick="copyToClipboardText('DJIMMY-PANDA')"></i>
                            </div>
                        </div>
                    </div>
                    <div class="credential-box">
                        <h3 class="font-semibold mb-3 flex items-center gap-2">
                            <i class="fas fa-truck text-primary"></i> Panda Truck
                        </h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between items-center p-2 bg-neutral-800 rounded">
                                <span class="text-neutral-400">URL:</span>
                                <span class="font-mono">https://pandatruckreloaded.com</span>
                                <i class="fas fa-copy copy-btn text-neutral-400 hover:text-primary" onclick="copyToClipboardText('https://pandatruckreloaded.com')"></i>
                            </div>
                            <div class="flex justify-between items-center p-2 bg-neutral-800 rounded">
                                <span class="text-neutral-400">Usuario:</span>
                                <span class="font-mono">djimmypanda</span>
                                <i class="fas fa-copy copy-btn text-neutral-400 hover:text-primary" onclick="copyToClipboardText('djimmypanda')"></i>
                            </div>
                            <div class="flex justify-between items-center p-2 bg-neutral-800 rounded">
                                <span class="text-neutral-400">Contraseña:</span>
                                <span class="font-mono">Solicitar al administrador</span>
                                <i class="fas fa-copy copy-btn text-neutral-400 hover:text-primary" onclick="copyToClipboardText('Solicitar al administrador')"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <p class="text-xs text-neutral-500 mt-4 text-center">
                    <i class="fas fa-shield-alt"></i> Estas credenciales son personales. No las compartas con nadie.
                </p>
            </div>
        </section>

        <!-- Cyberduck Instalación -->
        <section class="mb-12">
            <div class="guide-card rounded-2xl p-6">
                <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                    <i class="fas fa-download text-primary"></i> 1. Instalar Cyberduck
                </h2>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-neutral-300 mb-4">Cyberduck es una aplicación gratuita para subir archivos a Backblaze.</p>
                        <ol class="list-decimal list-inside space-y-2 text-neutral-300">
                            <li>Ve a <a href="https://cyberduck.io" target="_blank" class="text-primary hover:underline">https://cyberduck.io</a></li>
                            <li>Haz clic en <strong>"Download"</strong></li>
                            <li>Elige tu sistema operativo (Windows/Mac)</li>
                            <li>Instala la aplicación como cualquier otro programa</li>
                        </ol>
                    </div>
                    <div class="bg-neutral-800/50 rounded-lg p-4 text-center">
                        <i class="fas fa-cloud-upload-alt text-5xl text-primary mb-2"></i>
                        <p class="text-sm text-neutral-400">Cyberduck es <strong class="text-green-400">gratuito</strong> y fácil de usar</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Configurar Cyberduck -->
        <section class="mb-12">
            <div class="guide-card rounded-2xl p-6">
                <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                    <i class="fas fa-plug text-primary"></i> 2. Configurar Cyberduck
                </h2>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <ol class="list-decimal list-inside space-y-2 text-neutral-300">
                            <li>Abre Cyberduck</li>
                            <li>Haz clic en <strong>"Nueva Conexión"</strong> (Ctrl+N)</li>
                            <li>En el desplegable, selecciona <strong>"Backblaze B2"</strong></li>
                            <li>Ingresa tus credenciales:
                                <ul class="list-disc list-inside ml-6 mt-2 text-sm">
                                    <li>KeyID: <code class="bg-neutral-800 px-1 rounded">005205be2fa8c250000000003</code></li>
                                    <li>ApplicationKey: <code class="bg-neutral-800 px-1 rounded">K005o97SP8IJ0Uac0PJZZjTPSs1DuCc</code></li>
                                </ul>
                            </li>
                            <li>Haz clic en <strong>"Conectar"</strong></li>
                        </ol>
                    </div>
                    <div class="bg-neutral-800/50 rounded-lg p-4">
                        <p class="text-sm text-green-400 mb-2">✅ Conexión exitosa si ves:</p>
                        <p class="text-neutral-300">El bucket <strong class="text-primary">DJIMMY-PANDA</strong> aparecerá en la ventana</p>
                        <div class="mt-3 code-block text-xs">
                            📁 DJIMMY-PANDA/
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Crear Carpetas -->
        <section class="mb-12">
            <div class="guide-card rounded-2xl p-6">
                <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                    <i class="fas fa-folder-plus text-primary"></i> 3. Crear Carpetas
                </h2>
                <p class="text-neutral-300 mb-4">Organiza tu contenido creando estas carpetas:</p>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                    <div class="bg-neutral-800/50 rounded-lg p-3 text-center">
                        <i class="fas fa-music text-2xl text-primary mb-1"></i>
                        <p class="font-mono text-sm">mixes/</p>
                        <p class="text-xs text-neutral-500">Para archivos MP3</p>
                    </div>
                    <div class="bg-neutral-800/50 rounded-lg p-3 text-center">
                        <i class="fas fa-video text-2xl text-primary mb-1"></i>
                        <p class="font-mono text-sm">videos/</p>
                        <p class="text-xs text-neutral-500">Para archivos MP4</p>
                    </div>
                    <div class="bg-neutral-800/50 rounded-lg p-3 text-center">
                        <i class="fas fa-compact-disc text-2xl text-primary mb-1"></i>
                        <p class="font-mono text-sm">albumes/</p>
                        <p class="text-xs text-neutral-500">Para archivos ZIP</p>
                    </div>
                    <div class="bg-neutral-800/50 rounded-lg p-3 text-center">
                        <i class="fas fa-image text-2xl text-primary mb-1"></i>
                        <p class="font-mono text-sm">portadas/</p>
                        <p class="text-xs text-neutral-500">Para imágenes JPG/PNG</p>
                    </div>
                </div>
                <div class="code-block">
                    💡 Para crear una carpeta: Haz clic derecho → "Nueva Carpeta" → escribe el nombre con barra al final (ej: mixes/)
                </div>
            </div>
        </section>

        <!-- Subir Archivos -->
        <section class="mb-12">
            <div class="guide-card rounded-2xl p-6">
                <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                    <i class="fas fa-upload text-primary"></i> 4. Subir Archivos
                </h2>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <ol class="list-decimal list-inside space-y-2 text-neutral-300">
                            <li>Abre la carpeta donde quieres subir (ej: mixes/)</li>
                            <li>Arrastra el archivo desde tu computadora</li>
                            <li>Espera a que termine la subida (verás la barra de progreso)</li>
                            <li>✅ ¡Listo! El archivo está en la nube</li>
                        </ol>
                    </div>
                    <div class="bg-neutral-800/50 rounded-lg p-4">
                        <p class="text-sm text-yellow-400 mb-2">⚠️ Consejos importantes:</p>
                        <ul class="text-xs text-neutral-300 space-y-1 list-disc list-inside">
                            <li>Usa nombres sin espacios: <code>activadera_total.mp3</code></li>
                            <li>Las portadas: 500x500 o 1000x1000 píxeles</li>
                            <li>Los ZIP pueden ser grandes, ten paciencia</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Obtener URL -->
        <section class="mb-12">
            <div class="guide-card rounded-2xl p-6">
                <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                    <i class="fas fa-link text-primary"></i> 5. Obtener la URL
                </h2>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <p class="text-neutral-300 mb-3">Para copiar el enlace de un archivo:</p>
                        <ol class="list-decimal list-inside space-y-2 text-neutral-300">
                            <li>Haz clic derecho sobre el archivo</li>
                            <li>Selecciona <strong>"Copiar URL"</strong> o <strong>"Copy URL"</strong></li>
                            <li>La URL se copia automáticamente</li>
                        </ol>
                        <div class="mt-4 code-block">
                            📋 Ejemplo de URL:<br>
                            https://f005.backblazeb2.com/file/DJIMMY-PANDA/mixes/mi_mix.mp3
                        </div>
                    </div>
                    <div class="bg-neutral-800/50 rounded-lg p-4">
                        <p class="text-sm text-primary mb-2">📋 Estructura de la URL:</p>
                        <p class="text-xs font-mono break-all">
                            https://f005.backblazeb2.com/file/<span class="text-primary">[BUCKET]</span>/<span class="text-green-400">[CARPETA]/[ARCHIVO]</span>
                        </p>
                        <p class="text-xs text-neutral-500 mt-3">La URL es pública, cualquiera con el enlace puede descargar.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Dashboard - Agregar Mix -->
        <section class="mb-12">
            <div class="guide-card rounded-2xl p-6">
                <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                    <i class="fas fa-music text-primary"></i> 6. Agregar Mix en el Dashboard
                </h2>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <ol class="list-decimal list-inside space-y-2 text-neutral-300">
                            <li>Ve a <a href="dashboard.php" class="text-primary hover:underline">https://pandatruckreloaded.com/dashboard.php</a></li>
                            <li>Inicia sesión con tus credenciales</li>
                            <li>En el menú lateral, haz clic en <strong>"Mixes"</strong></li>
                            <li>Haz clic en <strong>"Agregar Mix"</strong></li>
                            <li>Completa el formulario:
                                <ul class="list-disc list-inside ml-6 mt-2 text-sm">
                                    <li><strong>Título:</strong> Nombre del mix</li>
                                    <li><strong>DJ:</strong> Nombre del DJ</li>
                                    <li><strong>Género:</strong> Estilo musical</li>
                                    <li><strong>URL del Audio:</strong> Pega la URL de Cyberduck</li>
                                    <li><strong>URL de Portada:</strong> Pega la URL de la portada</li>
                                    <li><strong>Duración:</strong> Ej: 1:30:00</li>
                                    <li><strong>Tamaño MB:</strong> Tamaño del archivo</li>
                                </ul>
                            </li>
                            <li>Haz clic en <strong>"Guardar"</strong></li>
                        </ol>
                    </div>
                    <div class="bg-neutral-800/50 rounded-lg p-4">
                        <p class="text-sm text-green-400 mb-2">✅ Verificación:</p>
                        <ul class="text-xs text-neutral-300 space-y-1 list-disc list-inside">
                            <li>El mix debe aparecer en la tabla</li>
                            <li>Haz clic en ▶️ para probar que suena</li>
                            <li>Haz clic en ⬇️ para probar la descarga</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Dashboard - Agregar Video -->
        <section class="mb-12">
            <div class="guide-card rounded-2xl p-6">
                <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                    <i class="fas fa-video text-primary"></i> 7. Agregar Video en el Dashboard
                </h2>
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <ol class="list-decimal list-inside space-y-2 text-neutral-300">
                            <li>En el menú lateral, haz clic en <strong>"Videos"</strong></li>
                            <li>Haz clic en <strong>"Agregar Video"</strong></li>
                            <li>Completa el formulario:
                                <ul class="list-disc list-inside ml-6 mt-2 text-sm">
                                    <li><strong>Título:</strong> Nombre del video</li>
                                    <li><strong>DJ:</strong> Nombre del DJ</li>
                                    <li><strong>Tipo:</strong> MP4 o YouTube</li>
                                    <li><strong>URL del Video:</strong> Pega la URL</li>
                                    <li><strong>Miniatura:</strong> URL de la portada</li>
                                </ul>
                            </li>
                            <li>Haz clic en <strong>"Guardar"</strong></li>
                        </ol>
                    </div>
                    <div class="bg-neutral-800/50 rounded-lg p-4">
                        <p class="text-sm text-yellow-400 mb-2">📹 Para YouTube:</p>
                        <p class="text-xs text-neutral-300">Solo necesitas la URL completa, el sistema extrae el ID automáticamente.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Dashboard - Agregar Álbum -->
        <section class="mb-12">
            <div class="guide-card rounded-2xl p-6">
                <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                    <i class="fas fa-compact-disc text-primary"></i> 8. Agregar Álbum en el Dashboard
                </h2>
                <ol class="list-decimal list-inside space-y-2 text-neutral-300">
                    <li>En el menú lateral, haz clic en <strong>"Álbumes"</strong></li>
                    <li>Haz clic en <strong>"Nuevo Álbum"</strong></li>
                    <li>Completa el formulario:
                        <ul class="list-disc list-inside ml-6 mt-2 text-sm">
                            <li><strong>Título del Álbum:</strong> Nombre del disco</li>
                            <li><strong>Artista:</strong> Nombre del artista/DJ</li>
                            <li><strong>Género:</strong> Estilo musical</li>
                            <li><strong>Año:</strong> Año de lanzamiento</li>
                            <li><strong>URL de la Portada:</strong> URL de la imagen</li>
                            <li><strong>URL del ZIP:</strong> URL del archivo ZIP</li>
                            <li><strong>Descripción:</strong> Información del álbum</li>
                        </ul>
                    </li>
                    <li>Haz clic en <strong>"Guardar"</strong></li>
                </ol>
                <div class="mt-4 bg-neutral-800/50 rounded-lg p-3">
                    <p class="text-sm text-primary">💡 Para agregar canciones al álbum:</p>
                    <p class="text-xs text-neutral-300 mt-1">Después de crear el álbum, haz clic en <strong>"Gestionar Canciones"</strong> para agregar cada tema con su número de pista y URL del MP3.</p>
                </div>
            </div>
        </section>

        <!-- Consejos Finales -->
        <section class="mb-12">
            <div class="guide-card rounded-2xl p-6 bg-gradient-to-r from-primary/10 to-transparent">
                <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                    <i class="fas fa-lightbulb text-primary"></i> Consejos Importantes
                </h2>
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-check-circle text-green-500 mt-1"></i>
                        <div>
                            <p class="font-semibold">Verifica siempre la URL</p>
                            <p class="text-sm text-neutral-400">Pégala en el navegador para confirmar que funciona antes de guardar en el dashboard.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <i class="fas fa-check-circle text-green-500 mt-1"></i>
                        <div>
                            <p class="font-semibold">Nombres de archivos</p>
                            <p class="text-sm text-neutral-400">Usa solo letras, números y guiones bajos. Evita espacios y caracteres especiales.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <i class="fas fa-check-circle text-green-500 mt-1"></i>
                        <div>
                            <p class="font-semibold">Portadas</p>
                            <p class="text-sm text-neutral-400">Tamaño recomendado: 500x500 o 1000x1000 píxeles. Formato JPG o PNG.</p>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <i class="fas fa-check-circle text-green-500 mt-1"></i>
                        <div>
                            <p class="font-semibold">Backblaze es público</p>
                            <p class="text-sm text-neutral-400">Cualquiera con la URL puede descargar. No subas información privada.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="text-center text-neutral-500 text-sm py-8 border-t border-neutral-800">
            <p>🐼 Panda Truck Reloaded - La casa de los DJs en Panamá</p>
            <p class="mt-2">¿Problemas? Contacta al administrador: <a href="https://wa.me/50765553370" class="text-primary hover:underline">+507 6555-3370</a></p>
        </footer>
    </main>

    <div id="toast" class="toast"></div>

    <script>
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.innerText;
            navigator.clipboard.writeText(text).then(() => {
                showToast('✅ Copiado: ' + text);
            }).catch(() => {
                showToast('❌ Error al copiar', true);
            });
        }

        function copyToClipboardText(text) {
            navigator.clipboard.writeText(text).then(() => {
                showToast('✅ Copiado: ' + text);
            }).catch(() => {
                showToast('❌ Error al copiar', true);
            });
        }

        function showToast(message, isError = false) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.style.background = isError ? '#dc2626' : '#10b981';
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 3000);
        }
    </script>
</body>
</html>
