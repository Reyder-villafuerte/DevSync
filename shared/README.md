# MilkFlow · módulo `shared` (Kotlin Multiplatform)

Lógica **offline-first** que consumen `androidApp/` e `iosApp/`. Clean
Architecture estricta: la capa **domain no importa nada de SQLDelight, Ktor ni
Android**, y **ninguna escritura de usuario requiere red** — se persiste en
SQLite y se encola en el `outbox` en la misma transacción; el `SyncManager`
concilia después.

## Estructura

```
shared/src/
├── commonMain/kotlin/pe/edu/upeu/milkflow/
│   ├── core/            Resultado, ErrorApp, Reloj, GeneradorId
│   ├── domain/
│   │   ├── model/       Entidades (Productor, JornadaRuta, Recoleccion, …) + enums + Ambito
│   │   ├── vo/          Value objects: Dni, Litros, MedicionLactoscan, TarifaPorLitro, Dinero
│   │   ├── calidad/     EvaluadorCalidad (RN-05 / RN-06, lógica pura)
│   │   ├── reporte/     ReporteAcopio (agregación día/semana/mes)
│   │   ├── repository/  Interfaces de repositorio (sin dependencias de infra)
│   │   ├── sync/        Puertos + modelos + SyncManager + PoliticaReintento
│   │   └── usecase/     Un caso de uso por intención del usuario
│   ├── data/
│   │   ├── local/       SQLDelight: drivers (expect), mapeadores, repos locales, ConstructorPayload
│   │   ├── remote/      Ktor: FabricaHttp (expect), ApiMilkFlow, DTOs, MapeadorErrores,
│   │   │                ClienteSincronizacionHttp, AplicadorCambios, SesionRepositoryHttp
│   │   └── (…)
│   ├── infra/
│   │   ├── almacen/     AlmacenSeguroToken (interface)
│   │   └── conectividad/ (interface en domain/sync)
│   └── di/              Koin: moduloCompartido + moduloPlataforma (expect) + iniciarKoin
├── commonMain/sqldelight/…/db/MilkFlow.sq   Esquema local + queries + outbox
├── androidMain/…        AndroidSqliteDriver, OkHttp, EncryptedSharedPreferences,
│                        ConnectivityManager, SyncWorker (WorkManager 15 min)
├── iosMain/…            NativeSqliteDriver, Darwin, Keychain, NWPathMonitor,
│                        ProgramadorSincronizacionIos (BGTaskScheduler)
└── commonTest/…         Fakes + tests (RN-05/06, use case, outbox, conflictos)
```

## Flujo de una recolección: del tap del acopiador a PostgreSQL

1. **Tap.** El acopiador pulsa "Registrar" en `androidApp`. El ViewModel llama
   a `RegistrarRecoleccionUseCase(acopiadorId, productorId, litros)`.
2. **Validación local (sin red).** El use case:
   - obtiene la jornada activa (`JornadaRepository`), verifica que está `EN_CURSO`;
   - carga el productor del padrón local, verifica que puede entregar;
   - comprueba que no haya entregado ya en esta ruta;
   - construye `Litros` (value object) y una `Recoleccion` con **UUID generado en
     el cliente**, `version = 0`, `updatedAt = ahora local`.
3. **Escritura atómica.** `RecoleccionRepositoryLocal.registrar()` abre
   `db.transaction { }` y hace **dos** escrituras:
   - `INSERT` en la tabla `recolecciones`;
   - `q.encolar("INSERTAR", "registros_acopio", <uuid>, <payload camelCase>, 0)`
     en la tabla `outbox` (con `ON CONFLICT(tabla, id_registro) DO UPDATE`).
   Si algo falla, no queda ni la recolección ni la operación de outbox.
   La UI ya ve la recolección (las queries devuelven `Flow`) marcada como
   `PENDIENTE`. **En ningún momento se tocó la red.**
4. **Disparo de sincronización.** Ocurre por cualquiera de estas vías:
   - el `ObservadorConectividad` emite `true` (volvió la red) → `SyncManager`
     se dispara solo;
   - `SyncWorker` (Android, cada 15 min con red) / `BGTaskScheduler` (iOS);
   - el usuario pulsa el indicador del encabezado (`SincronizarAhoraUseCase`).
5. **Subida (`SyncManager.subirPendientes`).**
   - `SincronizacionRepository.siguienteLote(100)` devuelve las operaciones
     pendientes en orden de creación;
   - `ClienteSincronizacionHttp.subir()` arma **una** llamada
     `POST /api/sync/push` con `{ operaciones: [{entidad, id, versionBase,
     atributos}] }` (`avisos_vistos` usa su endpoint dedicado);
   - el backend responde `200`/`207` separando `aceptadas` / `conflictos` /
     `rechazadas`.
6. **Aplicación del resultado (por dominio).**
   - `aceptada` → `marcarSincronizada` (sale del outbox);
   - `conflicto` en entidad **solo-inserción** o **version** → `marcarConflicto`
     (visible en la UI, con el estado del servidor);
   - `conflicto` **conmutativo** (stock) o **servidor_gana** (precios/avisos) →
     `descartar` (el servidor manda);
   - `rechazada` (validación/integridad) → `marcarConflicto`;
   - sin respuesta / error de transporte → `registrarIntentoFallido`; si supera
     `PoliticaReintento.maxIntentos` (backoff exponencial + jitter) →
     `marcarConflicto`.
7. **En el backend** (`SincronizacionController@push` → `SyncService`): una
   transacción por tabla + savepoint por operación; para `registros_acopio` la
   regla es solo-inserción; la fila entra en PostgreSQL con su `version` y
   `updated_at` definitivos. El `EvaluacionCalidadService` corre para las
   inspecciones.
8. **Bajada (`SyncManager.bajarDeltas`).** Inmediatamente después,
   `GET /api/sync/pull?desde={cursor}&ambito=ruta:01`. `AplicadorCambios`
   hace `INSERT OR REPLACE` de cada fila del delta en **una** transacción y
   marca las filas como `SINCRONIZADO`; la recolección recién subida vuelve con
   su estado real. El cursor (`updated_at`) se persiste por ámbito en
   `cursores_sync`.
9. **UI.** Los `Flow` de SQLDelight reemiten; el badge del encabezado
   (`ObtenerEstadoSincronizacionUseCase` → `EstadoSincronizacion`) pasa de
   `pendientes = 1` a `alDia`.

## Motor de sincronización — decisiones

| Decisión | Motivo |
|---|---|
| **Subir antes de bajar** | El servidor recibe primero lo que generó el dispositivo; la bajada devuelve esas filas ya con su `version`/`updated_at`, evitando un falso conflicto contra uno mismo. |
| **Outbox como tabla, no cola en memoria** | Sobrevive a que el proceso muera; el `ON CONFLICT(tabla,id_registro)` da idempotencia: reencolar el mismo registro no duplica. |
| **`encolar` en la MISMA transacción que la escritura de negocio** | Imposible tener una recolección sin su operación pendiente (o al revés). Ninguna escritura de usuario depende de la red. |
| **Estrategia de conflicto declarada por entidad** (`EstrategiaConflicto.deTabla`) | Cada dato tiene semántica propia: una recolección no se "edita" (solo-inserción), dos movimientos de stock conmutan, un precio lo fija la planta. |
| **Cursor de timestamp por ámbito + colchón de reloj** | El backend expone `?desde={timestamp}`. El colchón (`SYNC_MARGEN_RELOJ`) reenvía filas del borde; el `INSERT OR REPLACE` local las absorbe sin efecto. |
| **Backoff exponencial + jitter + tope** | Ante una caída del backend, los dispositivos no reintentan en manada; superado el tope, el registro se marca `CONFLICTO` y se muestra en la UI en vez de fallar en silencio. |
| **`Resultado<T>` + `ErrorApp` sellado** | La UI distingue exhaustivamente sin red / no autorizado / conflicto de versión / stock insuficiente / error de servidor. |
| **Token en almacén seguro, nunca en la BD** | EncryptedSharedPreferences (Android) / Keychain (iOS). El plugin Auth de Ktor lo lee en cada request, así un re-login no exige recrear el cliente. |

## Puesta en marcha

```kotlin
// Android — MilkFlowApplication.onCreate()
iniciarKoin(ConfiguracionMilkFlow(urlBase = "https://api.milkflow.pe/", nivelLogHttp = NivelLogHttp.BASICO)) {
    androidLogger()
    androidContext(this@MilkFlowApplication)
}
SyncWorker.programar(this)   // SyncWorker resuelve SyncManager por KoinComponent
```

```swift
// iOS — iOSApp.init()
MilkFlowSharedKt.iniciarKoinIos(urlBase: "https://api.milkflow.pe/")
// registrar BGTaskScheduler con ProgramadorSincronizacionIos.IDENTIFICADOR_TAREA
```

`ConfiguracionMilkFlow.nivelLogHttp` es un enum propio (`core.NivelLogHttp`), no
un tipo de Ktor: la frontera pública del módulo no filtra infraestructura.

## Estado del build (ver `CHANGELOG_BUILD.md`)

Compila y prueba en verde con Gradle 9.5.0 / AGP 9.1.1 / Kotlin 2.4.10 / JDK 17:

```bash
./gradlew :shared:compileKotlinMetadata          # commonMain
./gradlew :shared:compileAndroidMain             # Android
./gradlew :shared:compileKotlinIosSimulatorArm64 # iOS (klib)
./gradlew :shared:allTests                       # 24 tests, 0 fallos
./gradlew :androidApp:assembleDebug
```

- **`commonTest`** (fakes, lógica pura + `SyncManager`): `EvaluadorCalidadTest` (8),
  `RegistrarRecoleccionUseCaseTest` (5), `OutboxIdempotenciaTest` (3),
  `ResolucionConflictosTest` (4).
- **`androidHostTest`** (driver real `JdbcSqliteDriver` en memoria):
  `EsquemaMilkFlowTest` (4) — verifica el `ON CONFLICT` del outbox, la atomicidad
  negocio+outbox y los cursores contra el esquema real.
- `iosSimulatorArm64Test` se **omite** (SKIPPED) en hosts no-macOS; se ejecutaría
  en un runner de CI con Xcode.

## Notas / pendientes

- **iOS Keychain / NWPathMonitor: el cinterop COMPILA** en `iosSimulatorArm64`
  (`compileKotlinIosSimulatorArm64` verde). NO se ejecutó en simulador/dispositivo
  (build en Windows); el runtime del Keychain debe probarse en Xcode. Si diera
  problema, el binding de `AlmacenSeguroToken` en el `moduloPlataforma()` de iOS
  es el punto para inyectar una implementación Swift.
- Repos locales implementados para el flujo de campo (acopio, calidad, avisos,
  solicitudes, catálogos). `ventas`, `sesiones_produccion`, `asistencias` tienen
  esquema `.sq` pero repos aún no cableados (extensión mecánica del mismo patrón).
- Deuda de diseño menor documentada en `CHANGELOG_BUILD.md` (§ Notas de diseño):
  parámetro `RecepcionRepository` sin usar en `JornadaRepositoryLocal`;
  `avisos_vistos.productor_id` usa el `usuarioId` local.
