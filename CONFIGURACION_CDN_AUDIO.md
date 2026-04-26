# Configuracion CDN de Audio

Objetivo: reproducir MP3 desde BunnyCDN con origin en Backblaze B2, sin streaming ni descarga de MP3 desde PHP.

## URLs confirmadas

- CDN: `https://panda-truck.b-cdn.net/`
- Origin Backblaze: `https://f005.backblazeb2.com/file/DJIMMY-PANDA/`
- Prueba confirmada: `https://panda-truck.b-cdn.net/MIXES/PUCHO_ANDS_FRIENDS_VARIADO.mp3`

## Regla principal

- Los MP3 se reproducen desde BunnyCDN.
- Los MP3 se descargan desde BunnyCDN.
- PHP solo registra estadisticas y redirige.
- No usar PHP para streaming de MP3.
- No usar el hosting como origen publico de MP3.

## Configuracion del sistema

En `includes/config.local.php`:

```php
'CDN_BASE_URL' => 'https://panda-truck.b-cdn.net/',
'BACKBLAZE_AUDIO_ORIGIN' => 'https://f005.backblazeb2.com/file/DJIMMY-PANDA/',
```

La logica central esta en:

- `includes/cdn.php`
- `cdn_audio_url($path)`
- `cdn_download_url($path, $filename = '')`
- `cdn_normalize_audio_path($path)`

## Como guardar rutas de MP3

Preferido:

```text
MIXES/PUCHO_ANDS_FRIENDS_VARIADO.mp3
```

El sistema lo convierte a:

```text
https://panda-truck.b-cdn.net/MIXES/PUCHO_ANDS_FRIENDS_VARIADO.mp3
```

Tambien acepta URLs viejas de Backblaze:

```text
https://f005.backblazeb2.com/file/DJIMMY-PANDA/MIXES/archivo.mp3
```

y las convierte en URL CDN al reproducir/descargar.

## Reproduccion

El reproductor usa `preload = 'none'` y carga:

```php
cdn_audio_url($mix['url'])
```

Archivos revisados:

- `player/index.php`
- `dj/superpack.php`
- `api/player.php`

## Descarga

Los botones deben apuntar a:

```text
api/download_mix.php?id=ID_DEL_MIX
```

Ese endpoint:

1. Valida el ID.
2. Busca el mix activo.
3. Suma descarga en `mixes`.
4. Suma descarga en `statistics`.
5. Genera URL BunnyCDN con `cdn_download_url()`.
6. Redirige con `Location`.

## BunnyCDN

En BunnyCDN:

1. Crear o usar Pull Zone `panda-truck`.
2. Origin URL: `https://f005.backblazeb2.com/file/DJIMMY-PANDA/`
3. Probar:
   `https://panda-truck.b-cdn.net/MIXES/PUCHO_ANDS_FRIENDS_VARIADO.mp3`

Segun la documentacion de Bunny, Edge Rules permite acciones como `Set Response Header` y `Force Download`. Para forzar descarga al navegador, usar una regla especifica de descarga, no global sobre todos los MP3 porque romperia el player.

Fuente Bunny: https://docs.bunny.net/cdn/edge-rules

## Pruebas

- Abrir una URL CDN directa.
- Abrir `player/index.php?id=ID`.
- En DevTools, confirmar que el audio sale de `panda-truck.b-cdn.net`.
- Hacer click en descargar y confirmar que primero pasa por `api/download_mix.php?id=ID` y luego redirige al CDN.
- Confirmar que no aparece Backblaze directo en HTML publico para mixes.
