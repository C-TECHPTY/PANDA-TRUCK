<?php
require_once 'includes/config.php';
require_once 'includes/track_visit.php';

trackVisit('dj_pro');

$db = getDB();
$settings = [];
$stmt = $db->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('dj_pro_whatsapp')");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$whatsapp = $settings['dj_pro_whatsapp'] ?? GUIA_WHATSAPP;
$message = rawurlencode('Hola, quiero cotizar un perfil DJ PRO en Panda Truck.');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DJ PRO - Panda Truck Reloaded</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-neutral-950 text-white">
    <main class="max-w-6xl mx-auto px-4 py-10">
        <nav class="mb-10 flex items-center justify-between gap-4">
            <a href="index.php" class="flex items-center gap-3 text-neutral-200 hover:text-red-500 transition">
                <img src="<?php echo htmlspecialchars(SITE_LOGO); ?>" alt="Panda Truck" class="h-10 w-auto" onerror="this.style.display='none'">
                <span class="font-bold">Panda Truck Reloaded</span>
            </a>
            <a href="mixes.php" class="text-neutral-400 hover:text-white transition">Mixes</a>
        </nav>

        <section class="grid gap-8 overflow-hidden rounded-2xl border border-white/10 bg-neutral-900/70 p-6 shadow-2xl md:grid-cols-[1.1fr_.9fr] md:p-8">
            <div class="flex flex-col justify-center">
                <p class="mb-3 inline-flex w-fit items-center gap-2 rounded-full border border-red-500/40 bg-red-500/10 px-3 py-1 text-sm font-bold uppercase text-red-400">
                    <i class="fas fa-headphones"></i> DJ PRO
                </p>
                <h1 class="mb-4 text-4xl font-black leading-tight md:text-6xl">Tu perfil musical dentro de Panda Truck</h1>
                <p class="mb-6 max-w-2xl text-lg leading-8 text-neutral-300">
                    Creamos una presencia profesional para DJs que quieren mostrar sus mixes, conectar con su publico y tener un enlace facil de compartir.
                </p>
                <a href="https://wa.me/<?php echo htmlspecialchars($whatsapp); ?>?text=<?php echo $message; ?>" class="inline-flex w-fit items-center gap-2 rounded-lg bg-red-600 px-6 py-3 font-bold hover:bg-red-700 transition">
                    <i class="fab fa-whatsapp"></i> Cotiza con nosotros
                </a>
            </div>
            <div class="rounded-xl border border-white/10 bg-black/30 p-5">
                <div class="mb-5 rounded-lg bg-neutral-950 p-4">
                    <p class="text-sm uppercase tracking-wide text-neutral-500">Beneficios del perfil</p>
                    <h2 class="mt-1 text-2xl font-black">Presencia, catalogo y contacto directo</h2>
                </div>
                <div class="grid gap-3">
                    <div class="rounded-lg border border-white/10 bg-neutral-950/70 p-4"><i class="fas fa-id-card text-red-500 mr-2"></i> Perfil profesional personalizado</div>
                    <div class="rounded-lg border border-white/10 bg-neutral-950/70 p-4"><i class="fas fa-music text-red-500 mr-2"></i> Tus mixes organizados en un solo lugar</div>
                    <div class="rounded-lg border border-white/10 bg-neutral-950/70 p-4"><i class="fab fa-instagram text-red-500 mr-2"></i> Enlace directo a Instagram</div>
                    <div class="rounded-lg border border-white/10 bg-neutral-950/70 p-4"><i class="fas fa-chart-line text-red-500 mr-2"></i> Estadisticas de visitas y reproducciones</div>
                    <div class="rounded-lg border border-white/10 bg-neutral-950/70 p-4"><i class="fas fa-star text-red-500 mr-2"></i> Mayor visibilidad dentro de la plataforma</div>
                    <div class="rounded-lg border border-white/10 bg-neutral-950/70 p-4"><i class="fas fa-share-nodes text-red-500 mr-2"></i> Link publico para compartir</div>
                    <div class="rounded-lg border border-white/10 bg-neutral-950/70 p-4"><i class="fas fa-bolt text-red-500 mr-2"></i> Musica rapida servida por CDN</div>
                </div>
            </div>
        </section>

        <section class="mt-8 grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-white/10 bg-neutral-900 p-5">
                <i class="fas fa-user-check mb-3 text-2xl text-red-500"></i>
                <h3 class="font-bold">Imagen profesional</h3>
                <p class="mt-2 text-sm leading-6 text-neutral-400">Foto, biografia, genero musical, ciudad y enlaces principales del DJ.</p>
            </div>
            <div class="rounded-xl border border-white/10 bg-neutral-900 p-5">
                <i class="fas fa-compact-disc mb-3 text-2xl text-red-500"></i>
                <h3 class="font-bold">Catalogo musical</h3>
                <p class="mt-2 text-sm leading-6 text-neutral-400">Una pagina clara para mostrar mixes, reproducir y compartir con seguidores.</p>
            </div>
            <div class="rounded-xl border border-white/10 bg-neutral-900 p-5">
                <i class="fas fa-ranking-star mb-3 text-2xl text-red-500"></i>
                <h3 class="font-bold">Mas visibilidad</h3>
                <p class="mt-2 text-sm leading-6 text-neutral-400">Opciones para destacar el perfil dentro de Panda Truck segun disponibilidad.</p>
            </div>
        </section>
    </main>
</body>
</html>
