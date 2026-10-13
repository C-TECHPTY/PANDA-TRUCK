# Subir musica y portadas desde el dashboard

Estado inicial: desactivado. El formulario sigue permitiendo pegar enlaces y no cambia su guardado, notificaciones ni reproduccion con BunnyCDN.

## Comportamiento

Solo usuarios activos con rol `admin` o `superadmin` pueden usar la API. El servidor vuelve a comprobar el rol en la base en cada solicitud; DJs, viewers y moderadores no pueden subir, aunque invoquen la API directamente.

- Musica: bucket `DJIMMY-PANDA`, prefijo `MIXES/`.
- Imagenes: el mismo bucket, prefijo `PORTADAS/`.
- Controles en Agregar/Editar Mix; portada de album y audio de canciones de album.
- Archivos pequenos se suben directamente mediante PUT firmado; archivos de 16 MiB o mas se suben por partes.
- Los bytes nunca pasan por PHP/cPanel. PHP solo autoriza y confirma operaciones.
- Progreso, reintentos y cancelacion. Cada objeto recibe un nombre unico para evitar sobrescrituras.
- Al confirmar el tamano y tipo en Backblaze, se rellena el campo y aparece Friendly URL + Copiar enlace.
- La publicacion sigue requiriendo Guardar. No se crea un mix ni se envia una notificacion al subir solamente un archivo.
- Audio: MP3, M4A, WAV, OGG, FLAC y AAC. Portadas: JPG, PNG, WEBP y GIF. La reproduccion de formatos distintos de MP3 depende del navegador; no se convierte audio.
- Admite hasta 10 TB por archivo y 10.000 partes. No se afirma que sea ilimitado ni que se haya probado una transferencia de ese tamano.

## Configurar en Namecheap/cPanel

1. Guarda una copia de los archivos actuales que vas a sustituir. Prueba primero en un subdominio de pruebas que use una copia de la base. No importes SQL ni sustituyas la base de produccion: esta funcionalidad no necesita migraciones.
2. PHP debe ser de 64 bits y tener cURL, SimpleXML, hash y session (PHP 7.4 o superior; validado localmente con PHP 8.2).
3. En Backblaze > Application Keys crea una clave limitada a `DJIMMY-PANDA`. Para esta implementacion se necesitan `writeFiles` y `readFiles` (subir, listar partes, cancelar partes y verificar el archivo). No necesita administrar buckets, listar todos los buckets ni borrar versiones de archivos publicados. La consola puede ofrecer Read and Write como grupo; para conceder exactamente las dos capacidades usa la API de creacion de claves.
4. Deja el prefijo de la clave vacio porque esta integracion escribe en dos prefijos distintos. El servidor fija las rutas: el navegador no puede escoger otro bucket ni carpeta. No uses la master application key ni entregues esta clave a los usuarios.
5. Copia `includes/backblaze.local.example.php` como `includes/backblaze.local.php`, solo en el hosting. Completa `B2_KEY_ID` y `B2_APPLICATION_KEY`. Confirma `B2_REGION` en Backblaze; la captura y la configuracion actual corresponden a `us-east-005`. Activa `B2_UPLOAD_ENABLED => true` cuando este preparado. El archivo local esta ignorado por Git y protegido por `includes/.htaccess`.
6. Verifica que el bucket sigue siendo publico para que el Friendly URL reproduzca musica y muestre portadas sin credenciales. Mantenerlo publico no permite subir sin autorizacion.
7. Anade una regla CORS para las subidas, conservando todas las reglas actuales de reproduccion/CDN. Usa solo los origenes exactos del dashboard; anade `www` o el subdominio de pruebas si realmente se usan.

Ejemplo de regla B2 (adaptar los dominios):

```json
{
  "corsRuleName": "pandaDashboardUploads",
  "allowedOrigins": ["https://pandatruckreloaded.com"],
  "allowedHeaders": ["content-type"],
  "allowedOperations": ["s3_put"],
  "exposeHeaders": ["ETag"],
  "maxAgeSeconds": 3600
}
```

8. En el bucket configura una regla de ciclo de vida para cancelar archivos grandes sin terminar despues de varios dias (por ejemplo 7), preservando las reglas actuales y los archivos publicados. Cancelar desde el dashboard aborta el multipart. Cerrar la pestana, perder la sesion o una interrupcion del servidor puede dejar partes pendientes.

## Archivos a desplegar

- `dashboard.php` (solo se anade la carga del nuevo script para administradores).
- `assets/js/backblaze-upload.js`.
- `api/backblaze_upload.php`.
- `includes/backblaze_upload.php`.
- `includes/backblaze.local.example.php`.
- `includes/.htaccess` (si hay una regla existente en produccion, incorporar el bloque sin reemplazarla).
- `includes/backblaze.local.php`: configuracion creada manualmente, nunca incluida en Git.

No hace falta reemplazar `save_mix.php`, `upload_image.php`, `cdn.php`, el reproductor ni la base. Las subidas anteriores de logos/avatares siguen funcionando como antes.

## Prueba antes de publicar

1. Administrador: subir un MP3 pequeno y una portada; copiar Friendly URL y abrir ambos enlaces.
2. Subir un mix de mas de 16 MiB y confirmar progreso/resultado multipart.
3. Guardar el mix; comprobar musica y portada desde la web publica y descarga/CDN.
4. Editar un mix existente pegando enlaces manualmente; comprobar que sigue funcionando.
5. Superadministrador: repetir subida.
6. DJ: comprobar que no aparecen controles y que POST directo a la API devuelve 403.
7. Cancelar una subida grande, cerrar el modal y simular fallo de conexion; no debe cambiar el enlace anterior ni guardar un mix mientras hay una subida pendiente.
8. Verificar que HTTP a `/includes/backblaze.local.php` y `/includes/config.local.php` devuelve 403.
9. Desactivar `B2_UPLOAD_ENABLED`: se bloquean nuevas subidas, pero se conservan el guardado manual y los archivos ya publicados.

## Pruebas sin credenciales

Desde la raiz:

```text
php -l includes/backblaze_upload.php
php -l api/backblaze_upload.php
php -l dashboard.php
php tests/backblaze_upload_checks.php
node --check assets/js/backblaze-upload.js
node tests/backblaze_signature_check.cjs
node tests/backblaze_api_checks.cjs
node tests/backblaze_ui_checks.cjs
```

La prueba DOM requiere `jsdom` disponible en Node (por ejemplo instalado en una carpeta de herramientas de pruebas, fuera del paquete de produccion). La prueba de API inicia un servidor PHP local con una base simulada y lo termina despues. Ninguna prueba usa claves reales ni archivos del bucket.

Se verifican permisos, CSRF, usuario desactivado, solicitudes invalidas, nombres/rutas seguros, tamano, partes faltantes o desordenadas, firma SigV4 con dos implementaciones independientes, compatibilidad de BunnyCDN, controles del formulario, subidas simultaneas, copiar enlace, bloqueo temporal de Guardar, reintentos y cancelacion.

## Limites operativos

- Los enlaces firmados caducan en una hora y permiten reintentar una parte mientras la sesion siga activa.
- Mantener abierta la pestana y la sesion. No hay reanudacion persistente despues de recargar/cerrar la pestana.
- Una subida completada cuyo formulario no se guarda sigue siendo un archivo en Backblaze. Cancelar una subida pequena despues de que PUT haya terminado puede dejar el objeto completo. Se conserva para evitar borrar archivos publicados automaticamente; revisar estos archivos manualmente cuando corresponda.
- La validacion comprueba extension, tipo almacenado y tamano. No realiza antivirus ni analiza el contenido del audio; los cargadores son administradores autorizados.
- No se han ejecutado transferencias reales contra Backblaze sin la configuracion del propietario. Las pruebas locales no sustituyen la prueba del bucket/CORS en el hosting.

## Referencias oficiales

- [Application Keys](https://www.backblaze.com/docs/cloud-storage-application-keys)
- [S3 browser upload example](https://github.com/backblaze-b2-samples/b2-browser-upload)
- [CORS](https://www.backblaze.com/docs/cloud-storage-cross-origin-resource-sharing-rules)
- [Large files](https://www.backblaze.com/docs/cloud-storage-large-files)
