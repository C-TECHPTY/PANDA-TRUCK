# Configuracion CDN para Audio

Objetivo: que los MP3 salgan por CDN/Backblaze/Bunny y no por PHP.

## Arquitectura recomendada

```text
Namecheap = PHP, dashboard y base de datos
Backblaze B2 = almacenamiento de MP3
BunnyCDN = entrega rapida de MP3 al usuario
```

## Regla principal

No usar PHP para servir MP3 pesados:

- No `readfile()` para MP3.
- No `file_get_contents()` para MP3.
- No streaming de audio desde PHP.

PHP solo debe:

1. Registrar reproduccion o descarga.
2. Redirigir al archivo real/CDN.

## Configuracion central

Agregar en `system_settings`:

```text
cdn_base_url=https://cdn.tudominio.com/
```

Si en la base se guarda ruta relativa:

```text
MIXES/archivo.mp3
```

La URL final debe construirse como:

```text
CDN_BASE_URL + ruta_relativa
```

El sistema debe mantener compatibilidad con URLs antiguas completas de Backblaze.

## BunnyCDN Pull Zone

1. Crear cuenta en BunnyCDN.
2. Crear Pull Zone.
3. Origin URL: URL publica del bucket Backblaze o dominio actual de archivos.
4. Agregar hostname CDN: `cdn.tudominio.com`.
5. Crear CNAME en DNS apuntando a BunnyCDN.
6. Probar un MP3 directo.

## Descarga forzada

Para forzar descarga sin PHP, configurar header en CDN/storage:

```http
Content-Disposition: attachment
```

Si no se configura, algunos navegadores pueden abrir el MP3 en vez de descargarlo.
