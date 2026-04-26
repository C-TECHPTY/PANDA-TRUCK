# Diagnostico de Migracion - Panda Truck Reloaded

Fecha: 2026-04-26
Repositorio: https://github.com/C-TECHPTY/PANDA-TRUCK

## Estructura Actual

- `admin/`: modulos administrativos adicionales, DJ PRO y reportes.
- `api/`: endpoints JSON/descarga/CRUD usados por dashboard y frontend.
- `assets/`: CSS, JS e imagenes base.
- `cron/`: tareas programadas para suscripciones.
- `dj/`: compatibilidad de perfiles antiguos y vistas de superpacks.
- `includes/`: configuracion, autenticacion, conexion y tracking.
- `player/`: reproductor de audio/video.
- `sql/`: scripts SQL de estructura, produccion y migracion.
- raiz: paginas publicas y panel principal.

## Archivos Principales

- `index.php`: home publica, radio, banners, mixes destacados.
- `login.php`, `logout.php`: acceso administrativo.
- `dashboard.php`: panel admin principal.
- `mixes.php`: listado publico de mixes.
- `lista_djs.php`: listado publico de DJs.
- `albumes.php`: catalogo de albumes.
- `superpacks.php`: pagina publica de superpacks.
- `dj.php`: perfil publico de DJ.
- `dj-pro.php`: pagina publica de oferta DJ PRO.

## Admin

- `dashboard.php`: CRUD de mixes, DJs, videos, albumes, banners, ajustes y estadisticas.
- `admin/dj_pro.php`: administracion de membresia DJ PRO, pagos Yappy y vencimientos.
- `admin/reports/generate_partner_report.php`: genera PDF simple sin libreria externa.

## API

Endpoints activos principales:

- CRUD: `api/save_mix.php`, `api/save_dj.php`, `api/save_video.php`, `api/save_album.php`, `api/save_banner.php`.
- Lectura: `api/get_mix.php`, `api/get_dj.php`, `api/get_all_djs.php`, `api/get_albumes.php`, `api/get_banners.php`.
- Estadisticas/descargas: `api/download_mix.php`, `api/update_stats.php`, `api/get_stats.php`, `api/sync_stats.php`.
- Sistema: `api/save_settings.php`, `api/set_maintenance.php`, `api/backup.php`, `api/optimize.php`.

## Conexion y Configuracion

- `includes/config.php`: define constantes y `getDB()`. Lee `includes/config.local.php` si existe.
- `includes/config.local.example.php`: plantilla segura para hosting.
- `includes/db.php`: clase alternativa de conexion usada por helpers legados.
- `includes/auth.php`: login, roles, logs y estadisticas base.

## SQL Existente

- `sql/estructura.sql`: estructura base historica.
- `sql/PRODUCCION_COMPLETA_IMPORTAR.sql`: script final para instalacion nueva.
- `sql/MIGRACION_SOLO_CAMBIOS.sql`: script incremental.
- `panda_truck_v2.sql`, `pandqgxl_panda_truck_db.sql`: dumps historicos; no se recomiendan para importar en produccion nueva.
- `migrations/upgrade_dj_pro_stats.sql`: migracion historica DJ PRO/visitas.

## Dependencias PHP

No hay Composer ni dependencias externas obligatorias.

Extensiones necesarias:

- `PDO`
- `pdo_mysql`
- `mbstring`
- `fileinfo`
- `gd` si se procesan imagenes en el hosting
- `zip` recomendado para descargas ZIP de albumes/superpacks
- `curl` recomendado para APIs externas si se reactivan integraciones

## Version Recomendada

- PHP 8.1 u 8.2 en Namecheap/cPanel.
- MySQL 8 o MariaDB 10.3+.
- Charset/collation: `utf8mb4` / `utf8mb4_unicode_ci`.

## Riesgos de Migracion

- No subir `includes/config.local.php` real con credenciales.
- No reemplazar carpetas `uploads/` en hosting si ya contienen contenido.
- No importar dumps antiguos con `DROP TABLE` sobre una base en uso.
- Verificar que `djs.id` sea `PRIMARY KEY AUTO_INCREMENT` y que `djs.name` sea unico.
- Confirmar que URLs de MP3 sean absolutas o rutas relativas compatibles con `CDN_BASE_URL`.
- Las descargas ZIP generadas por PHP pueden requerir `ZipArchive` y memoria suficiente.
- El reporte PDF usa generacion nativa simple, no FPDF/TCPDF/Dompdf.
- El cron de correos usa `mail()` por defecto; si el hosting lo bloquea, configurar SMTP o adaptar a PHPMailer.

## Estado de Modulos Sensibles

- Login/admin: probado por sintaxis, no se renombro flujo.
- Estadisticas plays/downloads: siguen usando tabla `statistics`.
- Visitas: tabla separada `site_visits`, con IP hasheada.
- DJ PRO: usa campos en `djs` y tabla `dj_payments`.
- Radio: configuracion desde tablas/settings existentes.
- Banners/videos/API: conservados.
