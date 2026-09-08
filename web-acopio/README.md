# MilkFlow

Aplicación web para gestionar el acopio de leche en Huari: productores, rutas, entregas, control de calidad, producción, despacho, liquidaciones, reportes y auditoría.

El diseño vigente de MilkFlow es la versión adoptada para esta entrega. No requiere capturas ni recursos gráficos externos.

## Abrir la aplicación local

URL: **http://127.0.0.1:8011/login**

MySQL de Laragon debe estar en ejecución en el puerto 3306. La aplicación utiliza la base existente **milkflow_web**, con sesiones en MySQL.

Si reinicias el equipo, inicia MySQL desde Laragon y ejecuta desde esta carpeta:

```powershell
php artisan serve --host=127.0.0.1 --port=8011
```

Las dependencias y el build ya están instalados. No es necesario volver a sembrar ni reconstruir la base de datos.

## Cuentas de demostración locales

| Usuario | Rol | Panel |
| --- | --- | --- |
| admin | Administrador | /admin/dashboard |
| acopiador | Acopiador | /acopiador/dashboard |
| supervisor | Supervisor | /supervisor/dashboard |
| produccion | Jefe de Producción | /produccion/dashboard |
| despacho | Despacho | /despacho/dashboard |
| productor | Productor | /productor/dashboard |

Contraseña de demostración: **MilkFlow!2026**. También se puede iniciar sesión con el correo de cada cuenta, por ejemplo admin@milkflow.test.

El registro público conserva nombres, apellidos, documento, teléfono, correo y validaciones de contraseña. Las cuentas nuevas quedan pendientes hasta que el administrador las aprueba y asigna un rol.

## Recuperación de contraseña

El formulario envía una notificación en español con un enlace de un solo uso que vence a los 60 minutos. El cambio conserva el estado y los roles del usuario.

En este entorno se usa **MAIL_MAILER=log**: el contenido del correo y su enlace se consultan localmente en **storage/logs/laravel.log**. No se entrega correo a buzones externos. APP_URL apunta al servidor local para que los enlaces funcionen.

## PWA y recolección sin conexión

La PWA incluye manifiesto, iconos MilkFlow y service worker. El navegador compatible puede ofrecer instalarla desde su menú.

Para recolectar sin red, entra como acopiador, abre **Recolección sin conexión** mientras tienes conexión y mantén la pantalla abierta. Cada registro se guarda en el dispositivo con un UUID y se reintenta al volver la conexión.

Si recargas o abres otra pantalla sin red, aparece una pantalla de recuperación; los registros locales permanecen guardados. Al reconectar, entra con la misma cuenta y abre de nuevo la recolección. Una sesión vencida requiere volver a iniciar sesión. Si el almacenamiento no se puede leer, la aplicación evita sobrescribirlo.

El service worker almacena únicamente archivos públicos. No guarda paneles privados, respuestas de API ni tokens de sesión. La implementación sigue el ciclo de registro y caché documentado por [MDN](https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API/Using_Service_Workers).

## Verificar cambios

Ejecuta en este orden, terminando el build antes de abrir las pruebas de navegador:

```powershell
php artisan test
php vendor/bin/phpunit --configuration phpunit.mysql.xml
npm.cmd run build
node tests/browser-smoke.mjs
node tests/browser-pwa.mjs
```

La suite general usa SQLite en memoria. La suite MySQL reconstruye exclusivamente la base de pruebas **milkflow_web_test**, indicada en phpunit.mysql.xml.

Las pruebas de navegador usan Chrome instalado y las cuentas demo existentes. MILKFLOW_URL permite cambiar la URL y MILKFLOW_DEMO_PASSWORD, la contraseña. La prueba de reintento PWA simula las respuestas de sincronización para no añadir entregas de ensayo a la base real; la lógica real de entrega e idempotencia se comprueba en las suites PHP.

Resultados: **VERIFICACION.md**. Evidencias y capturas: **artifacts/**.
