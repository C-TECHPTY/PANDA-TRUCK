# Renovaciones DJ PRO

## Estados

- `pending`: sin pago activo confirmado.
- `active`: PRO vigente.
- `expired`: vencido.
- `cancelled`: cancelado manualmente.

## Activar PRO

Al activar:

- `plan = pro`
- `subscription_status = active`
- `subscription_start = NOW()`
- `subscription_end = NOW() + 30 dias`

## Extender 30 dias

- Si el DJ sigue activo y `subscription_end` es futuro, sumar 30 dias desde `subscription_end`.
- Si ya vencio o no tiene fecha, sumar 30 dias desde hoy.

## Avisos automaticos

El cron `cron/check_subscriptions.php` debe ejecutarse diario.

Avisos:

- 7 dias antes.
- 3 dias antes.
- 1 dia antes.
- Dia vencido.

Los campos `last_notice_*` evitan correos duplicados.
