# Verificación final de MilkFlow — 8 de septiembre de 2026

**Estado: versión local terminada y funcionando con el diseño actual autorizado.**
La espera de capturas y logo externos queda cerrada por instrucción del usuario.

## Cambios completados

- Se conserva el diseño MilkFlow de Login y Crear cuenta, con todos los campos, validaciones y botones de contraseña.
- Recuperar contraseña usa el mismo layout, títulos y botón de la identidad vigente.
- Notificación de recuperación en español; enlace de un solo uso, vencimiento y cambio de contraseña verificados. APP_URL coincide con el servidor local.
- PWA incorporada: manifiesto, iconos derivados de la marca tipográfica actual, registro del service worker y pantalla sin conexión.
- La caché contiene solo archivos públicos; no almacena paneles, API ni tokens.
- Los registros de recolección se conservan en el dispositivo. Los datos locales dañados no se sobrescriben y no impiden iniciar el resto del JavaScript.
- README actualizado con acceso, cuentas demo, recuperación local, PWA y comandos de verificación.

## Resultados finales

| Comprobación | Resultado |
| --- | --- |
| Suite general | 28 tests, 189 aserciones, aprobados |
| Suite MySQL | 27 tests, 188 aserciones, aprobados |
| npm run build | Correcto |
| Migraciones | Las cinco aplicadas |
| MySQL | 8.4.3, puerto 3306, base milkflow_web |
| Aplicación | http://127.0.0.1:8011/login responde HTTP 200 |
| Cuentas demo | Seis activas |
| Navegación | Seis logins y redirecciones correctas; 46 enlaces válidos |
| Permisos | 30 accesos a paneles ajenos y ocho operaciones POST rechazados con 403 |
| Sesiones | Cierre de sesión y rechazo posterior de acceso privado verificados |
| Responsive | Autenticación a 1440, 390 y 320 px; menús móviles de los seis roles |
| PWA | Manifiesto, iconos, service worker, alternativa sin red y persistencia local verificados |

| Rol | Panel comprobado |
| --- | --- |
| Administrador | /admin/dashboard |
| Acopiador | /acopiador/dashboard |
| Supervisor | /supervisor/dashboard |
| Jefe de Producción | /produccion/dashboard |
| Despacho | /despacho/dashboard |
| Productor | /productor/dashboard |

Las pruebas conservan la cobertura existente de roles, validaciones, API, auditoría, reportes PDF/CSV, calidad, producción, stock, liquidaciones, rotaciones y cierre de rutas. No se eliminaron funcionalidades ni se reconstruyó la base de trabajo.

## Alcance del entorno local

No quedan bloqueos del alcance local acordado. El correo usa MAIL_MAILER=log: los mensajes de recuperación se consultan en storage/logs/laravel.log. No se ha configurado ni comprobado entrega a buzones externos.

La recolección sin red requiere abrir previamente su pantalla con conexión y mantenerla abierta. Si se recarga sin red, la PWA muestra la alternativa pública y conserva los registros locales; al reconectar se accede con la misma cuenta.

La prueba de navegador de sincronización simula respuestas 503/201, verifica el reintento con el mismo UUID y evita introducir entregas ficticias en la base real. La lógica del servidor y la idempotencia se comprueban en las suites PHP.

## Evidencias

- artifacts/browser-verification.json: recorrido final de roles y autenticación, sin errores.
- artifacts/pwa-verification.json: seis comprobaciones PWA, sin errores.
- artifacts/phpunit-mysql.xml: resultados de PHPUnit sobre milkflow_web_test.
- artifacts/auth-*.png y artifacts/{rol}-{desktop,mobile}.png: capturas de navegador.

La verificación final de navegador se ejecutó después del build. El fallo temporal detectado mientras se regeneraba el manifiesto no reapareció. La prueba PWA respeta Retry-After si el servidor limita intentos de login.

Consulta README.md para volver a iniciar el servidor tras reiniciar el equipo.
