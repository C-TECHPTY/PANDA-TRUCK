# Diagnostico de Cambios - PANDA-TRUCK

Fecha: 2026-04-25
Entorno revisado: XAMPP / proyecto local `c:\xampp\htdocs\panda-truck-v2`
Rama Git actual: `codex/import-panda-truck-v2`

## Estado General

El proyecto es una aplicacion PHP/MySQL sin framework, con paginas publicas, dashboard administrativo, APIs JSON, reproductores, gestion de mixes, videos, banners, eventos, albumes, superpacks y estadisticas.

La conexion principal a base de datos esta centralizada en `includes/config.php` mediante `getDB()`. La autenticacion esta en `includes/auth.php` y el dashboard principal es un archivo grande: `dashboard.php`.

No se deben hacer cambios directos grandes sobre `dashboard.php` sin respaldarlo primero, porque concentra muchas secciones del admin, consultas iniciales, HTML, CSS y JavaScript.

## Archivos Principales Encontrados

- `index.php`: pagina principal publica, radio, hero video, mixes recientes, banners, estadisticas visibles y llamadas AJAX.
- `dashboard.php`: panel administrativo principal con gestion de mixes, DJs, videos, eventos, banners, usuarios, configuracion, mantenimiento y estadisticas.
- `login.php`: inicio de sesion.
- `logout.php`: cierre de sesion.
- `mixes.php`: listado publico de mixes.
- `albumes.php`: listado publico de albumes y canciones.
- `superpacks.php`: pagina publica de superpacks.
- `lista_djs.php`: listado publico/API visual de DJs.
- `GuíaDJs.php`: guia publica para DJs.
- `guia_admin.php`: guia administrativa.
- `maintenance.php`: pagina de mantenimiento.
- `player/index.php`: reproductor publico de audio.
- `player/video.php`: reproductor publico de video.
- `player/download.php`: endpoint/compatibilidad de descarga desde carpeta `player`.

## Archivos de Login y Sesion

- `login.php`
  - Hace login consultando `users`.
  - Usa `password_verify()`.
  - Actualiza `last_login` y `last_ip`.
  - Redirige a `dashboard.php`.

- `logout.php`
  - Destruye sesion y sale del sistema.

- `includes/auth.php`
  - Clase `Auth`.
  - Metodos clave: `login`, `logout`, `isLoggedIn`, `requireLogin`, `requireAdmin`, `requireSuperAdmin`, `isSuperAdmin`, `isAdmin`, `logActivity`.
  - Tambien carga estadisticas basicas del sistema y listas de DJs/usuarios.

- `includes/config.php`
  - Inicia sesion si hace falta.
  - Define constantes de base de datos desde variables de entorno o `includes/config.local.php`.
  - Define `getDB()`.

Riesgo: algunos APIs llaman metodos que no existen actualmente en `Auth`, por ejemplo `requireRole()` y posiblemente firmas inconsistentes de `logActivity`. Esto debe corregirse con cuidado para no romper endpoints existentes.

## Archivos del Dashboard

- `dashboard.php`
  - Archivo critico.
  - Requiere `includes/config.php` e `includes/auth.php`.
  - Usa `$auth->requireLogin()`.
  - Carga datos iniciales de:
    - `mixes`
    - `djs`
    - `videos`
    - `events`
    - `banners`
    - `users`
    - `albumes`
    - `system_settings`
    - `player_config`
    - `statistics`
  - Incluye Chart.js por CDN.
  - Contiene logica frontend extensa para formularios y fetch hacia APIs.

- `dashboard2.php`
  - Parece copia/variante anterior del dashboard.
  - No debe modificarse inicialmente salvo que se confirme que se usa en produccion.

- `admin/functions.php`
  - Funciones administrativas auxiliares para usuarios, logs, cache y backups.
  - Contiene operaciones sensibles como backup y borrado de usuarios.

Riesgo: `dashboard.php` contiene consultas interpoladas en algunos puntos, por ejemplo conteo de mixes por DJ dentro de un loop. Conviene refactorizar con consultas agregadas/preparadas cuando se toque esa zona.

## Archivos de Conexion a Base de Datos

- `includes/config.php`
  - Conexion principal actual y recomendada.
  - Usa PDO.
  - Configuracion local segura mediante `includes/config.local.php`.

- `includes/db.php`
  - Clase `Database` con credenciales locales antiguas (`panda_truck_v2`, `root`, password vacio).
  - Parece legado. Antes de tocarlo, confirmar si algun archivo lo incluye.

- `includes/config.local.example.php`
  - Plantilla de configuracion local.
  - No contiene claves reales.

Riesgo: hay dos estilos de conexion (`getDB()` y clase `Database`). La implementacion nueva debe usar `getDB()` para mantenerse alineada.

## Archivos de Mixes

Paginas:
- `mixes.php`
- `player/index.php`
- `dj/perfil.php`
- `dj/superpack.php`
- `index.php`

APIs:
- `api/save_mix.php`
- `api/get_mix.php`
- `api/delete_mix.php`
- `api/download_mix.php`
- `api/download.php`
- `api/descargar_zip.php`
- `api/download_superpack.php`
- `api/update_stats.php`
- `api/actualizar_estadisticas.php`
- `api/sync_stats.php`
- `api/get_stats.php`
- `api/get_stats2.php`

Tablas relacionadas:
- `mixes`
- `statistics`
- `user_activity`

Notas:
- `api/download_mix.php` ya fue optimizado para contar descarga y redirigir al archivo remoto/CDN sin cargar MP3 en RAM.
- `api/download.php` apunta a `download_mix.php` como compatibilidad.
- Los ZIPs (`descargar_zip`, `download_superpack`, `download_album`) usan temporales para reducir RAM.

Riesgo: las reproducciones se cuentan cada vez que dispara `play`; falta control anti-duplicado por sesion/IP/tiempo para evitar inflar plays.

## Archivos de DJs

Paginas:
- `lista_djs.php`
- `dj/perfil.php`
- `dj/perfil02.php`
- `dj/superpack.php`

APIs:
- `api/save_dj.php`
- `api/get_dj.php`
- `api/get_all_djs.php`
- `api/delete_dj.php`
- `api/get_superpacks.php`
- `api/toggle_superpack.php`
- `api/update_superpack.php`

Tabla relacionada:
- `djs`

Columnas actuales principales en `djs`:
- `id`
- `name`
- `genre`
- `city`
- `bio`
- `avatar`
- `socials`
- `mixes`
- `videos`
- `featured_week`
- `active`
- `created_at`
- `updated_at`

Riesgo: los mixes se relacionan con DJs por texto (`mixes.dj = djs.name`), no por `dj_id`. Esto hace delicado cambiar nombres de DJs. Para DJ PRO conviene agregar campos a `djs` sin romper esa relacion existente, y mas adelante evaluar migracion gradual a `dj_id`.

## Archivos de Estadisticas

APIs:
- `api/get_stats.php`
- `api/get_stats2.php`
- `api/update_stats.php`
- `api/actualizar_estadisticas.php`
- `api/sync_stats.php`
- `api/estadisticas.php`

Frontend:
- `index.php`
- `dashboard.php`
- `assets/js/player.js`
- `player/index.php`
- `dj/perfil.php`
- `dj/superpack.php`

Tablas actuales:
- `statistics`
- `user_activity`
- `activity_logs`

Columnas actuales en `statistics`:
- `id`
- `item_id`
- `item_type` (`mix`, `video`)
- `plays`
- `downloads`
- `last_updated`

Notas:
- `statistics` guarda acumulados por item.
- `user_activity` existe, pero su estructura actual no es suficiente para visitas de sitio como se pide en la nueva plataforma.
- Hay que crear `site_visits` separada para no mezclar visitas publicas con actividad de reproduccion/descarga.

Riesgo: algunas paginas muestran `mixes.plays` / `mixes.downloads`, mientras otras usan `statistics`. Mantener compatibilidad actual y sincronizacion.

## Archivos API

APIs de contenido:
- `api/save_mix.php`
- `api/save_dj.php`
- `api/save_video.php`
- `api/save_event.php`
- `api/save_banner.php`
- `api/save_album.php`
- `api/save_cancion.php`

APIs de lectura:
- `api/get_mix.php`
- `api/get_dj.php`
- `api/get_all_djs.php`
- `api/get_videos.php`
- `api/get_video.php`
- `api/get_event.php`
- `api/get_banners.php`
- `api/get_banners_admin.php`
- `api/get_album.php`
- `api/get_albumes.php`
- `api/get_settings.php`
- `api/get_stats.php`
- `api/get_superpacks.php`

APIs de borrado/estado:
- `api/delete_mix.php`
- `api/delete_dj.php`
- `api/delete_video.php`
- `api/delete_event.php`
- `api/delete_banner.php`
- `api/delete_album.php`
- `api/delete_cancion.php`
- `api/delete_user.php`
- `api/set_maintenance.php`
- `api/toggle_superpack.php`

APIs de descargas/estadisticas:
- `api/download_mix.php`
- `api/download.php`
- `api/download_album.php`
- `api/download_superpack.php`
- `api/descargar_zip.php`
- `api/download_video.php`
- `api/update_stats.php`
- `api/actualizar_estadisticas.php`
- `api/sync_stats.php`

APIs de sistema:
- `api/backup.php`
- `api/clear_cache.php`
- `api/optimize.php`
- `api/upload_image.php`
- `api/check_maintenance.php`
- `api/live_stream_config.php`
- `api/get_bunny_video.php`

Riesgo: varios APIs tienen `Access-Control-Allow-Origin: *`, no todos verifican admin/login, y no hay CSRF. Las acciones sensibles deben endurecerse por fases, no de golpe.

## Base de Datos Detectada

Archivo principal mas completo:
- `pandqgxl_panda_truck_db.sql`

Archivo secundario:
- `panda_truck_v2.sql`

Scripts de estructura:
- `sql/estructura.sql`
- `sql/update_v2.sql`

Tablas principales detectadas:
- `activity_logs`
- `albumes`
- `banners`
- `canciones`
- `configuration`
- `djs`
- `events`
- `mixes`
- `player_config`
- `playlists`
- `radio_config`
- `statistics`
- `sync_cache`
- `system_settings`
- `users`
- `user_activity`
- `videos`
- `weekly_reports`

Vistas/procedimientos:
- `vw_dj_stats`
- `vw_super_packs`
- `sp_generate_weekly_report`
- `sp_update_statistics`

Nota: `pandqgxl_panda_truck_db.sql` parece mas actualizado que `panda_truck_v2.sql`, porque incluye albumes, canciones, playlists, sync_cache y weekly_reports.

## Posibles Riesgos Antes de Modificar

1. `dashboard.php` es monolitico y mezcla PHP, HTML, CSS y JS. Cambios grandes ahi pueden romper varias secciones.
2. La relacion DJ/mix usa nombre de DJ como texto. Cambiar `djs.name` puede dejar mixes desconectados visualmente.
3. Algunas APIs no protegen roles de forma uniforme.
4. Algunas funciones referenciadas no parecen existir en `Auth`, por ejemplo `requireRole()` o `createUser()` desde `api/create_user.php`.
5. Hay duplicidad de endpoints de estadisticas (`update_stats.php`, `actualizar_estadisticas.php`, `sync_stats.php`, `get_stats.php`, `get_stats2.php`).
6. La tabla `user_activity` actual no debe reutilizarse como tabla principal de visitas publicas; mejor crear `site_visits`.
7. Los archivos SQL tienen datos reales/semillas. Las migraciones nuevas deben ser `ALTER TABLE IF NOT EXISTS` compatible o usar comprobaciones de columnas.
8. No existe Composer visible. Para PDF conviene FPDF/TCPDF local o documentar instalacion antes de usar Dompdf.
9. No existe configuracion SMTP central detectada. El cron de correos debe crearse con archivo local/variables de entorno, sin claves en Git.
10. Hay archivos ignorados/locales (`BACKUP2`, zips, pruebas, logs) que no deben subirse a hosting como parte de la implementacion.
11. Algunas descargas ZIP siguen consumiendo CPU/tiempo aunque ya no carguen MP3 completos en RAM. Para alto trafico convendra pre-generar ZIPs o usar CDN.
12. Hay encoding mixto visible en textos con caracteres acentuados. Evitar ediciones masivas que cambien codificacion accidentalmente.

## Orden Recomendado de Implementacion

1. Crear respaldo de archivos que se modifiquen:
   - `dashboard.php`
   - `index.php`
   - `dj/perfil.php`
   - `dj/superpack.php`
   - `api/save_dj.php`
   - `api/get_all_djs.php`
   - `api/get_stats.php`
   - `includes/auth.php`
   - `includes/config.php` si se toca configuracion

2. Crear migracion SQL:
   - `migrations/upgrade_dj_pro_stats.sql`
   - Agregar campos DJ PRO a `djs`.
   - Crear `site_visits`.
   - Crear `dj_payments`.
   - Crear indices.

3. Crear documentacion base:
   - `PLANES_DJ_PRO.md`
   - `DOCUMENTACION_ESTADISTICAS.md`
   - `DOCUMENTACION_RENOVACIONES_DJ_PRO.md`
   - `CONFIGURACION_CDN_AUDIO.md`
   - `SECURITY_NOTES.md`

4. Crear helpers nuevos sin tocar dashboard todavia:
   - `includes/track_visit.php`
   - helpers para DJ PRO/subscripciones si aplica.

5. Crear APIs administrativas nuevas para DJ PRO:
   - activar PRO
   - extender 30 dias
   - registrar pago Yappy
   - pausar/cancelar/free
   - subir foto/perfil

6. Integrar una seccion nueva en `dashboard.php`, con cambios pequenos y localizados.

7. Crear perfil publico nuevo compatible:
   - `dj.php?slug=...` o una pagina nueva equivalente.
   - No reemplazar de entrada `dj/perfil.php` hasta validar.

8. Agregar tracking de visitas solo en paginas publicas:
   - `index.php`
   - `mixes.php`
   - `albumes.php`
   - `superpacks.php`
   - `lista_djs.php`
   - perfil publico nuevo

9. Mejorar estadisticas de plays con deduplicacion por sesion/IP hash/ventana de tiempo.

10. Crear cron de renovaciones:
    - `cron/check_subscriptions.php`
    - Usar mail/SMTP configurable sin secretos en Git.

11. Crear reporte PDF:
    - `admin/reports/generate_partner_report.php`
    - Elegir libreria compatible sin romper hosting.

12. Probar en XAMPP antes de subir:
    - login
    - dashboard
    - pagina principal
    - radio
    - mixes
    - videos
    - banners
    - eventos
    - usuarios
    - estadisticas
    - DJ PRO
    - visitas
    - PDF
    - cron manual

## Archivos Criticos que No Deben Romperse

- `includes/config.php`
- `includes/auth.php`
- `login.php`
- `dashboard.php`
- `index.php`
- `api/save_mix.php`
- `api/save_dj.php`
- `api/save_video.php`
- `api/save_banner.php`
- `api/save_event.php`
- `api/get_stats.php`
- `api/download_mix.php`
- `player/index.php`
- `assets/js/player.js`

## Respaldo Recomendado Antes de Fase 2+

Crear carpeta:

```text
backups/codex_dj_pro_YYYYMMDD_HHMMSS/
```

Copiar ahi cada archivo antes de modificarlo. No subir esa carpeta al repositorio ni al hosting.

## Confirmacion de Alcance Actual

En esta fase solo se creo este archivo de diagnostico:

- `DIAGNOSTICO_CAMBIOS.md`

No se modifico la logica del sistema, no se altero base de datos y no se tocaron archivos de produccion.
