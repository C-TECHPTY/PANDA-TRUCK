# Reporte Cambio Masivo CDN

Fecha: 2026-04-26

## Resumen

Se centralizo la generacion de URLs de audio para que los MP3 se reproduzcan y descarguen desde BunnyCDN:

```text
https://panda-truck.b-cdn.net/
```

PHP no sirve el MP3. Para descargas individuales, PHP registra estadisticas y redirige al CDN.

## Archivos modificados

- `includes/config.php`
- `includes/config.local.example.php`
- `api/download_mix.php`
- `api/download.php`
- `api/player.php`
- `api/save_mix.php`
- `api/descargar_zip.php`
- `api/download_superpack.php`
- `player/index.php`
- `dj/superpack.php`
- `dashboard.php`
- `CONFIGURACION_CDN_AUDIO.md`

## Archivos nuevos

- `includes/cdn.php`
- `DIAGNOSTICO_CDN_AUDIO.md`
- `CONFIGURACION_DESCARGAS_CDN.md`
- `CHECKLIST_CDN_AUDIO.md`
- `REPORTE_CAMBIO_MASIVO_CDN.md`
- `sql/normalizar_rutas_cdn_audio.sql`

## Funciones agregadas

- `cdn_audio_url($path)`
- `cdn_download_url($path, $filename = '')`
- `cdn_normalize_audio_path($path)`
- `cdn_encode_path($path)`

`cdn_download_url()` genera URLs marcadas para BunnyCDN:

```text
?download=1&filename=archivo.mp3
```

Esto permite aplicar una Edge Rule `Force Download` solo a descargas, sin afectar el player.

## Endpoints revisados

- `api/download_mix.php`
  - Cuenta descargas y redirige a BunnyCDN.

- `api/download.php`
  - Compatibilidad con `api/download_mix.php`.

- `api/player.php`
  - Devuelve URL CDN para mixes.

- `api/descargar_zip.php`
  - Ya no genera ZIP con MP3 en el hosting. Muestra links individuales CDN.

- `api/download_superpack.php`
  - Redirige a la lista segura de descargas CDN.

## Cambios de base de datos

No se ejecuta ningun cambio automaticamente.

Se agrego SQL opcional:

```text
sql/normalizar_rutas_cdn_audio.sql
```

Sirve para convertir URLs completas de Backblaze/Bunny en rutas relativas dentro de `mixes.url`.

## Pruebas realizadas

- OK - `php -l` en `includes/cdn.php`.
- OK - `php -l` en `includes/config.php`.
- OK - `php -l` en `api/download_mix.php`.
- OK - `php -l` en `api/player.php`.
- OK - `php -l` en `api/save_mix.php`.
- OK - `php -l` en `api/descargar_zip.php`.
- OK - `php -l` en `api/download_superpack.php`.
- OK - `php -l` en `player/index.php`.
- OK - `php -l` en `dj/superpack.php`.
- OK - `php -l` en `dashboard.php`.
- OK - Busqueda de `readfile`, `fopen`, `curl_init` y `Content-Type: audio` en endpoints MP3 actualizados sin coincidencias.
- OK - Prueba directa del helper: `MIXES/PUCHO_ANDS_FRIENDS_VARIADO.mp3` genera `https://panda-truck.b-cdn.net/MIXES/PUCHO_ANDS_FRIENDS_VARIADO.mp3`.
- PENDIENTE - Probar hosting con DevTools.

## Instrucciones para subir al hosting

Subir/reemplazar:

- `includes/config.php`
- `includes/cdn.php`
- `includes/config.local.example.php`
- `api/download_mix.php`
- `api/download.php`
- `api/player.php`
- `api/save_mix.php`
- `api/descargar_zip.php`
- `api/download_superpack.php`
- `player/index.php`
- `dj/superpack.php`
- `dashboard.php`
- Documentacion `.md` si quieres conservarla en hosting.
- `sql/normalizar_rutas_cdn_audio.sql` solo como herramienta opcional.

## Archivos que NO debo tocar

- `includes/config.local.php` real del hosting.
- Credenciales SMTP reales.
- Carpetas `uploads/` con contenido real.
- Base de datos sin respaldo.
- Archivos reales en Backblaze/Bunny.

## Nota sobre descarga forzada

El navegador puede abrir el MP3 si BunnyCDN no envia `Content-Disposition: attachment`.

BunnyCDN soporta Edge Rules con acciones como `Set Response Header` y `Force Download`. Configurar esa regla cuando el query string contenga `download=1`, no globalmente en `/MIXES/*.mp3`, para no romper el player.

Fuente: https://docs.bunny.net/cdn/edge-rules
