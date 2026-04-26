# Renovaciones DJ PRO

Plan activo:

- DJ PRO: USD 10 mensual.

## Campos Usados

Tabla `djs`:

- `plan`: `free`, `pro`, `founder`
- `subscription_status`: `active`, `expired`, `pending`, `cancelled`
- `subscription_start`
- `subscription_end`
- `email`
- `instagram`
- `biography`
- `profile_photo`
- `slug`
- `is_featured`
- `priority`
- `last_notice_7_days`
- `last_notice_3_days`
- `last_notice_1_day`
- `last_notice_expired`

Tabla `dj_payments`:

- `dj_id`
- `amount`
- `payment_method`
- `reference_number`
- `proof_image`
- `notes`
- `payment_date`
- `created_by`

## Administracion

Entrar a:

```text
/admin/dj_pro.php
```

Acciones disponibles:

- Activar PRO por 30 dias.
- Extender 30 dias.
- Registrar pago Yappy.
- Ver vencimiento y dias restantes.
- Marcar como Fundador.
- Pausar/cancelar.
- Volver a FREE sin borrar datos.
- Definir destacado y prioridad.

## Comportamiento al Vencer

Cuando un DJ PRO vence:

- No se borra el DJ.
- No se borran sus mixes.
- El perfil queda en modo basico.
- Se limita la visibilidad de mixes.
- Se ocultan beneficios avanzados.
- Se quita destacado si el admin lo define.

## Cron en Namecheap/cPanel

Comando recomendado:

```bash
php /home/USUARIO/public_html/cron/check_subscriptions.php
```

Frecuencia sugerida:

```text
1 vez al dia, por ejemplo 08:00 AM.
```

## Avisos

El cron intenta enviar avisos:

- 7 dias antes.
- 3 dias antes.
- 1 dia antes.
- Dia vencido.

No duplica avisos porque guarda fecha en columnas `last_notice_*`.

## SMTP

Actualmente `cron/check_subscriptions.php` usa `mail()` nativo para maxima compatibilidad sin librerias.

Si el hosting bloquea `mail()`, configurar SMTP en `includes/config.local.php` y adaptar el envio a PHPMailer o al proveedor SMTP del dominio. No subir claves SMTP al repositorio.
