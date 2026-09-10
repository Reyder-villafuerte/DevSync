# MilkFlow — App Android (Jetpack Compose + Material 3)

Capa de presentación construida sobre los use cases del módulo `shared/` (KMP,
Clean Architecture). **Ninguna lógica de negocio se duplica aquí**: la evaluación
de calidad (`EvaluadorCalidad` / `EvaluarCalidadUseCase`), el cálculo de reportes
(`ObtenerReporteAcopioUseCase`) y la resolución de conflictos (`SyncManager`) ya
existen y están probados en `shared/`.

## Arquitectura

```
androidApp/src/main/kotlin/pe/edu/upeu/milkflow/
├── MainActivity.kt                 host de navegación real (setContent { MilkFlowTheme { MilkFlowApp() } })
├── MilkFlowApplication.kt          arranca Koin (moduloCompartido + moduloPlataforma + moduloAndroid)
├── di/ModuloAndroid.kt             SÓLO ViewModels; consumen use cases (factory) y repos (single) de shared
├── ui/theme/                       Color · Type · Dimens · Theme  (Material 3, modo claro)
├── ui/navigation/                  Destinos · SesionGateViewModel · MilkFlowApp (NavHost por rol)
├── ui/components/                  IndicadorSync · EstadoPantalla · TarjetaKpi · ChipEstado · BuscadorTexto · DialogoConfirmacion
├── ui/util/                        sinAcentos · Formatos · CargadorImagenRemota · Impresion · presentacionDictamen
└── ui/screens/{login,acopiador,supervisor,productor,conflictos}/
```

- **ViewModels** (`androidx.lifecycle.ViewModel`) exponen `StateFlow<XxxUiState>`.
  Ninguna llamada a repositorio ocurre dentro de un `@Composable`.
- Los **Flow de SQLDelight** alimentan la UI: al registrar una recolección, los
  KPIs del acopiador se recalculan solos (el VM hace `combine` sobre
  `RecoleccionRepository.observarPorJornada`).
- **Ninguna acción del usuario requiere red** salvo el login. Todo escribe local
  y encola en el `outbox`; el `SyncManager` sube cuando hay conexión.
- **Estados explícitos** de carga / vacío / error en cada pantalla
  (`ui/components/EstadoPantalla.kt`).
- **Sistema de diseño**: primario `#1565C0`, fondo `#F8FAFC`; verde `#2E7D32`
  (conforme), rojo `#C62828` (alerta/sanción), naranja `#E65100` (acción
  principal). Cuerpo ≥ 16sp, KPIs 30sp, objetivo táctil mínimo 48dp
  (`Modifier.objetivoTactil()`), tarjetas con esquinas de 16dp.

## Navegación por rol

`SesionGateViewModel` observa `SesionRepository.observarSesion()`:

| Estado | Grafo |
|---|---|
| `SinSesion` | `login` |
| `Autenticado(ACOPIADOR)` | `acopiador/home` → `acopiador/cierre` → `acopiador/comprobante/{jornadaId}` (+ `conflictos`) |
| `Autenticado(SUPERVISOR_CALIDAD)` | `supervisor/inspeccion` → `supervisor/historial` (+ `conflictos`) |
| `Autenticado(PRODUCTOR)` | `productor/dashboard` (+ `conflictos`) |
| `RolNoSoportado` (planta/admin) | aviso + cerrar sesión |

El `NavHost` sólo registra las `composable(...)` del rol autenticado, así que un
destino de otro rol **no es alcanzable** (ni por back stack ni por deep link).
Al cerrar sesión, `observarSesion()` emite `null` y `MilkFlowApp` se recompone al
grafo de login sin navegación manual.

## Pantallas

### 0 · Login  (`ui/screens/login/`)
- DNI con teclado numérico (`KeyboardType.NumberPassword`, se limita a 8 dígitos)
  y contraseña.
- `IniciarSesionUseCase(dni, password, ANDROID_ID)`. El token lo guarda
  `SesionRepositoryHttp` en el almacén seguro; el re-enrutamiento lo hace el gate.
- Errores tipados de `ErrorApp`:
  `SinRed` → «Se necesita conexión sólo para el primer inicio de sesión»,
  `NoAutorizado` → «DNI o contraseña incorrectos»,
  `Validacion` (DNI mal formado) → mensaje bajo el campo.

### 1 · Acopiador  (`ui/screens/acopiador/`) — offline-first
- **Encabezado**: ruta asignada (resuelta de `Ambito.Ruta.codigo` vía
  `RutaRepository`), «Guardado local ✓» e `IndicadorSync` (pendientes + botón
  Sincronizar, conflictos tappables). *(El dominio no modela «camión»; sólo ruta.)*
- Pestañas **Ruta** / **Reportes**.
- **Ruta**: si no hay jornada abierta → «Iniciar ruta» (`IniciarJornadaUseCase`).
  KPIs de litros acopiados y socios atendidos (Flow). Buscador de proveedores que
  ignora acentos (`String.sinAcentos()` con `java.text.Normalizer`). Filtros
  Todos / Pendiente / Completo. Tocar una parada pendiente abre un
  `ModalBottomSheet` para ingresar litros → `RegistrarRecoleccionUseCase`; al
  confirmar, KPIs y estado de la parada cambian solos.
- **Reportes**: `ObtenerReporteAcopioUseCase` (ya agrega por día/semana/mes);
  selector de periodo, totales y detalle por `TotalPeriodo`.
- **Cierre de ruta** (pantalla aparte): resumen de litros, socios y paradas
  pendientes; opción de descarga en tina; `CerrarJornadaUseCase` →
  **Comprobante** con folio, detalle, estado de sincronización (`IndicadorSync`)
  y botón **Imprimir** (`android.print.PrintManager`).

### 2 · Supervisor de Calidad  (`ui/screens/supervisor/`)
- Selector **Ruta 01 / 02** → carga los mismos productores que ve el acopiador de
  esa ruta.
- Formulario Lactoscan: pH, % de agua adulterada, densidad, temperatura.
- **«Registrar / evaluar»** previsualiza el dictamen: construye
  `MedicionLactoscan.de(...)`, consulta `SancionRepository.tieneSancionAguaVigente`
  y llama a `EvaluadorCalidad.evaluar(...)` **sin persistir**. Muestra titular del
  veredicto, estado del lote, tarifa resultante y situación en el padrón
  (`presentacionDe(dictamen)` — presentación, no recálculo).
- **Un botón de acción ejecutable** según el dictamen
  (advertencia+descuento / descuento+retiro / expulsión+tarifa mínima /
  derivar a capacitación BPO). Al confirmarlo se ejecuta `EvaluarCalidadUseCase`
  (persiste la inspección con dictamen congelado y la encola; el backend aplica
  la sanción al sincronizar) y aparece **«Imprimir ticket térmico»**.
- **Historial** (pantalla aparte): buscador por productor (sin acentos), filtro
  Día / Semana / Mes sobre `tomadoEn`, listado con fecha, hora, supervisor,
  mediciones y dictamen, y **«Ver / descargar acta PDF»**
  (`android.graphics.pdf.PdfDocument` + `FileProvider`).

### 3 · Productor  (`ui/screens/productor/`)
- **Pop-up obligatorio** con el aviso vigente (`ObtenerAvisoActivoUseCase`):
  título, imagen (descargada sin librería en el ViewModel → `ImageBitmap`),
  mensaje y **«Leído y Entendido»** (`AvisoRepository.marcarVisto`).
- **Dashboard**: litros entregados hoy (`ObtenerReporteAcopioUseCase(productorId)`),
  estado de calidad (última inspección).
- **Ciclo jueves→miércoles** + **pago proyectado del viernes**: litros del ciclo
  (ventana `LocalDate.previousOrSame(THURSDAY)…+6`, America/Lima) × tarifa vigente
  de compra (`PrecioRepository.observarVigentes` → `ConceptoPrecio.COMPRA_LECHE`);
  más la última `Liquidacion` real.
- **Ruta asignada** + **«Solicitar cambio»**: `ModalBottomSheet` para elegir una
  de las cuatro zonas y escribir el motivo → `SolicitarCambioRutaUseCase`. Muestra
  el estado de la última solicitud (`SolicitudRutaRepository.observarMisSolicitudes`).

### Conflictos  (`ui/screens/conflictos/`, común a los 3 roles)
`SincronizacionRepository.observarConflictos()` → lista de operaciones del
`outbox` en estado CONFLICTO con su `ultimoError`. Botón **«Reintentar
sincronización»** (`SincronizarAhoraUseCase`). Los conflictos se **exponen**, no
se silencian.

## Tabla pantalla → use cases / repos

| Pantalla | Use cases | Repositorios |
|---|---|---|
| Login | `IniciarSesionUseCase` | — |
| Acopiador · Ruta | `IniciarJornadaUseCase`, `RegistrarRecoleccionUseCase` | `RutaRepository`, `ZonaRepository`, `ProductorRepository`, `JornadaRepository`, `RecoleccionRepository` |
| Acopiador · Reportes | `ObtenerReporteAcopioUseCase` | — |
| Acopiador · Cierre / Comprobante | `CerrarJornadaUseCase` | `JornadaRepository`, `RecoleccionRepository`, `ProductorRepository`, (+ zonas/rutas) |
| Supervisor · Inspección | `EvaluarCalidadUseCase` (+ `EvaluadorCalidad` para preview) | `RutaRepository`, `ZonaRepository`, `ProductorRepository`, `SancionRepository` |
| Supervisor · Historial | — | `ProductorRepository`, `InspeccionRepository` |
| Productor | `ObtenerAvisoActivoUseCase`, `ObtenerReporteAcopioUseCase`, `SolicitarCambioRutaUseCase` | `AvisoRepository`, `ProductorRepository`, `ZonaRepository`, `RutaRepository`, `InspeccionRepository`, `PrecioRepository`, `LiquidacionRepository`, `SolicitudRutaRepository` |
| Indicador de sync (global) | `ObtenerEstadoSincronizacionUseCase`, `SincronizarAhoraUseCase` | — |
| Conflictos | `SincronizarAhoraUseCase` | `SincronizacionRepository` |

## Compilar y probar

```bash
cd D:\DevSync
./gradlew :androidApp:assembleDebug
./gradlew :androidApp:testDebugUnitTest      # tests de transición de estado de los ViewModels
./gradlew :shared:allTests                    # sigue en verde (shared no se toca)
```

Toolchain fijado por el repo: Gradle 9.5.0 · AGP 9.1.1 · Kotlin 2.4.10 · JDK 17 ·
Android SDK 37 · minSdk 24. `androidApp` **no** aplica `org.jetbrains.kotlin.android`
(AGP 9 integra Kotlin); Compose se habilita con el plugin `composeCompiler`.

`urlBase` = `http://10.0.2.2:8000/` (host del emulador → backend Laravel local).

## Descripción visual de cada pantalla

- **Login**: tarjeta centrada, título «MilkFlow» en azul marino, dos campos
  redondeados y botón naranja «Ingresar» a ancho completo; el error aparece en
  rojo centrado entre los campos y el botón.
- **Acopiador · Ruta**: barra superior con «Ruta Norte / Guardado local ✓», fila
  del `IndicadorSync`, pestañas Ruta|Reportes; dos `TarjetaKpi` grandes (Litros /
  Socios), buscador, tres `FilterChip` de filtro, y una lista de tarjetas de
  parada: nombre + código de padrón a la izquierda, chip verde con los litros
  (completo) o chip naranja «Pendiente» a la derecha. Abajo, botón naranja
  «Cerrar ruta».
- **Acopiador · Sheet de recolección**: hoja inferior con el nombre del
  productor, campo decimal de litros y botón naranja «Confirmar recolección».
- **Acopiador · Cierre**: tres KPI (Litros / Socios / Pendientes), aviso naranja
  si quedan paradas, campos opcionales de tina/litros/recibido, botón naranja
  «Confirmar cierre de ruta».
- **Acopiador · Comprobante**: `IndicadorSync` arriba, tarjeta «Folio ABCD1234»
  con fecha, líneas productor→litros, total en negrita, botón naranja «Imprimir
  comprobante» que abre el diálogo de impresión del sistema.
- **Supervisor · Inspección**: `IndicadorSync`, tarjeta «Ruta» con dos chips
  01/02, tarjeta con la lista de productores; al elegir uno, formulario con
  cuatro campos decimales y botón azul «Registrar / evaluar». La tarjeta de
  dictamen muestra el titular en negrita, un chip «LOTE ACEPTADO/RECHAZADO», el
  detalle, tarifa y padrón, y un único botón naranja con la acción ejecutable;
  tras ejecutarla, texto verde «Medida registrada…» y botón «Imprimir ticket
  térmico».
- **Supervisor · Historial**: buscador, sugerencias de productor, tres chips
  Día/Semana/Mes, y tarjetas de inspección (fecha+hora, chip de dictamen,
  supervisor, mediciones, enlace «Ver / descargar acta PDF»).
- **Productor · Dashboard**: `IndicadorSync`, KPI «Litros entregados hoy» y
  tarjeta «Estado de calidad» con chip; tarjeta «Ciclo de pago» con acumulado,
  tarifa y «Pago proyectado (viernes)» en negrita; tarjeta «Ruta asignada» con el
  nombre, chip del estado de la última solicitud y botón naranja «Solicitar
  cambio de ruta».
- **Productor · Pop-up de aviso**: `AlertDialog` no descartable con título,
  imagen (si hay), mensaje y botón «Leído y Entendido».
- **Conflictos**: botón «Reintentar sincronización» a ancho completo y una lista
  de tarjetas con entidad·operación, id de registro, motivo en rojo y fecha.
