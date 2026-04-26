<?php
// cron/check_subscriptions.php
require_once __DIR__ . '/../includes/config.php';

$db = getDB();
$isCli = PHP_SAPI === 'cli';

function sendSubscriptionMail($to, $subject, $body) {
    if (!$to) {
        return false;
    }

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'From: Panda Truck Reloaded <no-reply@pandatruckreloaded.com>',
    ];

    return @mail($to, $subject, $body, implode("\r\n", $headers));
}

function noticeBody($name, $endDate, $expired = false) {
    $date = date('d/m/Y', strtotime($endDate));

    if ($expired) {
        return "Hola {$name},\n\nTu plan DJ PRO vencio el {$date}.\n\nTu perfil ahora esta en modo basico. Para recuperar todos los beneficios PRO, realiza el pago mensual de $10 por Yappy y envia el comprobante.\n\nGracias,\nPanda Truck Reloaded";
    }

    return "Hola {$name},\n\nTu plan DJ PRO en Panda Truck vence el {$date}.\n\nPara mantener activo tu perfil profesional, todos tus mixes visibles, estadisticas, Instagram y mayor visibilidad en la plataforma, realiza el pago de $10 por Yappy y envia el comprobante.\n\nSi no se renueva antes de la fecha de vencimiento, tu perfil pasara automaticamente a modo basico.\n\nGracias,\nPanda Truck Reloaded";
}

$stmt = $db->query("SELECT * FROM djs WHERE plan = 'pro' AND subscription_end IS NOT NULL AND active = 1");
$djs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$sent = 0;
$updated = 0;

foreach ($djs as $dj) {
    $days = (int)floor((strtotime(date('Y-m-d', strtotime($dj['subscription_end']))) - strtotime(date('Y-m-d'))) / 86400);
    $email = $dj['email'] ?? '';
    $name = $dj['name'];

    $noticeField = null;
    $subject = 'Tu plan DJ PRO esta por vencer';
    $expired = false;

    if ($days === 7 && empty($dj['last_notice_7_days'])) {
        $noticeField = 'last_notice_7_days';
    } elseif ($days === 3 && empty($dj['last_notice_3_days'])) {
        $noticeField = 'last_notice_3_days';
    } elseif ($days === 1 && empty($dj['last_notice_1_day'])) {
        $noticeField = 'last_notice_1_day';
    } elseif ($days <= 0 && empty($dj['last_notice_expired'])) {
        $noticeField = 'last_notice_expired';
        $subject = 'Tu plan DJ PRO ha vencido';
        $expired = true;
    }

    if ($noticeField) {
        if (sendSubscriptionMail($email, $subject, noticeBody($name, $dj['subscription_end'], $expired))) {
            $sent++;
        }

        $sql = "UPDATE djs SET {$noticeField} = NOW()" . ($expired ? ", subscription_status = 'expired'" : '') . " WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $dj['id']]);
        $updated++;
    }

    if ($days < 0 && $dj['subscription_status'] === 'active') {
        $stmt = $db->prepare("UPDATE djs SET subscription_status = 'expired' WHERE id = :id");
        $stmt->execute([':id' => $dj['id']]);
        $updated++;
    }
}

$message = "Cron DJ PRO completado. Correos intentados: {$sent}. DJs actualizados: {$updated}.";

if ($isCli) {
    echo $message . PHP_EOL;
} else {
    header('Content-Type: text/plain; charset=UTF-8');
    echo $message;
}
?>
