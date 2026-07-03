# Panda Truck Android App

App Android nativa/hibrida para Panda Truck Reloaded.

## Que incluye

- Inicio nativo con secciones Radio, Mixes, DJs y Web.
- Listas nativas para mixes y DJs consumiendo APIs del sitio.
- Descarga de mixes desde la app.
- Servicio de audio en segundo plano.
- Notificacion persistente con controles de radio.
- Lectura de avisos del dashboard mientras la app esta abierta.
- APK debug para instalar manualmente y probar.

## Build manual

Desde PowerShell:

```powershell
cd android
.\build-apk.ps1
```

El APK queda en:

```text
android/release/PandaTruck-debug.apk
```

## Nota

Esta primera version usa como stream nativo:

```text
https://stream.zeno.fm/vjsa6jiwafavv
```

Si cambias la radio en el dashboard, tambien hay que actualizar `RadioService.java`.

## Avisos del dashboard

La app nativa lee estos endpoints:

```text
https://pandatruckreloaded.com/api/app_mixes.php
https://pandatruckreloaded.com/api/app_djs.php
https://pandatruckreloaded.com/api/app_notifications.php
```

Sube esos archivos al hosting para que Mixes, DJs y avisos funcionen en la app. Para push real con la app cerrada hace falta Firebase Cloud Messaging.
