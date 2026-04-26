<?php
if (!defined('CDN_BASE_URL')) {
    define('CDN_BASE_URL', 'https://panda-truck.b-cdn.net/');
}

if (!defined('BACKBLAZE_AUDIO_ORIGIN')) {
    define('BACKBLAZE_AUDIO_ORIGIN', 'https://f005.backblazeb2.com/file/');
}

function cdn_backblaze_audio_origins() {
    return [
        rtrim(BACKBLAZE_AUDIO_ORIGIN, '/') . '/',
    ];
}

function cdn_strip_url_suffix($path) {
    $path = trim((string)$path);
    $path = preg_replace('/[?#].*$/', '', $path);
    return $path;
}

function cdn_normalize_relative_audio_path($path) {
    $path = str_replace('\\', '/', cdn_strip_url_suffix($path));
    $path = rawurldecode(ltrim($path, '/'));

    if ($path === '') {
        return '';
    }

    if (stripos($path, 'MIXES/') === 0) {
        return 'DJIMMY-PANDA/' . $path;
    }

    return $path;
}

function cdn_normalize_audio_path($path) {
    $path = trim((string)$path);

    if ($path === '') {
        return '';
    }

    $cdnBase = rtrim(CDN_BASE_URL, '/') . '/';

    if (stripos($path, $cdnBase) === 0) {
        return cdn_normalize_relative_audio_path(substr($path, strlen($cdnBase)));
    }

    foreach (cdn_backblaze_audio_origins() as $origin) {
        if (stripos($path, $origin) === 0) {
            $relativePath = substr($path, strlen($origin));
            return cdn_normalize_relative_audio_path(str_replace('+', ' ', $relativePath));
        }
    }

    return cdn_normalize_relative_audio_path($path);
}

function cdn_encode_path($path) {
    $path = str_replace('\\', '/', trim((string)$path));
    $parts = explode('/', ltrim($path, '/'));
    $encoded = [];

    foreach ($parts as $part) {
        if ($part === '') {
            continue;
        }
        $encoded[] = rawurlencode(rawurldecode($part));
    }

    return implode('/', $encoded);
}

function cdn_audio_enabled() {
    if (!defined('CDN_AUDIO_ENABLED')) {
        return true;
    }

    return (bool)CDN_AUDIO_ENABLED;
}

function cdn_origin_audio_url($path) {
    $path = trim((string)$path);

    if ($path === '') {
        return '';
    }

    $cdnBase = rtrim(CDN_BASE_URL, '/') . '/';
    $originBase = rtrim(BACKBLAZE_AUDIO_ORIGIN, '/') . '/';

    if (stripos($path, $cdnBase) === 0) {
        $path = substr($path, strlen($cdnBase));
        return $originBase . cdn_encode_path(cdn_normalize_relative_audio_path($path));
    }

    foreach (cdn_backblaze_audio_origins() as $origin) {
        if (stripos($path, $origin) === 0) {
            $path = substr($path, strlen($origin));
            return $originBase . cdn_encode_path(cdn_normalize_relative_audio_path(str_replace('+', ' ', $path)));
        }
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    return $originBase . cdn_encode_path(cdn_normalize_relative_audio_path($path));
}

function cdn_audio_url($path) {
    $path = trim((string)$path);

    if ($path === '') {
        return '';
    }

    if (!cdn_audio_enabled()) {
        return cdn_origin_audio_url($path);
    }

    $cdnBase = rtrim(CDN_BASE_URL, '/') . '/';

    if (stripos($path, $cdnBase) === 0) {
        return $path;
    }

    foreach (cdn_backblaze_audio_origins() as $origin) {
        if (stripos($path, $origin) === 0) {
            $path = substr($path, strlen($origin));
            return $cdnBase . cdn_encode_path(cdn_normalize_relative_audio_path(str_replace('+', ' ', $path)));
        }
    }

    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }

    return $cdnBase . cdn_encode_path(cdn_normalize_relative_audio_path($path));
}

function cdn_download_url($path, $filename = '') {
    $url = cdn_audio_url($path);

    if ($url === '') {
        return '';
    }

    $separator = strpos($url, '?') === false ? '?' : '&';
    return $url . $separator . 'download=1';
}
?>
