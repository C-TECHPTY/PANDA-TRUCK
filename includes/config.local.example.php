<?php
// Copy this file to includes/config.local.php and fill the values for
// your local or production server. The local file is ignored by Git.
return [
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'nombre_base',
    'DB_USER' => 'usuario_base',
    'DB_PASS' => 'clave_base',
    'BASE_URL' => 'https://tudominio.com/',
    'CDN_BASE_URL' => 'https://panda-truck.b-cdn.net/',
    'BACKBLAZE_AUDIO_ORIGIN' => 'https://f005.backblazeb2.com/file/',
    // Cambiar a false solo como respaldo si BunnyCDN no tiene credito.
    // El audio se servira directo desde Backblaze sin usar RAM del hosting.
    'CDN_AUDIO_ENABLED' => true,
    'SMTP_HOST' => '',
    'SMTP_USER' => '',
    'SMTP_PASS' => '',
    'SMTP_PORT' => 587,
    'SMTP_SECURE' => 'tls',
];
?>
