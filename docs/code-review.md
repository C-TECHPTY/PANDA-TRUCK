# Observaciones de revisión de código

## Seguridad de credenciales y autenticación
- `config/database.php` define el host, base de datos, usuario y contraseña directamente en el código fuente. Esto obliga a versionar credenciales sensibles y dificulta el despliegue en múltiples entornos. Conviene moverlas a variables de entorno (por ejemplo mediante `getenv`) o a un fichero no versionado y leerlas de forma segura antes de instanciar `PDO`. 【F:config/database.php†L3-L21】
- `dashboard.html` expone el usuario y la contraseña del panel directamente en el JavaScript del cliente y valida el inicio de sesión en el navegador. Cualquier visitante puede inspeccionar el código y obtener las credenciales, por lo que el panel no está realmente protegido. Se recomienda implementar autenticación en el servidor (por ejemplo con PHP) y emitir sesiones o tokens firmados en lugar de validar en el cliente. 【F:dashboard.html†L878-L986】
- El dashboard persiste el estado de autenticación en `localStorage` sin ninguna protección adicional ni rotación de tokens. En caso de XSS, un atacante puede robar el token y reutilizarlo durante 24 h. Usar cookies `httpOnly` o reducir drásticamente la superficie (por ejemplo, mantener la sesión en el servidor) mitigaría este riesgo. 【F:dashboard.html†L965-L1017】

## Integridad de datos y robustez backend
- `api/guardar.php` elimina por completo las tablas (`DELETE FROM ...`) antes de insertar los nuevos registros, sin envolver la operación en una transacción. Si se interrumpe el proceso (error de red, datos inválidos) la tabla queda vacía. Encapsular los borrados/inserciones dentro de `BEGIN/COMMIT` y validar la entrada antes de eliminar los datos evitaría pérdidas. También conviene preferir `INSERT ... ON DUPLICATE KEY UPDATE` para sincronizaciones incrementales. 【F:api/guardar.php†L66-L225】
- En la función de banners el acceso `$banner['active'] ? 1 : 0` asume que la clave siempre existe. Si el cliente omite ese campo, PHP emitirá un `Undefined index`. Validar con `isset($banner['active'])` o proporcionar un valor por defecto (`!empty($banner['active'])`) previene avisos y estados incoherentes. 【F:api/guardar.php†L203-L217】
- `backup.php` intenta incluir `api/config.php`, pero dicho archivo no existe en el repositorio. Esto provoca un error fatal cuando se llama al script, impidiendo generar respaldos. Conviene crear el archivo requerido o reutilizar `config/database.php`. 【F:backup.php†L6-L8】

## Superficie de ataque en el cliente
- `applyCustomization` inserta `CUSTOMIZATION.siteTitle` en el DOM mediante `innerHTML`, y estos datos provienen del almacenamiento local sincronizado con el panel. Si alguien inyecta HTML o scripts en esos campos (por ejemplo desde la base de datos), se produciría XSS en la página pública. Es preferible usar `textContent` o sanitizar los valores antes de asignarlos. 【F:index.html†L770-L799】

## Otras consideraciones
- Varias respuestas JSON de `api/cargar.php`/`api/guardar.php` se exponen con `Access-Control-Allow-Origin: *`, lo que permite que cualquier sitio web invoque la API desde el navegador. Si el panel queda detrás de autenticación real, conviene restringir CORS al dominio legítimo. 【F:api/cargar.php†L2-L35】【F:api/guardar.php†L2-L39】
- El script `backup.php` permite descargar cualquier fichero dentro de la carpeta `backups` sin autenticación adicional. Una vez se implemente seguridad en el panel, conviene proteger también estas rutas y validar mejor el parámetro `file`. 【F:backup.php†L3-L137】

Estas acciones mejorarían de forma considerable la seguridad y la resiliencia de la aplicación.
