# Configuracion de Descargas CDN

## Flujo correcto

Reproduccion:

```text
https://panda-truck.b-cdn.net/mixes-mp3/archivo.mp3
https://panda-truck.b-cdn.net/DJIMMY-PANDA/MIXES/archivo.mp3
```

Descarga:

```text
api/download_mix.php?id=123
```

El hosting no entrega el MP3. Solo registra estadisticas y redirige a BunnyCDN.

## Que hace `api/download_mix.php`

1. Recibe `id`.
2. Valida que el mix exista y este activo.
3. Incrementa `mixes.downloads`.
4. Incrementa `statistics.downloads`.
5. Genera URL con `cdn_download_url()`.
6. Redirige al CDN con HTTP `302`.
7. Termina con `exit`.

La URL final de descarga queda marcada asi:

```text
https://panda-truck.b-cdn.net/mixes-mp3/archivo.mp3?download=1
https://panda-truck.b-cdn.net/DJIMMY-PANDA/MIXES/archivo.mp3?download=1
```

## Origen correcto en BunnyCDN

Como hay mixes en dos buckets de Backblaze, el Pull Zone debe apuntar al nivel padre:

```text
https://f005.backblazeb2.com/file/
```

No debe apuntar solo a `DJIMMY-PANDA/` ni solo a `mixes-mp3/`.

## Formato correcto en la base de datos

La columna `mixes.url` debe guardar rutas relativas con el bucket incluido:

```text
mixes-mp3/01 THE PANDATRUCK By DjROLY.mp3
DJIMMY-PANDA/MIXES/Vallenato Del Alma VOL 1.mp3
```

No guardes URLs completas de Backblaze o Bunny en `mixes.url`.

## Si el navegador abre el reproductor

La descarga final la sirve BunnyCDN. Si Bunny no envia `Content-Disposition: attachment`, algunos navegadores pueden abrir el MP3 en su reproductor integrado.

Opciones:

- Configurar en BunnyCDN una Edge Rule de tipo `Force Download` cuando el query string contenga `download=1`.
- Configurar una Edge Rule `Set Response Header` con `Content-Disposition: attachment` cuando el query string contenga `download=1`.
- No aplicar `Content-Disposition: attachment` globalmente sobre `/MIXES/*.mp3`, porque afectaria el reproductor.

La documentacion oficial de Bunny Edge Rules indica que existen acciones `Set Response Header` y `Force Download`:

https://docs.bunny.net/cdn/edge-rules

## Recomendacion para descarga forzada

Mantener una URL limpia de reproduccion:

```text
/mixes-mp3/archivo.mp3
/DJIMMY-PANDA/MIXES/archivo.mp3
```

Para descarga forzada al 100%, usar esta condicion en Bunny Edge Rules:

```text
Query String equals download=1
```

Accion recomendada: `Force Download`.

## Super Packs y descargas masivas

Los endpoints de superpack ya no descargan MP3 al servidor para crear ZIP. En su lugar muestran una lista de descargas individuales que pasan por `api/download_mix.php` y terminan en BunnyCDN.

Esto evita consumo de CPU/disco/ancho de banda del hosting compartido.

## Modo respaldo si BunnyCDN se queda sin credito

El sitio puede operar directo contra Backblaze sin usar RAM del hosting. En `public_html/includes/config.local.php` cambia:

```php
'CDN_AUDIO_ENABLED' => false,
```

Con eso:

- Reproduccion: usa `https://f005.backblazeb2.com/file/...`
- Descarga: sigue pasando por `api/download_mix.php` para contar estadisticas y redirigir.
- Hosting: no lee ni transmite MP3, solo hace consultas SQL y redirect.
- Dashboard: muestra alerta `CDN DESACTIVADO: USANDO BACKBLAZE`.

Cuando recargues saldo en Bunny, vuelve a:

```php
'CDN_AUDIO_ENABLED' => true,
```

La descarga forzada con `Content-Disposition: attachment` depende de BunnyCDN. En modo Backblaze el navegador podria abrir el reproductor integrado.

## Archivos relacionados

- `includes/cdn.php`
- `api/download_mix.php`
- `api/download.php`
- `api/descargar_zip.php`
- `api/download_superpack.php`
- `player/index.php`
- `dj/superpack.php`
- `sql/normalizar_rutas_cdn_audio.sql`
