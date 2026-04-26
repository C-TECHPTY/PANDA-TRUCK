# Guia de Subida a Namecheap/cPanel

Paquete final:

```text
build/PANDA_TRUCK_HOSTING_READY.zip
```

## 1. Crear Base de Datos

1. Entrar a cPanel.
2. Abrir **MySQL Databases**.
3. Crear una base de datos.
4. Crear un usuario MySQL.
5. Asignar el usuario a la base con **ALL PRIVILEGES**.

## 2. Importar SQL

Entrar a phpMyAdmin y seleccionar la base creada.

Instalacion nueva:

```text
sql/PRODUCCION_COMPLETA_IMPORTAR.sql
```

Actualizacion de hosting existente:

```text
sql/MIGRACION_SOLO_CAMBIOS.sql
```

No importar dumps historicos con `DROP TABLE` sobre una base en uso.

## 3. Subir Archivos

1. Ir a **File Manager**.
2. Abrir `public_html` o la carpeta del dominio.
3. Subir `PANDA_TRUCK_HOSTING_READY.zip`.
4. Extraer el ZIP.
5. Confirmar que `index.php`, `dashboard.php`, `api/`, `includes/`, `assets/`, `player/`, `admin/`, `cron/` queden en la raiz del dominio.

## 4. Configuracion Local del Hosting

Copiar:

```text
includes/config.local.example.php
```

como:

```text
includes/config.local.php
```

Editar valores:

```php
'DB_HOST' => 'localhost',
'DB_NAME' => 'nombre_base',
'DB_USER' => 'usuario_base',
'DB_PASS' => 'clave_base',
'BASE_URL' => 'https://tudominio.com/',
'CDN_BASE_URL' => 'https://panda-truck.b-cdn.net/',
```

Si se usa SMTP, completar:

```php
'SMTP_HOST' => '',
'SMTP_USER' => '',
'SMTP_PASS' => '',
'SMTP_PORT' => 587,
'SMTP_SECURE' => 'tls',
```

No subir `config.local.php` a GitHub.

## 5. Configurar CDN

Ver:

```text
CONFIGURACION_CDN_AUDIO.md
```

Los MP3 deben estar en Backblaze/BunnyCDN. En la base, nuevos mixes pueden guardar rutas relativas como:

```text
MIXES/archivo.mp3
```

El sistema las convierte con `CDN_BASE_URL`.

## 6. Cron Job

En cPanel > Cron Jobs:

```bash
php /home/USUARIO/public_html/cron/check_subscriptions.php
```

Frecuencia sugerida: diaria.

## 7. Probar Despues de Subir

- `/`
- `/login.php`
- `/dashboard.php`
- `/mixes.php`
- `/lista_djs.php`
- `/albumes.php`
- `/superpacks.php`
- `/dj.php?slug=dj-irvin-algarete`
- `/admin/dj_pro.php`
- `/admin/reports/generate_partner_report.php`
- reproduccion de MP3 en `/player/index.php?id=ID`
- contador de visitas en dashboard
- banners en home
- radio en home

## Archivos a Reemplazar en Hosting

Reemplazar estos desde el ZIP:

- `admin/`
- `api/`
- `assets/`
- `cron/`
- `dj/`
- `includes/` excepto `includes/config.local.php`
- `player/`
- `sql/`
- `.htaccess`
- `index.php`
- `login.php`
- `logout.php`
- `dashboard.php`
- `dashboard2.php`
- `lista_djs.php`
- `mixes.php`
- `albumes.php`
- `superpacks.php`
- `maintenance.php`
- `GuíaDJs.php`
- `guia_admin.php`
- `dj.php`
- `dj-pro.php`

## Archivos que NO Debo Tocar

- `includes/config.local.php` real del hosting.
- credenciales SMTP reales.
- carpetas `uploads/` si ya tienen contenido.
- archivos MP3 si estan externos en Backblaze/BunnyCDN.
- backups del hosting.
- bases de datos existentes antes de tener respaldo.

## Seguridad

- Cambiar la contrasena inicial de `superadmin` al entrar.
- No guardar credenciales reales en archivos versionados.
- Mantener `includes/config.local.php` fuera de Git.
- Hacer backup de base de datos antes de importar `MIGRACION_SOLO_CAMBIOS.sql`.
