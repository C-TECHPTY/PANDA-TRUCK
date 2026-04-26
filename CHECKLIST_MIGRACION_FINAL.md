# Checklist Migracion Final

Fecha: 2026-04-26

## Diagnostico

- OK - Estructura del proyecto revisada.
- OK - Archivos publicos identificados.
- OK - Archivos admin/API identificados.
- OK - SQL historicos identificados.
- OK - Riesgos documentados en `MIGRACION_DIAGNOSTICO.md`.

## Respaldo

- OK - Carpeta `_backup_migracion/` creada.
- OK - Archivos criticos copiados.
- OK - Backup excluido del paquete final y de Git.

## SQL

- OK - `sql/PRODUCCION_COMPLETA_IMPORTAR.sql` creado.
- OK - `sql/MIGRACION_SOLO_CAMBIOS.sql` creado.
- OK - Importacion probada en una base temporal local.

## Configuracion

- OK - `includes/config.local.example.php` actualizado sin credenciales reales.
- OK - `includes/config.php` lee `CDN_BASE_URL` y SMTP desde config local/env.
- OK - `.gitignore` excluye config local, backups, build, logs y dumps sensibles.

## CDN / Audio

- OK - `resolveMediaUrl()` agregado.
- OK - Player usa `preload = none`.
- OK - `api/download_mix.php` redirige a storage/CDN, no transmite MP3.
- OK - `mixes.php` descarga via API para contar estadisticas.
- OK - Documentacion CDN actualizada.

## Visitas

- OK - `includes/track_visit.php` existe.
- OK - Tabla `site_visits` incluida en SQL.
- OK - Publicas trackeadas: home, mixes, lista DJs, albumes, superpacks, DJ profile.
- OK - Admin/API/assets excluidos por `shouldSkipVisitTracking()`.
- OK - Dashboard muestra totales, hoy, semana, mes, paginas y DJs.

## DJ PRO

- OK - Campos de membresia incluidos en SQL.
- OK - `admin/dj_pro.php` permite activar, extender, registrar Yappy, pausar, FREE y destacado.
- OK - Perfil DJ limita visibilidad cuando no es PRO/fundador.
- OK - No borra DJs ni mixes al vencer.

## Correos / Cron

- OK - `cron/check_subscriptions.php` existe.
- OK - Avisos 7, 3, 1 y vencido implementados.
- OK - Documentacion de cron creada.
- PENDIENTE - Confirmar si `mail()` funciona en Namecheap o adaptar a SMTP/PHPMailer.

## Reporte PDF

- OK - `admin/reports/generate_partner_report.php` existe.
- OK - Boton disponible desde `admin/dj_pro.php`.
- OK - No requiere libreria externa.
- OK - Sintaxis del generador PDF validada.
- PENDIENTE - Probar descarga PDF en navegador luego de subir.

## Pruebas Locales

- OK - Sintaxis PHP validada en archivos principales.
- OK - Home responde en XAMPP.
- OK - `index.php` sin error de sintaxis.
- OK - Home local responde HTTP 200.
- OK - Sintaxis de login, dashboard, paginas publicas, cron, DJ PRO y reporte validada.
- PENDIENTE - Prueba manual completa de login y acciones admin en navegador.
- PENDIENTE - Prueba real de cron en cPanel.

## Paquete Hosting

- OK - `README_HOSTING.md` creado.
- OK - Crear `build/panda-truck-hosting/`.
- OK - Crear `build/PANDA_TRUCK_HOSTING_READY.zip`.
