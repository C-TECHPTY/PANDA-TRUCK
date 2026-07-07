<?php
session_start();
require_once 'includes/config.php';

$db = getDB();

$stmt = $db->query("SELECT config_value FROM configuration WHERE config_key = 'radio_url'");
$radio = $stmt->fetch();
$radio_url = $radio['config_value'] ?? 'https://stream.zeno.fm/vjsa6jiwafavv';

$stmt = $db->query("SELECT config_value FROM configuration WHERE config_key = 'radio_name'");
$radio_name_row = $stmt->fetch();
$radio_name = $radio_name_row['config_value'] ?? 'Panda Truck Radio';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Sala de Chat - <?php echo htmlspecialchars(SITE_TITLE); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/chat.css?v=20260707-room-info-toggle">
</head>
<body class="chat-room-page">
    <main class="chat-room-shell" data-chat-room data-api-base="api/chat">
        <header class="chat-room-header">
            <a href="index.php#radio" class="chat-room-back" aria-label="Volver a la radio">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1>Sala en vivo</h1>
                <p>Saluda, pide tu complacencia y escucha la radio.</p>
            </div>
        </header>

        <section class="chat-room-radio">
            <div class="chat-room-radio-info">
                <div class="chat-room-radio-icon"><i class="fas fa-broadcast-tower"></i></div>
                <div>
                    <strong><?php echo htmlspecialchars($radio_name); ?></strong>
                    <span id="chatRoomRadioStatus">Detenido</span>
                </div>
            </div>
            <div class="chat-room-radio-controls">
                <button type="button" id="chatRoomPlay"><i class="fas fa-play"></i></button>
                <button type="button" id="chatRoomStop"><i class="fas fa-stop"></i></button>
                <input type="range" id="chatRoomVolume" min="0" max="100" value="70">
            </div>
        </section>

        <section class="chat-room-card">
            <div class="live-chat-pinned hidden" data-room-pinned></div>
            <div class="live-chat-live chat-room-live" data-room-live>
                <div>
                    <span class="live-chat-live-kicker">EN VIVO</span>
                    <strong data-room-live-title>Ahora en vivo</strong>
                    <small data-room-live-subtitle>Panda Truck Reloaded</small>
                </div>
                <span class="live-chat-listeners" data-room-listeners>0 oyentes</span>
            </div>
            <div class="chat-room-rules" data-room-rules>Respeta a los DJs y a los oyentes.</div>
            <div class="live-chat-poll hidden" data-room-poll></div>

            <div class="live-chat-identity chat-room-identity">
                <label class="live-chat-name-field">
                    <span>Tu nombre para participar</span>
                    <input type="text" data-room-name maxlength="40" placeholder="Ejemplo: Carlos507">
                </label>
                <button type="button" class="live-chat-save-name" data-room-save-name aria-label="Guardar nombre">
                    <i class="fas fa-check"></i>
                </button>
            </div>

            <div class="live-chat-messages chat-room-messages" data-room-messages>
                <div class="live-chat-empty">Todavia no hay mensajes. Se el primero en mandar un saludo.</div>
            </div>

            <div class="live-chat-composer chat-room-composer">
                <div class="live-chat-quick-emojis" data-room-emojis aria-label="Emojis rapidos"></div>
                <form class="live-chat-form" data-room-form>
                    <input type="text" data-room-input maxlength="500" autocomplete="off" placeholder="Escribe tu saludo o complacencia...">
                    <button type="submit" aria-label="Enviar mensaje"><i class="fas fa-paper-plane"></i></button>
                </form>
                <div class="chat-room-reactions" data-room-reactions></div>
            </div>
        </section>
    </main>

    <script>
        window.PANDA_CHAT_ROOM_RADIO_URL = <?php echo json_encode($radio_url); ?>;
    </script>
    <script src="assets/js/chat-room.js?v=20260707-room-info-toggle"></script>
</body>
</html>
