# Documentacion de Estadisticas

## Tablas actuales

- `statistics`: acumulados por mix/video.
- `user_activity`: actividad historica de reproducciones/descargas.
- `site_visits`: nueva tabla para visitas publicas anonimizadas.

## Reglas nuevas

- Las visitas publicas se guardan con IP hasheada, nunca IP completa visible.
- No se registran visitas para dashboard, login, admin, api, assets ni uploads.
- Se evita duplicar visitas del mismo usuario en una ventana corta de tiempo.
- Plays y descargas existentes siguen funcionando con `statistics`.

## Indicadores recomendados

- Visitas totales.
- Visitas de hoy.
- Visitas de la semana.
- Visitas del mes.
- Paginas mas visitadas.
- DJs mas visitados.
- Mixes mas escuchados.
- Dispositivos usados.
- Referers.
