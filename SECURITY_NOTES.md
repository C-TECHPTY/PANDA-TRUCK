# Security Notes

## Puntos revisados

- La conexion usa PDO en `includes/config.php`.
- La configuracion sensible debe ir en variables de entorno o `includes/config.local.php`.
- No subir claves SMTP, Backblaze, BunnyCDN ni contrasenas a GitHub.
- Las acciones admin deben requerir sesion y rol.

## Riesgos actuales

- No hay CSRF general en acciones sensibles.
- Hay APIs con `Access-Control-Allow-Origin: *`.
- Algunas APIs no tienen control de rol uniforme.
- Hay endpoints duplicados para estadisticas.
- Algunos metodos llamados por APIs no existen todavia en `Auth`.
- Hay interpolacion SQL en algunas zonas del dashboard.
- Los uploads requieren validacion estricta de tipo, extension, tamano y ruta.
- Los errores no deben mostrarse en produccion.

## Recomendaciones

- Agregar tokens CSRF por formulario/API sensible.
- Unificar validacion de roles.
- Usar prepared statements en todo SQL dinamico.
- Validar uploads con MIME real y extension permitida.
- Guardar logs tecnicos fuera de pantalla.
- Mantener `display_errors = 0` en hosting.
