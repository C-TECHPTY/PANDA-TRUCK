<?php
// Isolated Backblaze S3 integration. Never load local credentials into JavaScript.
class PandaB2Exception extends RuntimeException {
    public $serviceCode;
    public function __construct($message, $serviceCode = '') {
        parent::__construct($message);
        $this->serviceCode = $serviceCode;
    }
}

function panda_b2_config() {
    $file = __DIR__ . '/backblaze.local.php';
    $local = is_file($file) ? require $file : [];
    if (!is_array($local)) throw new RuntimeException('Configuracion Backblaze invalida.');
    $get = function ($key, $default = '') use ($local) {
        return $local[$key] ?? (getenv($key) !== false ? getenv($key) : $default);
    };
    $config = [
        'enabled' => filter_var($get('B2_UPLOAD_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'key_id' => $get('B2_KEY_ID'),
        'secret' => $get('B2_APPLICATION_KEY'),
        'region' => $get('B2_REGION', 'us-east-005'),
        'bucket' => 'DJIMMY-PANDA',
        'friendly_base' => 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/',
    ];
    if (!$config['enabled']) throw new RuntimeException('La subida a Backblaze aun no esta configurada. Puedes seguir pegando enlaces.');
    if ($config['key_id'] === '' || $config['secret'] === '') throw new RuntimeException('Falta configurar la clave limitada de Backblaze en el servidor.');
    if (!preg_match('/^[a-z0-9-]+$/D', $config['region'])) throw new RuntimeException('Region de Backblaze invalida.');
    if (PHP_INT_SIZE < 8) throw new RuntimeException('La subida requiere PHP de 64 bits.');
    return $config;
}

function panda_b2_admin($session, $user) {
    return !empty($session['user_id']) && $user && (int)$user['id'] === (int)$session['user_id']
        && (int)$user['active'] === 1 && in_array($user['role'], ['admin', 'superadmin'], true);
}

function panda_b2_plan($name, $kind, $size) {
    if (!is_int($size) || $size < 1 || $size > 10000000000000) {
        throw new InvalidArgumentException('Archivo vacio o demasiado grande. Esta integracion admite hasta 10 TB.');
    }
    $formats = [
        'audio' => ['mp3' => 'audio/mpeg', 'm4a' => 'audio/mp4', 'wav' => 'audio/wav',
            'ogg' => 'audio/ogg', 'flac' => 'audio/flac', 'aac' => 'audio/aac'],
        'image' => ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
            'webp' => 'image/webp', 'gif' => 'image/gif'],
    ];
    $extension = strtolower(pathinfo((string)$name, PATHINFO_EXTENSION));
    if (!isset($formats[$kind][$extension])) throw new InvalidArgumentException('Formato de archivo no permitido.');
    // Generate an independent key: never trust a client-supplied bucket or path.
    $stem = pathinfo(str_replace('\\', '/', (string)$name), PATHINFO_FILENAME);
    $stem = trim(preg_replace('/[^a-zA-Z0-9_-]+/', '-', $stem), '-');
    $stem = substr($stem !== '' ? $stem : 'archivo', 0, 80);
    $key = ($kind === 'audio' ? 'MIXES/' : 'PORTADAS/') . $stem . '-' . bin2hex(random_bytes(12)) . '.' . $extension;
    $partSize = (int)(max(16 * 1048576, ceil($size / 10000 / 1048576) * 1048576));
    return ['key' => $key, 'kind' => $kind, 'mime' => $formats[$kind][$extension],
        'size' => $size, 'part_size' => $partSize, 'parts' => (int)ceil($size / $partSize)];
}

function panda_b2_path($config, $key) {
    return '/' . rawurlencode($config['bucket']) . '/' . implode('/', array_map('rawurlencode', explode('/', $key)));
}

function panda_b2_query($query) {
    ksort($query, SORT_STRING);
    $encoded = [];
    foreach ($query as $key => $value) $encoded[] = rawurlencode($key) . '=' . rawurlencode((string)$value);
    return implode('&', $encoded);
}

function panda_b2_signature($config, $date, $scope, $canonical) {
    $key = hash_hmac('sha256', substr($date, 0, 8), 'AWS4' . $config['secret'], true);
    $key = hash_hmac('sha256', $config['region'], $key, true);
    $key = hash_hmac('sha256', 's3', $key, true);
    $key = hash_hmac('sha256', 'aws4_request', $key, true);
    return hash_hmac('sha256', "AWS4-HMAC-SHA256\n" . $date . "\n" . $scope . "\n" . hash('sha256', $canonical), $key);
}

function panda_b2_presign($config, $method, $key, $query = [], $timestamp = null) {
    $date = gmdate('Ymd\THis\Z', $timestamp ?? time());
    $scope = substr($date, 0, 8) . '/' . $config['region'] . '/s3/aws4_request';
    $host = 's3.' . $config['region'] . '.backblazeb2.com';
    $query += ['X-Amz-Algorithm' => 'AWS4-HMAC-SHA256', 'X-Amz-Credential' => $config['key_id'] . '/' . $scope,
        'X-Amz-Date' => $date, 'X-Amz-Expires' => '3600', 'X-Amz-SignedHeaders' => 'host'];
    $uri = panda_b2_path($config, $key);
    $canonical = $method . "\n" . $uri . "\n" . panda_b2_query($query) . "\nhost:" . $host . "\n\nhost\nUNSIGNED-PAYLOAD";
    $query['X-Amz-Signature'] = panda_b2_signature($config, $date, $scope, $canonical);
    return 'https://' . $host . $uri . '?' . panda_b2_query($query);
}

function panda_b2_request($config, $method, $key, $query = [], $body = '', $extraHeaders = []) {
    if (!function_exists('curl_init') || !function_exists('simplexml_load_string')) {
        throw new RuntimeException('Activa cURL y SimpleXML en PHP desde cPanel.');
    }
    $date = gmdate('Ymd\THis\Z');
    $scope = substr($date, 0, 8) . '/' . $config['region'] . '/s3/aws4_request';
    $host = 's3.' . $config['region'] . '.backblazeb2.com';
    $headers = ['host' => $host, 'x-amz-content-sha256' => hash('sha256', $body), 'x-amz-date' => $date] + $extraHeaders;
    ksort($headers, SORT_STRING);
    $canonicalHeaders = '';
    foreach ($headers as $name => $value) $canonicalHeaders .= $name . ':' . trim($value) . "\n";
    $signedHeaders = implode(';', array_keys($headers));
    $uri = panda_b2_path($config, $key);
    $queryString = panda_b2_query($query);
    $canonical = $method . "\n" . $uri . "\n" . $queryString . "\n" . $canonicalHeaders . "\n" . $signedHeaders . "\n" . hash('sha256', $body);
    $signature = panda_b2_signature($config, $date, $scope, $canonical);
    $requestHeaders = [];
    foreach ($headers as $name => $value) $requestHeaders[] = $name . ': ' . $value;
    $requestHeaders[] = 'Authorization: AWS4-HMAC-SHA256 Credential=' . $config['key_id'] . '/' . $scope
        . ', SignedHeaders=' . $signedHeaders . ', Signature=' . $signature;
    $responseHeaders = [];
    $curl = curl_init('https://' . $host . $uri . ($queryString !== '' ? '?' . $queryString : ''));
    curl_setopt_array($curl, [
        CURLOPT_CUSTOMREQUEST => $method, CURLOPT_HTTPHEADER => $requestHeaders,
        CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 10, CURLOPT_TIMEOUT => 60,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HEADERFUNCTION => function ($curl, $line) use (&$responseHeaders) {
            $split = explode(':', $line, 2);
            if (count($split) === 2) $responseHeaders[strtolower(trim($split[0]))] = trim($split[1]);
            return strlen($line);
        },
    ]);
    if ($method === 'HEAD') curl_setopt($curl, CURLOPT_NOBODY, true);
    if ($method === 'POST') curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
    $result = curl_exec($curl);
    $status = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if ($result === false) throw new PandaB2Exception('No se pudo comunicar con Backblaze. Reintenta o comprueba el bucket.');
    if ($status < 200 || $status >= 300) {
        $xml = $result !== '' ? @simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NONET) : false;
        $code = $xml ? (string)$xml->Code : '';
        throw new PandaB2Exception('Backblaze rechazo la operacion' . ($code !== '' ? ' (' . preg_replace('/[^a-zA-Z0-9]/', '', $code) . ')' : '') . '.', $code);
    }
    return ['body' => $result, 'headers' => $responseHeaders];
}

function panda_b2_xml($body) {
    $xml = @simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NONET);
    if (!$xml || $xml->getName() === 'Error') {
        throw new PandaB2Exception('Backblaze no confirmo la operacion.');
    }
    return $xml;
}

function panda_b2_result($config, $state) {
    return ['success' => true, 'url' => $config['friendly_base'] . implode('/', array_map('rawurlencode', explode('/', $state['key']))),
        'size_mb' => (int)ceil($state['size'] / 1048576), 'key' => $state['key']];
}

function panda_b2_validate_parts($state, $parts) {
    if (count($parts) !== $state['parts']) throw new InvalidArgumentException('Faltan partes del archivo. Reintenta la subida.');
    $total = 0;
    foreach ($parts as $i => $part) {
        $expected = min($state['part_size'], $state['size'] - $i * $state['part_size']);
        if ($part['number'] !== $i + 1 || $part['size'] !== $expected || $part['etag'] === '') {
            throw new InvalidArgumentException('Las partes no coinciden con el archivo seleccionado.');
        }
        $total += $part['size'];
    }
    if ($total !== $state['size']) throw new InvalidArgumentException('El tamano subido no coincide.');
}

function panda_b2_complete($config, $state) {
    $parts = [];
    $marker = 0;
    do {
        $response = panda_b2_request($config, 'GET', $state['key'], ['uploadId' => $state['upload_id'], 'part-number-marker' => $marker]);
        $xml = panda_b2_xml($response['body']);
        foreach ($xml->Part as $part) {
            $parts[] = ['number' => (int)$part->PartNumber, 'size' => (int)$part->Size, 'etag' => (string)$part->ETag];
        }
        $next = (int)$xml->NextPartNumberMarker;
        $more = (string)$xml->IsTruncated === 'true';
        if ($more && $next <= $marker) throw new RuntimeException('Respuesta de paginacion invalida.');
        $marker = $next;
    } while ($more && count($parts) <= 10000);
    panda_b2_validate_parts($state, $parts);
    $body = '<CompleteMultipartUpload>';
    foreach ($parts as $part) {
        $body .= '<Part><PartNumber>' . $part['number'] . '</PartNumber><ETag>'
            . htmlspecialchars($part['etag'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</ETag></Part>';
    }
    $response = panda_b2_request($config, 'POST', $state['key'], ['uploadId' => $state['upload_id']],
        $body . '</CompleteMultipartUpload>', ['content-type' => 'application/xml']);
    panda_b2_xml($response['body']);
}
