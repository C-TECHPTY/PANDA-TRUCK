<?php
// Control plane only: audio/image bytes go directly from the browser to Backblaze.
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function b2_reply($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') b2_reply(['success' => false, 'error' => 'Usa POST.'], 405);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/backblaze_upload.php';
if (empty($_SESSION['user_id'])) b2_reply(['success' => false, 'error' => 'Inicia sesion.'], 401);
$stmt = getDB()->prepare('SELECT id, role, active FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $_SESSION['user_id']]);
if (!panda_b2_admin($_SESSION, $stmt->fetch(PDO::FETCH_ASSOC))) {
    b2_reply(['success' => false, 'error' => 'Solo administradores y superadministradores pueden subir archivos.'], 403);
}
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 16384) b2_reply(['success' => false, 'error' => 'Solicitud demasiado grande.'], 413);
$raw = file_get_contents('php://input', false, null, 0, 16385);
$input = json_decode($raw, true);
if (!is_array($input) || strlen($raw) > 16384) b2_reply(['success' => false, 'error' => 'Solicitud invalida.'], 400);
if (!isset($_SESSION['b2_csrf'], $input['csrf']) || !is_string($input['csrf'])
    || !hash_equals($_SESSION['b2_csrf'], $input['csrf'])) {
    b2_reply(['success' => false, 'error' => 'Recarga el dashboard para renovar la sesion.'], 403);
}
try {
    $config = panda_b2_config();
    $action = $input['action'] ?? '';
    $_SESSION['b2_uploads'] = $_SESSION['b2_uploads'] ?? [];
    if ($action === 'init') {
        // Limit abandoned jobs in one session; bucket lifecycle cleans up unfinished parts.
        foreach ($_SESSION['b2_uploads'] as $id => $job) {
            if (($job['touched'] ?? $job['created']) < time() - 86400 || !empty($job['completed'])) unset($_SESSION['b2_uploads'][$id]);
        }
        if (count($_SESSION['b2_uploads']) >= 4) throw new InvalidArgumentException('Ya hay varias subidas abiertas. Cancela una antes de continuar.');
        $state = panda_b2_plan($input['name'] ?? '', $input['kind'] ?? '', $input['size'] ?? null);
        $state['created'] = time();
        $state['touched'] = time();
        $state['mode'] = $state['size'] < 16 * 1048576 ? 'single' : 'multipart';
        $state['upload_id'] = '';
        if ($state['mode'] === 'multipart') {
            $response = panda_b2_request($config, 'POST', $state['key'], ['uploads' => ''], '', ['content-type' => $state['mime']]);
            $xml = panda_b2_xml($response['body']);
            $state['upload_id'] = (string)$xml->UploadId;
            if ($state['upload_id'] === '') throw new RuntimeException('Backblaze no devolvio el identificador de subida.');
        }
        $id = bin2hex(random_bytes(16));
        $_SESSION['b2_uploads'][$id] = $state;
        b2_reply(['success' => true, 'id' => $id, 'mode' => $state['mode'], 'mime' => $state['mime'],
            'part_size' => $state['part_size'], 'parts' => $state['parts']]);
    }
    $id = $input['id'] ?? '';
    if (!is_string($id) || !isset($_SESSION['b2_uploads'][$id])) throw new InvalidArgumentException('Subida desconocida. Vuelve a seleccionar el archivo.');
    $state = $_SESSION['b2_uploads'][$id];
    $_SESSION['b2_uploads'][$id]['touched'] = time();
    if ($action === 'part') {
        $number = $input['number'] ?? null;
        if (!is_int($number) || $number < 1 || $number > $state['parts'] || !empty($state['completed'])) {
            throw new InvalidArgumentException('Numero de parte invalido.');
        }
        $query = $state['mode'] === 'multipart' ? ['uploadId' => $state['upload_id'], 'partNumber' => $number] : [];
        b2_reply(['success' => true, 'url' => panda_b2_presign($config, 'PUT', $state['key'], $query)]);
    }
    if ($action === 'abort') {
        if ($state['mode'] === 'multipart' && empty($state['completed'])) {
            try {
                panda_b2_request($config, 'DELETE', $state['key'], ['uploadId' => $state['upload_id']]);
            } catch (PandaB2Exception $e) {
                if ($e->serviceCode !== 'NoSuchUpload') throw $e;
            }
        }
        unset($_SESSION['b2_uploads'][$id]);
        b2_reply(['success' => true]);
    }
    if ($action === 'complete') {
        if (!empty($state['completed'])) b2_reply(panda_b2_result($config, $state));
        if ($state['mode'] === 'multipart') {
            try {
                panda_b2_complete($config, $state);
            } catch (PandaB2Exception $e) {
                // A retry after completion/timeout must confirm the object, never return a speculative URL.
                if ($e->serviceCode !== 'NoSuchUpload') throw $e;
            }
        }
        $head = panda_b2_request($config, 'HEAD', $state['key']);
        if ((int)($head['headers']['content-length'] ?? -1) !== $state['size']
            || ($head['headers']['content-type'] ?? '') !== $state['mime']) {
            throw new InvalidArgumentException('Backblaze no confirmo el tamano y formato esperados. Revisa el archivo en el bucket.');
        }
        $_SESSION['b2_uploads'][$id]['completed'] = true;
        b2_reply(panda_b2_result($config, $state));
    }
    throw new InvalidArgumentException('Operacion desconocida.');
} catch (InvalidArgumentException $e) {
    b2_reply(['success' => false, 'error' => $e->getMessage()], 400);
} catch (Throwable $e) {
    // Do not return credentials, signed requests, or cURL diagnostics.
    $message = $e instanceof RuntimeException ? $e->getMessage() : 'No se pudo procesar la subida.';
    b2_reply(['success' => false, 'error' => $message], 503);
}
