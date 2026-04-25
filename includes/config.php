<?php
// includes/config.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$localConfigFile = __DIR__ . '/config.local.php';
$localConfig = file_exists($localConfigFile) ? require $localConfigFile : [];

$configValue = function ($key, $default) use ($localConfig) {
    if (array_key_exists($key, $localConfig)) {
        return $localConfig[$key];
    }

    $value = getenv($key);
    return $value !== false ? $value : $default;
};

// Database configuration. Override these values with environment variables
// or with includes/config.local.php in each server.
define('DB_HOST', $configValue('DB_HOST', 'localhost'));
define('DB_NAME', $configValue('DB_NAME', 'panda_truck_v2'));
define('DB_USER', $configValue('DB_USER', 'root'));
define('DB_PASS', $configValue('DB_PASS', ''));

// Site configuration.
define('BASE_URL', $configValue('BASE_URL', 'https://pandatruckreloaded.com/'));
define('SITE_TITLE', $configValue('SITE_TITLE', 'Panda Truck Reloaded'));
define('SITE_DESCRIPTION', $configValue('SITE_DESCRIPTION', 'La casa de los DJs en Panama - Descarga los mejores mixes'));
define('SITE_LOGO', $configValue('SITE_LOGO', 'assets/img/logo.png'));
define('FOOTER_TEXT', $configValue('FOOTER_TEXT', 'Panda Truck Reloaded - La casa de los DJs en Panama'));

// DJ guide configuration.
define('GUIA_TITLE', $configValue('GUIA_TITLE', 'Guia para DJs - Panda Truck'));
define('GUIA_DESCRIPTION', $configValue('GUIA_DESCRIPTION', 'Guia completa para DJs que quieren publicar sus mixes'));
define('GUIA_WHATSAPP', $configValue('GUIA_WHATSAPP', '50762115209'));

function getDB() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        die("Error de conexion: " . $e->getMessage());
    }
}
?>
