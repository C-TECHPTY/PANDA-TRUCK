<?php
// includes/notifications.php - Notificaciones por correo para contenido nuevo.

if (!function_exists('getDB')) {
    require_once __DIR__ . '/config.php';
}

function notification_ensure_log_table(PDO $db)
{
    $db->exec("
        CREATE TABLE IF NOT EXISTS email_notifications_log (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            type ENUM('mix','video','dj') NOT NULL,
            related_id INT NOT NULL,
            recipient_email VARCHAR(190) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            status ENUM('sent','failed') NOT NULL,
            error_message TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_email_notifications_lookup (type, related_id, status),
            KEY idx_email_notifications_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function notification_bool($value, bool $default = true): bool
{
    if ($value === null || $value === '') {
        return $default;
    }

    return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
}

function notification_settings(PDO $db)
{
    $settings = [
        'admin_notify_email' => defined('ADMIN_NOTIFY_EMAIL') ? ADMIN_NOTIFY_EMAIL : '',
        'notifications_enabled' => defined('NOTIFICATIONS_ENABLED') ? NOTIFICATIONS_ENABLED : true,
        'notify_new_mixes' => true,
        'notify_new_videos' => true,
        'notify_new_djs' => true,
    ];

    try {
        $stmt = $db->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('admin_notify_email','notifications_enabled','notify_new_mixes','notify_new_videos','notify_new_djs')");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Throwable $e) {
        // Keep config defaults if settings table is unavailable.
    }

    $settings['notifications_enabled'] = notification_bool($settings['notifications_enabled'], true);
    $settings['notify_new_mixes'] = notification_bool($settings['notify_new_mixes'], true);
    $settings['notify_new_videos'] = notification_bool($settings['notify_new_videos'], true);
    $settings['notify_new_djs'] = notification_bool($settings['notify_new_djs'], true);
    $settings['admin_notify_email'] = trim((string)$settings['admin_notify_email']);
    if ($settings['admin_notify_email'] === '' && defined('ADMIN_NOTIFY_EMAIL')) {
        $settings['admin_notify_email'] = trim((string)ADMIN_NOTIFY_EMAIL);
    }

    return $settings;
}

function notification_already_sent(PDO $db, string $type, int $relatedId): bool
{
    $stmt = $db->prepare("SELECT id FROM email_notifications_log WHERE type = :type AND related_id = :related_id AND status = 'sent' LIMIT 1");
    $stmt->execute([
        ':type' => $type,
        ':related_id' => $relatedId,
    ]);

    return (bool)$stmt->fetchColumn();
}

function notification_log(PDO $db, $type, $relatedId, $recipient, $subject, $status, $error = '')
{
    notification_ensure_log_table($db);

    $stmt = $db->prepare("
        INSERT INTO email_notifications_log (type, related_id, recipient_email, subject, status, error_message)
        VALUES (:type, :related_id, :recipient_email, :subject, :status, :error_message)
    ");
    $stmt->execute([
        ':type' => $type,
        ':related_id' => $relatedId,
        ':recipient_email' => $recipient,
        ':subject' => $subject,
        ':status' => $status,
        ':error_message' => $error,
    ]);
}

function notification_site_url($path = '')
{
    $base = defined('SITE_URL') ? SITE_URL : (defined('BASE_URL') ? BASE_URL : '/');
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function notification_public_image_url($path)
{
    $path = trim((string)$path);
    if ($path === '') {
        return notification_site_url('assets/img/logo.png');
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    if (function_exists('cdn_audio_url') && (strpos($path, 'mixes-mp3/') === 0 || strpos($path, 'DJIMMY-PANDA/') === 0)) {
        return cdn_audio_url($path);
    }

    return notification_site_url($path);
}

function notification_escape($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function notification_button_html($label, $url)
{
    $safeLabel = notification_escape($label);
    $safeUrl = notification_escape($url);
    return '<a href="' . $safeUrl . '" style="display:inline-block;background:#e1261d;color:#ffffff;text-decoration:none;font-weight:700;padding:13px 22px;border-radius:8px;margin:18px 0 8px;">' . $safeLabel . '</a>';
}

function notification_html_template(array $data)
{
    $logo = notification_site_url('assets/img/logo.png');
    $title = notification_escape($data['title'] ?? 'Panda Truck Reloaded');
    $subtitle = notification_escape($data['subtitle'] ?? '');
    $description = nl2br(notification_escape($data['description'] ?? ''));
    $image = notification_escape($data['image'] ?? $logo);
    $button = notification_button_html($data['button_label'] ?? 'Ver ahora', $data['url'] ?? notification_site_url());
    $url = notification_escape($data['url'] ?? notification_site_url());

    return '<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>' . $title . '</title>
</head>
<body style="margin:0;background:#111111;font-family:Arial,Helvetica,sans-serif;color:#f5f5f5;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#111111;padding:24px 12px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#1b1b1b;border:1px solid #2f2f2f;border-radius:14px;overflow:hidden;">
          <tr>
            <td align="center" style="padding:24px 20px 12px;">
              <img src="' . notification_escape($logo) . '" alt="Panda Truck Reloaded" width="170" style="display:block;max-width:170px;width:100%;height:auto;">
            </td>
          </tr>
          <tr>
            <td>
              <img src="' . $image . '" alt="' . $title . '" width="620" style="display:block;width:100%;max-height:340px;object-fit:cover;background:#0f0f0f;">
            </td>
          </tr>
          <tr>
            <td style="padding:24px 24px 30px;">
              <p style="margin:0 0 8px;color:#e1261d;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;">' . $subtitle . '</p>
              <h1 style="margin:0 0 14px;color:#ffffff;font-size:28px;line-height:1.2;">' . $title . '</h1>
              <div style="color:#d7d7d7;font-size:16px;line-height:1.55;">' . $description . '</div>
              ' . $button . '
              <p style="margin:14px 0 0;color:#9ca3af;font-size:12px;line-height:1.5;">Enlace directo:<br><a href="' . $url . '" style="color:#ff5a52;word-break:break-all;">' . $url . '</a></p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';
}

function notification_send_mail($to, $subject, $html, &$error = null)
{
    $error = null;

    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoload)) {
        require_once $autoload;
    }

    if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer') && defined('SMTP_HOST') && SMTP_HOST !== '') {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = SMTP_USER !== '';
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->Port = SMTP_PORT;
            if (SMTP_SECURE !== '') {
                $mail->SMTPSecure = SMTP_SECURE;
            }
            $from = SMTP_USER !== '' ? SMTP_USER : $to;
            $mail->setFrom($from, 'Panda Truck Reloaded');
            $mail->addAddress($to);
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body = $html;
            $mail->AltBody = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)));
            $mail->send();
            return true;
        } catch (Throwable $e) {
            $error = $e->getMessage();
            return false;
        }
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: Panda Truck Reloaded <no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'pandatruckreloaded.com') . '>',
    ];

    $sent = @mail($to, $subject, $html, implode("\r\n", $headers));
    if (!$sent) {
        $error = 'mail() no pudo entregar el mensaje. Configura SMTP/PHPMailer en el hosting.';
    }

    return $sent;
}

function notification_dispatch(string $type, int $relatedId, string $subject, array $templateData, bool $force = false): bool
{
    $db = getDB();
    notification_ensure_log_table($db);

    $settings = notification_settings($db);
    $typeSetting = [
        'mix' => 'notify_new_mixes',
        'video' => 'notify_new_videos',
        'dj' => 'notify_new_djs',
    ][$type] ?? '';

    $recipient = $settings['admin_notify_email'];

    if (!$settings['notifications_enabled'] || ($typeSetting && !$settings[$typeSetting])) {
        return false;
    }

    if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
        notification_log($db, $type, $relatedId, $recipient, $subject, 'failed', 'ADMIN_NOTIFY_EMAIL no esta configurado o no es valido.');
        return false;
    }

    if (!$force && notification_already_sent($db, $type, $relatedId)) {
        return false;
    }

    $html = notification_html_template($templateData);
    $error = null;
    $sent = notification_send_mail($recipient, $subject, $html, $error);
    notification_log($db, $type, $relatedId, $recipient, $subject, $sent ? 'sent' : 'failed', $error ?? '');

    return $sent;
}

function send_new_mix_notification($mix_id, bool $force = false): bool
{
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, title, dj, genre, cover FROM mixes WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => (int)$mix_id]);
        $mix = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$mix) {
            return false;
        }

        $url = notification_site_url('player/index.php?id=' . (int)$mix['id']);
        $subject = 'Nuevo mix publicado: ' . $mix['title'];
        return notification_dispatch('mix', (int)$mix['id'], $subject, [
            'subtitle' => 'Nuevo mix disponible',
            'title' => $mix['title'],
            'description' => 'DJ: ' . ($mix['dj'] ?: 'Panda Truck') . "\nGenero: " . ($mix['genre'] ?: 'Variado'),
            'image' => notification_public_image_url($mix['cover'] ?? ''),
            'button_label' => 'Escuchar mix',
            'url' => $url,
        ], $force);
    } catch (Throwable $e) {
        return false;
    }
}

function send_new_video_notification($video_id, bool $force = false): bool
{
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, title, dj, cover FROM videos WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => (int)$video_id]);
        $video = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$video) {
            return false;
        }

        $url = notification_site_url('player/video.php?id=' . (int)$video['id']);
        $subject = 'Nuevo video publicado: ' . $video['title'];
        return notification_dispatch('video', (int)$video['id'], $subject, [
            'subtitle' => 'Nuevo video disponible',
            'title' => $video['title'],
            'description' => 'Ya esta disponible en Panda Truck Reloaded.' . ($video['dj'] ? "\nDJ: " . $video['dj'] : ''),
            'image' => notification_public_image_url($video['cover'] ?? ''),
            'button_label' => 'Ver video',
            'url' => $url,
        ], $force);
    } catch (Throwable $e) {
        return false;
    }
}

function send_new_dj_notification($dj_id, bool $force = false): bool
{
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, name, slug, instagram, avatar, profile_photo, bio FROM djs WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => (int)$dj_id]);
        $dj = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$dj) {
            return false;
        }

        $profileKey = trim((string)($dj['slug'] ?? ''));
        $url = notification_site_url('dj.php?slug=' . rawurlencode($profileKey !== '' ? $profileKey : (string)$dj['id']));
        $instagram = trim((string)($dj['instagram'] ?? ''));
        $subject = 'Nuevo DJ publicado: ' . $dj['name'];
        return notification_dispatch('dj', (int)$dj['id'], $subject, [
            'subtitle' => 'Nuevo perfil DJ',
            'title' => $dj['name'],
            'description' => trim(($dj['bio'] ?: 'Nuevo perfil disponible en Panda Truck Reloaded.') . ($instagram ? "\nInstagram: " . $instagram : '')),
            'image' => notification_public_image_url($dj['profile_photo'] ?: ($dj['avatar'] ?? '')),
            'button_label' => 'Ver perfil DJ',
            'url' => $url,
        ], $force);
    } catch (Throwable $e) {
        return false;
    }
}
?>
