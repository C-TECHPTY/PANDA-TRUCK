<?php
// Copy this file to includes/config.local.php and fill the values for
// your local or production server. The local file is ignored by Git.
return [
    'DB_HOST' => 'localhost',
    'DB_NAME' => 'pandqgxl_panda_truck_db',
    'DB_USER' => 'pandqgxl_panda_admin',
    'DB_PASS' => 'Xmcb96Nky@8hTtH',
    'BASE_URL' => 'https://pandatruckreloaded.com/',
    'SITE_URL' => 'https://pandatruckreloaded.com/',
    'CDN_BASE_URL' => 'https://panda-truck.b-cdn.net/',
    'ADMIN_NOTIFY_EMAIL' => 'nelsonsanchezdillon@outlook.com',
    'NOTIFICATIONS_ENABLED' => true,
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
