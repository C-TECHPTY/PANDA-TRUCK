# Checklist CDN Audio

## Configuracion

- OK - `CDN_BASE_URL` apunta a `https://panda-truck.b-cdn.net/`.
- OK - `BACKBLAZE_AUDIO_ORIGIN` apunta a `https://f005.backblazeb2.com/file/DJIMMY-PANDA/`.
- OK - Existe helper central `includes/cdn.php`.
- OK - Las rutas nuevas recomendadas son relativas: `MIXES/archivo.mp3`.
- OK - Existe SQL opcional `sql/normalizar_rutas_cdn_audio.sql`.

## Reproduccion

- OK - `player/index.php` usa `cdn_audio_url()`.
- OK - `api/player.php` devuelve URLs CDN para mixes.
- OK - `dj/superpack.php` usa URLs CDN en `data-url`.
- OK - El reproductor usa `preload = 'none'`.

## Descargas

- OK - Botones publicos usan `api/download_mix.php?id=ID`.
- OK - `api/download_mix.php` cuenta descarga y redirige al CDN.
- OK - `api/download.php` conserva compatibilidad.
- OK - Superpack ya no arma ZIP descargando MP3 al hosting.
- OK - Sintaxis PHP validada en archivos CDN modificados.
- OK - No hay `readfile()`, `fopen()` ni `curl_init()` en los endpoints MP3 actualizados.

## Pendiente en BunnyCDN

- PENDIENTE - Si se quiere forzar descarga al 100%, crear Edge Rule `Force Download` o `Set Response Header` solo en ruta dedicada de descargas.

## Pruebas finales recomendadas

- PENDIENTE - Probar home en hosting.
- PENDIENTE - Probar login en hosting.
- PENDIENTE - Probar dashboard en hosting.
- PENDIENTE - Probar `player/index.php?id=ID` en hosting.
- PENDIENTE - Confirmar en DevTools que el MP3 sale de `panda-truck.b-cdn.net`.
- PENDIENTE - Probar boton Descargar y confirmar redireccion a CDN.
- PENDIENTE - Confirmar que sube el contador de descargas.
- PENDIENTE - Probar perfil DJ.
- PENDIENTE - Probar superpack.
