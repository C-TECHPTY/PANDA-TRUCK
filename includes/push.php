<?php
// includes/push.php - Web Push/PWA helpers.

if (!function_exists('getDB')) {
    require_once __DIR__ . '/config.php';
}

function panda_push_base64url_encode(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function panda_push_base64url_decode(string $data): string
{
    $padding = strlen($data) % 4;
    if ($padding) {
        $data .= str_repeat('=', 4 - $padding);
    }
    return base64_decode(strtr($data, '-_', '+/')) ?: '';
}

function panda_push_ensure_tables(PDO $db): void
{
    $db->exec("
        CREATE TABLE IF NOT EXISTS push_subscriptions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            endpoint TEXT NOT NULL,
            endpoint_hash CHAR(64) NOT NULL,
            p256dh TEXT NOT NULL,
            auth TEXT NOT NULL,
            user_agent TEXT NULL,
            ip_address VARCHAR(64) NULL,
            active TINYINT(1) NOT NULL DEFAULT 1,
            last_error TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uniq_push_endpoint_hash (endpoint_hash),
            KEY idx_push_active (active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS push_notifications_log (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(190) NOT NULL,
            body TEXT NOT NULL,
            target_url TEXT NULL,
            sent_count INT NOT NULL DEFAULT 0,
            failed_count INT NOT NULL DEFAULT 0,
            created_by INT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_push_notifications_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

function panda_push_settings(PDO $db): array
{
    $settings = [
        'push_enabled' => '0',
        'push_vapid_public_key' => '',
        'push_vapid_private_key' => '',
        'push_vapid_subject' => 'mailto:no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'pandatruckreloaded.com'),
    ];

    try {
        $stmt = $db->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('push_enabled','push_vapid_public_key','push_vapid_private_key','push_vapid_subject')");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $settings[$row['setting_key']] = (string)$row['setting_value'];
        }
    } catch (Throwable $e) {
        // Keep defaults.
    }

    return $settings;
}

function panda_push_save_setting(PDO $db, string $key, string $value): void
{
    $stmt = $db->prepare("
        INSERT INTO system_settings (setting_key, setting_value, `group`)
        VALUES (:key, :value, 'push')
        ON DUPLICATE KEY UPDATE setting_value = :value, updated_at = NOW()
    ");
    $stmt->execute([':key' => $key, ':value' => $value]);

    $stmt = $db->prepare("
        INSERT INTO configuration (config_key, config_value)
        VALUES (:key, :value)
        ON DUPLICATE KEY UPDATE config_value = :value
    ");
    $stmt->execute([':key' => $key, ':value' => $value]);
}

function panda_push_public_key_from_pem(string $privatePem): string
{
    $resource = openssl_pkey_get_private($privatePem);
    if (!$resource) {
        throw new RuntimeException('Llave VAPID privada invalida.');
    }

    $details = openssl_pkey_get_details($resource);
    if (empty($details['ec']['x']) || empty($details['ec']['y'])) {
        throw new RuntimeException('OpenSSL no pudo leer la llave publica EC.');
    }

    $x = str_pad($details['ec']['x'], 32, "\x00", STR_PAD_LEFT);
    $y = str_pad($details['ec']['y'], 32, "\x00", STR_PAD_LEFT);

    return panda_push_base64url_encode("\x04" . $x . $y);
}

function panda_push_generate_vapid_keys(PDO $db): array
{
    if (!function_exists('openssl_pkey_new')) {
        throw new RuntimeException('OpenSSL no esta disponible en PHP.');
    }

    $key = openssl_pkey_new([
        'private_key_type' => OPENSSL_KEYTYPE_EC,
        'curve_name' => 'prime256v1',
    ]);

    if (!$key || !openssl_pkey_export($key, $privatePem)) {
        throw new RuntimeException('No se pudieron generar llaves VAPID.');
    }

    $publicKey = panda_push_public_key_from_pem($privatePem);
    panda_push_save_setting($db, 'push_vapid_private_key', $privatePem);
    panda_push_save_setting($db, 'push_vapid_public_key', $publicKey);
    panda_push_save_setting($db, 'push_enabled', '1');

    return ['publicKey' => $publicKey, 'privateKey' => $privatePem];
}

function panda_push_asn1_length(int $length): string
{
    if ($length < 128) {
        return chr($length);
    }

    $bytes = '';
    while ($length > 0) {
        $bytes = chr($length & 0xff) . $bytes;
        $length >>= 8;
    }
    return chr(0x80 | strlen($bytes)) . $bytes;
}

function panda_push_asn1_integer(string $value): string
{
    $value = ltrim($value, "\x00");
    if ($value === '') {
        $value = "\x00";
    }
    if ((ord($value[0]) & 0x80) !== 0) {
        $value = "\x00" . $value;
    }
    return "\x02" . panda_push_asn1_length(strlen($value)) . $value;
}

function panda_push_der_to_raw_signature(string $der): string
{
    $offset = 0;
    if (ord($der[$offset++]) !== 0x30) {
        throw new RuntimeException('Firma VAPID invalida.');
    }
    $len = ord($der[$offset++]);
    if ($len & 0x80) {
        $bytes = $len & 0x7f;
        $len = 0;
        for ($i = 0; $i < $bytes; $i++) {
            $len = ($len << 8) + ord($der[$offset++]);
        }
    }
    if (ord($der[$offset++]) !== 0x02) {
        throw new RuntimeException('Firma VAPID invalida.');
    }
    $rLen = ord($der[$offset++]);
    $r = substr($der, $offset, $rLen);
    $offset += $rLen;
    if (ord($der[$offset++]) !== 0x02) {
        throw new RuntimeException('Firma VAPID invalida.');
    }
    $sLen = ord($der[$offset++]);
    $s = substr($der, $offset, $sLen);

    return str_pad(ltrim($r, "\x00"), 32, "\x00", STR_PAD_LEFT) .
        str_pad(ltrim($s, "\x00"), 32, "\x00", STR_PAD_LEFT);
}

function panda_push_vapid_jwt(string $endpoint, array $settings): string
{
    $parts = parse_url($endpoint);
    $aud = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? '');
    if (!empty($parts['port'])) {
        $aud .= ':' . $parts['port'];
    }

    $header = panda_push_base64url_encode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));
    $payload = panda_push_base64url_encode(json_encode([
        'aud' => $aud,
        'exp' => time() + 43200,
        'sub' => $settings['push_vapid_subject'] ?: 'mailto:no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'pandatruckreloaded.com'),
    ]));
    $unsigned = $header . '.' . $payload;

    $private = openssl_pkey_get_private($settings['push_vapid_private_key']);
    if (!$private || !openssl_sign($unsigned, $signature, $private, OPENSSL_ALGO_SHA256)) {
        throw new RuntimeException('No se pudo firmar el JWT VAPID.');
    }

    return $unsigned . '.' . panda_push_base64url_encode(panda_push_der_to_raw_signature($signature));
}

function panda_push_raw_public_to_pem(string $rawPublicKey): string
{
    if (strlen($rawPublicKey) !== 65 || $rawPublicKey[0] !== "\x04") {
        throw new RuntimeException('p256dh invalido.');
    }

    $der = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200') . $rawPublicKey;
    return "-----BEGIN PUBLIC KEY-----\n" .
        chunk_split(base64_encode($der), 64, "\n") .
        "-----END PUBLIC KEY-----\n";
}

function panda_push_hkdf_extract(string $salt, string $ikm): string
{
    return hash_hmac('sha256', $ikm, $salt, true);
}

function panda_push_hkdf_expand(string $prk, string $info, int $length): string
{
    $last = '';
    $output = '';
    $counter = 1;
    while (strlen($output) < $length) {
        $last = hash_hmac('sha256', $last . $info . chr($counter), $prk, true);
        $output .= $last;
        $counter++;
    }
    return substr($output, 0, $length);
}

function panda_push_encrypt_payload(string $payload, string $userPublicKeyB64, string $authSecretB64): array
{
    $userPublic = panda_push_base64url_decode($userPublicKeyB64);
    $authSecret = panda_push_base64url_decode($authSecretB64);

    $localKey = openssl_pkey_new([
        'private_key_type' => OPENSSL_KEYTYPE_EC,
        'curve_name' => 'prime256v1',
    ]);
    if (!$localKey || !openssl_pkey_export($localKey, $localPrivatePem)) {
        throw new RuntimeException('No se pudo generar llave ECDH.');
    }

    $localDetails = openssl_pkey_get_details($localKey);
    $localPublic = "\x04" . $localDetails['ec']['x'] . $localDetails['ec']['y'];
    $peerPublic = openssl_pkey_get_public(panda_push_raw_public_to_pem($userPublic));
    $sharedSecret = openssl_pkey_derive($peerPublic, openssl_pkey_get_private($localPrivatePem), 32);
    if (!$sharedSecret) {
        throw new RuntimeException('No se pudo derivar secreto ECDH.');
    }

    $salt = random_bytes(16);
    $authPrk = panda_push_hkdf_extract($authSecret, $sharedSecret);
    $keyInfo = "WebPush: info\x00" . $userPublic . $localPublic;
    $ikm = panda_push_hkdf_expand($authPrk, $keyInfo, 32);
    $prk = panda_push_hkdf_extract($salt, $ikm);
    $cek = panda_push_hkdf_expand($prk, "Content-Encoding: aes128gcm\x00", 16);
    $nonce = panda_push_hkdf_expand($prk, "Content-Encoding: nonce\x00", 12);

    $plaintext = $payload . "\x02";
    $tag = '';
    $ciphertext = openssl_encrypt($plaintext, 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag);
    if ($ciphertext === false) {
        throw new RuntimeException('No se pudo cifrar payload push.');
    }

    $body = $salt . pack('N', 4096) . chr(strlen($localPublic)) . $localPublic . $ciphertext . $tag;
    return ['body' => $body, 'publicKey' => panda_push_base64url_encode($localPublic)];
}

function panda_push_send_one(array $subscription, array $payload, array $settings): array
{
    if (empty($settings['push_vapid_private_key']) || empty($settings['push_vapid_public_key'])) {
        throw new RuntimeException('Faltan llaves VAPID.');
    }

    $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $encrypted = panda_push_encrypt_payload($json, $subscription['p256dh'], $subscription['auth']);
    $jwt = panda_push_vapid_jwt($subscription['endpoint'], $settings);

    $headers = [
        'TTL: 86400',
        'Content-Type: application/octet-stream',
        'Content-Encoding: aes128gcm',
        'Authorization: WebPush ' . $jwt,
        'Crypto-Key: p256ecdsa=' . $settings['push_vapid_public_key'],
    ];

    $ch = curl_init($subscription['endpoint']);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_POSTFIELDS => $encrypted['body'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_TIMEOUT => 15,
    ]);
    $response = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return [
        'success' => $status >= 200 && $status < 300,
        'status' => $status,
        'error' => $error ?: ($response ?: ''),
    ];
}

function panda_push_broadcast(array $payload): array
{
    $db = getDB();
    panda_push_ensure_tables($db);
    $settings = panda_push_settings($db);

    if (($settings['push_enabled'] ?? '0') !== '1') {
        return ['success' => false, 'sent' => 0, 'failed' => 0, 'error' => 'Push no esta activado.'];
    }

    $stmt = $db->query("SELECT id, endpoint, p256dh, auth FROM push_subscriptions WHERE active = 1 ORDER BY id DESC");
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $sent = 0;
    $failed = 0;

    foreach ($subscriptions as $subscription) {
        try {
            $result = panda_push_send_one($subscription, $payload, $settings);
            if ($result['success']) {
                $sent++;
                continue;
            }

            $failed++;
            $inactive = in_array((int)$result['status'], [404, 410], true) ? 0 : 1;
            $update = $db->prepare("UPDATE push_subscriptions SET active = :active, last_error = :error WHERE id = :id");
            $update->execute([
                ':active' => $inactive,
                ':error' => substr((string)$result['error'], 0, 1000),
                ':id' => (int)$subscription['id'],
            ]);
        } catch (Throwable $e) {
            $failed++;
            $update = $db->prepare("UPDATE push_subscriptions SET last_error = :error WHERE id = :id");
            $update->execute([
                ':error' => substr($e->getMessage(), 0, 1000),
                ':id' => (int)$subscription['id'],
            ]);
        }
    }

    $log = $db->prepare("
        INSERT INTO push_notifications_log (title, body, target_url, sent_count, failed_count, created_by)
        VALUES (:title, :body, :target_url, :sent_count, :failed_count, :created_by)
    ");
    $log->execute([
        ':title' => (string)($payload['title'] ?? 'Panda Truck Reloaded'),
        ':body' => (string)($payload['body'] ?? ''),
        ':target_url' => (string)($payload['url'] ?? ''),
        ':sent_count' => $sent,
        ':failed_count' => $failed,
        ':created_by' => $_SESSION['user_id'] ?? null,
    ]);

    return ['success' => true, 'sent' => $sent, 'failed' => $failed];
}

function panda_push_new_mix(int $mixId): array
{
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, title, dj, cover FROM mixes WHERE id = :id AND active = 1 LIMIT 1");
        $stmt->execute([':id' => $mixId]);
        $mix = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$mix) {
            return ['success' => false, 'sent' => 0, 'failed' => 0, 'error' => 'Mix no encontrado.'];
        }

        $image = trim((string)($mix['cover'] ?? ''));
        if ($image !== '' && !preg_match('#^https?://#i', $image)) {
            $image = SITE_URL . ltrim(str_replace('../', '', $image), '/');
        }

        return panda_push_broadcast([
            'title' => 'Nuevo mix en la plataforma',
            'body' => ($mix['title'] ?: 'Nuevo mix') . ' - ' . ($mix['dj'] ?: 'Panda Truck'),
            'url' => SITE_URL . 'player/index.php?id=' . (int)$mix['id'],
            'image' => $image ?: SITE_URL . 'assets/img/android-chrome-512x512.png',
            'icon' => '/assets/img/android-chrome-192x192.png',
            'badge' => '/assets/img/favicon-32x32.png',
            'vibrate' => [240, 100, 240],
        ]);
    } catch (Throwable $e) {
        return ['success' => false, 'sent' => 0, 'failed' => 0, 'error' => $e->getMessage()];
    }
}
?>
