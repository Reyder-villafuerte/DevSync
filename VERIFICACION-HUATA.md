# Entrega del diseño Huata: azul y marfil

## Contenido

- MilkFlowWeb: Bootstrap 5.3 y Tailwind 4 compilados con Vite, menú responsive, selector Claro/Oscuro/Sistema, identidad gráfica e indicadores con datos del controlador.
- MilkFlowMovil: identidad equivalente en Compose, recursos de marca, ilustraciones y preferencia de apariencia local.
- Corrección de renderizado de anuncios en sesión y prueba de regresión.
- Cambios locales previos de web-acopio: lockfile npm y utilidades de diagnóstico/configuración de base de datos. Las utilidades no se ejecutaron en esta revisión.
- Notas locales de configuración previamente preparadas en MilkFlowWeb/.artifacts. Son antecedentes, no instrucciones de instalación vigentes.

No se incluyen .env, credenciales de máquina, cachés del IDE, dependencias descargadas, APK ni compilaciones generadas. El código y los recursos necesarios para regenerarlos sí están versionados.

## Verificación previa a publicación (septiembre de 2026)

| Comprobación | Resultado |
| --- | --- |
| Base origin/proyecto | Coincide con el commit local de partida 78b6de4; sin divergencias |
| Índice Git y marcadores de conflicto en fuentes revisadas | Sin conflictos pendientes |
| MilkFlowWeb: npm ci y npm run build | Correctos |
| MilkFlowWeb: PHPUnit con SQLite en memoria | 48 pruebas y 333 aserciones aprobadas |
| Android: assembleDebug y testAndroidHostTest | Correctos; 18 pruebas automatizadas |
| Web real y API móvil: nueve roles | Ingreso y descarga móvil con respuesta HTTP 200 |
| Navegación MilkFlowWeb | 77 enlaces/páginas revisados, HTTP 200 |
| web-acopio: npm ci y npm run build | Correctos |
| web-acopio: PHPUnit con SQLite en memoria | 28 pruebas; 25 aprobadas y 3 fallidas preexistentes |
| Utilidades create_db.php y list_tables.php | Sintaxis PHP válida; no ejecutadas |

## Incidencia preexistente en web-acopio

`app/Models/User.php`, método `dashboard()`, devuelve `/dashboard` para los roles oficiales. `routes/web.php` registra los paneles bajo `/{rol}/dashboard`. Las pruebas PasswordRecoveryTest, WorkerRegistrationTest y WorkflowTest detectan este desacuerdo de redirección. El archivo ya tiene ese comportamiento en origin/proyecto (commit de origen 5c6cc34); no fue cambiado por el rediseño.

Se conserva para respetar el alcance de no modificar backend. Este defecto afecta al proyecto anterior web-acopio, no a MilkFlowWeb. No se presenta la entrega como libre de todos los errores del repositorio.

## Reproducir

En MilkFlowWeb y web-acopio, con PHP 8.3 y Composer disponibles:

```sh
composer install
npm ci
npm run build
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/phpunit --no-progress
```

Las suites usan SQLite en memoria; no deben apuntarse a la base de datos de trabajo. Configurar el entorno local desde `.env.example` para ejecutar las aplicaciones. El rediseño web se usa en MilkFlowWeb; los prototipos de puertos 8027–8029 no son necesarios.

En MilkFlowMovil, con Android SDK y el JDK requerido por Gradle:

```powershell
.\gradlew.bat :androidApp:assembleDebug :shared:testAndroidHostTest --console=plain
```

En el equipo de revisión se utilizó `--offline` porque las dependencias ya estaban descargadas. iOS necesita macOS y no se compiló aquí.

El build web informa un aviso no bloqueante de `@charset` en el CSS importado de Bootstrap y de la ilustración pública resuelta en ejecución. La imagen se verificó sirviéndose desde MilkFlowWeb/public/brand.

La comprobación HTTP no valida todas las operaciones de negocio. No se enviaron pagos, ventas ni cambios de tarifas. Queda pendiente recorrer visualmente todas las pantallas Android del rediseño y probarlo en un teléfono físico.
