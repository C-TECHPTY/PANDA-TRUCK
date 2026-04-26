<?php
require_once 'includes/config.php';
require_once 'includes/track_visit.php';

trackVisit('dj_pro');

$db = getDB();
$settings = [];
$stmt = $db->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('dj_pro_whatsapp', 'dj_pro_price')");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$whatsapp = $settings['dj_pro_whatsapp'] ?? GUIA_WHATSAPP;
$price = $settings['dj_pro_price'] ?? '10.00';
$message = rawurlencode('Hola, quiero activar mi perfil DJ PRO en Panda Truck.');
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
    <main class="max-w-5xl mx-auto px-4 py-10">
        <nav class="mb-10 flex justify-between">
            <a href="index.php" class="text-neutral-300 hover:text-red-500">Panda Truck Reloaded</a>
            <a href="mixes.php" class="text-neutral-400 hover:text-white">Mixes</a>
        </nav>

        <section class="grid md:grid-cols-[1.2fr_.8fr] gap-8 items-center">
            <div>
                <p class="text-red-500 font-semibold mb-2">DJ PRO</p>
                <h1 class="text-4xl md:text-5xl font-extrabold leading-tight mb-4">Haz crecer tu nombre como DJ</h1>
                <p class="text-lg text-neutral-300 mb-6">Con DJ PRO tendras tu propio perfil profesional dentro de Panda Truck, donde podras mostrar tus mixes, conectar con tu publico y dirigir visitas a tu Instagram.</p>
                <a href="https://wa.me/<?php echo htmlspecialchars($whatsapp); ?>?text=<?php echo $message; ?>" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 px-6 py-3 rounded-lg font-bold">
                    <i class="fab fa-whatsapp"></i> Quiero ser DJ PRO
                </a>
            </div>
            <div class="bg-neutral-900 border border-neutral-800 rounded-lg p-6">
                <div class="text-sm text-neutral-400">Solo</div>
                <div class="text-5xl font-black mb-1">$<?php echo htmlspecialchars(number_format((float)$price, 2)); ?></div>
                <div class="text-neutral-400 mb-6">mensual por Yappy</div>
                <ul class="space-y-3 text-neutral-200">
                    <li><i class="fas fa-check text-red-500 mr-2"></i> Perfil profesional personalizado</li>
                    <li><i class="fas fa-check text-red-500 mr-2"></i> Todos tus mixes en un solo lugar</li>
                    <li><i class="fas fa-check text-red-500 mr-2"></i> Enlace directo a Instagram</li>
                    <li><i class="fas fa-check text-red-500 mr-2"></i> Estadisticas de visitas y reproducciones</li>
                    <li><i class="fas fa-check text-red-500 mr-2"></i> Mayor visibilidad dentro de la plataforma</li>
                    <li><i class="fas fa-check text-red-500 mr-2"></i> Posibilidad de aparecer destacado</li>
                    <li><i class="fas fa-check text-red-500 mr-2"></i> Link publico para compartir</li>
                    <li><i class="fas fa-check text-red-500 mr-2"></i> Musica rapida gracias a CDN</li>
                </ul>
            </div>
        </section>
    </main>
</body>
</html>
