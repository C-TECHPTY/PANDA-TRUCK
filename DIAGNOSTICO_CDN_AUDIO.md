# Diagnostico CDN Audio - PANDA-TRUCK

Fecha: 2026-04-26

## Objetivo

Preparar la reproduccion y descarga de MP3 para que usen BunnyCDN:

- CDN final: `https://panda-truck.b-cdn.net/`
- Origin Backblaze: `https://f005.backblazeb2.com/file/DJIMMY-PANDA/`
- Ejemplo confirmado: `https://panda-truck.b-cdn.net/MIXES/PUCHO_ANDS_FRIENDS_VARIADO.mp3`

## Busquedas realizadas

Se revisaron los usos de:

- `.mp3`
- `audio`
- `mix`
- `download`
- `readfile`
- `file_get_contents`
- `Content-Type: audio`
- `backblazeb2.com`
- `uploads`
- `MIXES`
- `ruta`
- `archivo`
- `file_path`
- `download_url`
- `audio_url`

Carpetas/archivos revisados: `index.php`, `mixes.php`, `lista_djs.php`, `dj.php`, `dj-pro.php`, `albumes.php`, `superpacks.php`, `dashboard.php`, `api/`, `includes/`, `player/`, `dj/`, `admin/`, `cron/`, `sql/`, `migrations/`.

## Donde se reproducen audios

- `player/index.php`
  - Reproductor principal con `new Audio()`.
  - Ya usa `preload = 'none'`.
  - La playlist PHP ya genera URLs con `resolveMediaUrl($mix['url'])`, pero algunos atributos HTML `data-url` aun imprimen `$mix['url']` directo.

- `dj/superpack.php`
  - Los botones/lista usan `data-url`.
  - Actualmente usan `resolveMediaUrl($mix['url'])`.

- `index.php`
  - Usa un `new Audio()` para radio. No es MP3 de mixes.
  - Los mixes destacados enlazan al reproductor `player/index.php?id=...`.

- `mixes.php`
  - Enlaza al reproductor `player/index.php?id=...`.

- `dj.php`
  - Enlaza al reproductor `player/index.php?id=...`.

## Donde se descargan audios

- `api/download_mix.php`
  - Endpoint principal de descarga individual.
  - Valida ID, registra descarga y redirige.
  - Actualmente redirige usando `resolveMediaUrl($mix['url'])`.
  - No sirve el MP3 por PHP.

- `api/download.php`
  - Endpoint de compatibilidad.
  - Incluye `api/download_mix.php`.

- `api/descargar_zip.php`
  - Descarga varios MP3 al servidor temporalmente con cURL/fopen y genera ZIP con `readfile()`.
  - Riesgo: para MP3, esto usa hosting como intermediario.

- `api/download_superpack.php`
  - Descarga MP3 al servidor temporalmente con cURL/fopen y genera ZIP con `readfile()`.
  - Riesgo: para MP3, esto usa hosting como intermediario.

- `api/download_album.php`
  - Genera ZIP de albumes/canciones descargando archivos al servidor y usando `readfile()`.
  - Aplica a albumes, no necesariamente mixes, pero puede incluir MP3.

## Campos de base de datos que guardan rutas MP3

- `mixes.url`
  - Campo principal para audio MP3 de mixes.
  - Actualmente puede contener URL completa Backblaze, URL BunnyCDN o ruta relativa.
  - Recomendado: guardar ruta relativa, por ejemplo `MIXES/archivo.mp3`.

- `canciones.url`
  - Usado por albumes/canciones.
  - Puede contener URLs de audio/archivos para ZIP de albumes.

## Archivos que deben cambiarse

- `includes/config.php`
  - Centralizar `CDN_BASE_URL`.
  - Cargar helper `includes/cdn.php`.

- `includes/cdn.php`
  - Nuevo helper `cdn_audio_url()`, `cdn_download_url()` y normalizacion de rutas.

- `api/download_mix.php`
  - Usar `cdn_download_url()`.
  - Mantener conteo de descargas antes del redirect.

- `player/index.php`
  - Usar `cdn_audio_url()` en todos los `data-url` y playlist.

- `dj/superpack.php`
  - Usar `cdn_audio_url()` en `data-url`.
  - Boton individual debe ir a `api/download_mix.php?id=...`.

- `api/player.php`
  - Para mixes, devolver URLs CDN en el JSON.

- `api/save_mix.php`
  - Normalizar URL de Backblaze/Bunny a ruta relativa al guardar.

- `dashboard.php`
  - Ajustar texto del campo de URL de audio para pedir ruta relativa o URL soportada.

- `sql/normalizar_rutas_cdn_audio.sql`
  - SQL opcional para normalizar rutas existentes.

## Riesgos detectados

- Los ZIP de mixes/superpacks descargan MP3 al hosting para empaquetar. Esto contradice la regla de no servir/transportar MP3 desde PHP. Se deben deshabilitar o cambiar a un flujo que no descargue MP3 al servidor. La opcion segura es redirigir descargas masivas hacia descargas individuales CDN o mostrar instrucciones de descarga individual.

- BunnyCDN puede reproducir MP3 en el navegador si no envia `Content-Disposition: attachment`. PHP no puede forzar descarga despues de redirigir a otro servidor. Para forzar descarga al 100%, debe configurarse una Edge Rule/Header Rule en BunnyCDN sobre una ruta separada o una pull zone dedicada de descargas.

- Hay URLs Backblaze en portadas/videos. No son MP3 de mixes. No deben cambiarse ciegamente para evitar romper imagenes o video.

- `file_get_contents('php://input')` aparece en APIs JSON. Eso no sirve MP3 y no es problema. La regla prohibida aplica a enviar audio desde PHP.

## Estado antes de cambios

- Descarga individual: ya redirige y no sirve archivo.
- Player principal: parcialmente listo, pero requiere helper CDN central y corregir `data-url`.
- ZIP de mixes/superpacks/albumes: usan hosting como intermediario y deben tratarse como excepcion/riesgo.
