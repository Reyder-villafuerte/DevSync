# MilkFlowMovil

App móvil (Kotlin Multiplatform + Compose Multiplatform) de la asociación de productores de Huata.
Replica las pantallas del panel web `MilkFlowWeb` y **funciona sin internet**: casi todo el acopio
se registra a las 4:30 de la mañana en zonas sin cobertura.

## Cómo funciona el offline

```
Pantallas  ──lee──►  Base local (JSON en el teléfono)  ◄──escribe──  Sincronizador
                                     ▲                                    │
                                     └── acciones del usuario ────────────┘
                                          (cambio inmediato + cola)
```

1. **Ninguna pantalla habla con la red.** Todas leen de la base local (`BaseLocal`), así que
   responden igual con o sin señal.
2. Cada acción escribe de inmediato en la base local (el usuario ve el resultado al instante) y
   **encola una operación** con un `client_uuid` generado en el teléfono.
3. El `Sincronizador` sube la cola y luego baja los cambios del servidor, siempre en ese orden.
4. El servidor aplica las reglas de negocio y devuelve el id definitivo; la fila provisional del
   teléfono (id negativo) adopta ese id.
5. Reenviar una operación no duplica nada: el servidor recuerda el `client_uuid`
   (tabla `sync_operations`) y devuelve la respuesta original.

Si el servidor **rechaza** una operación por una regla de negocio (por ejemplo, vender queso sin
stock), el cambio optimista se deshace en el teléfono y el motivo aparece en la pantalla
**Sincronización**. Si el fallo es de red, la operación se queda en cola y se reintenta sola
(cada minuto si hay trabajo pendiente, cada cinco si no).

## Estructura (arquitectura Clean, igual que la guía de Ktor Client en KMP)

```
shared/src/commonMain/kotlin/com/example/milkflowmovil/
├── core/                         Resultado, ErrorApp, fechas y formato (soles, litros)
│
├── data/                         CAPA DE DATOS: de dónde vienen los datos
│   ├── remote/
│   │   ├── HttpClientFactory.kt  createHttpClient(engine): ContentNegotiation, Logging,
│   │   │                         HttpTimeout y defaultRequest
│   │   ├── MotorHttp.kt          expect: OkHttp (androidMain) / Darwin (iosMain)
│   │   ├── MilkFlowRemoteDataSource.kt  única clase que llama a la API /api/sync
│   │   └── dto/                  @Serializable + @SerialName: el contrato JSON
│   ├── mapper/                   toDomain() / toDto(): DTO <-> modelo de dominio
│   ├── local/                    BaseLocal (archivo JSON en el teléfono) y DocumentoLocal
│   ├── sync/Sincronizador.kt     sube la cola y baja los cambios por cursor
│   └── repository/               *RepositoryImpl: implementan las interfaces del dominio
│
├── domain/                       CAPA DE DOMINIO: Kotlin puro, sin JSON ni Ktor
│   ├── model/                    Zona, Usuario, Ruta, Entrega... y EstadoApp
│   ├── rules/Reglas.kt           precios, sobre semanal y merma
│   ├── repository/               interfaces (contratos) de cada módulo
│   └── usecase/                  un caso de uso por acción (RegistrarEntregaUseCase...)
│
├── presentation/                 CAPA DE PRESENTACIÓN: Compose
│   ├── App.kt                    raíz: sesión, menú lateral y pantalla actual
│   ├── viewmodel/                un ViewModel por módulo, expone uiState (StateFlow)
│   ├── screens/                  pantallas por rol
│   ├── navigation/               Pantalla (menú por rol) y Navegador
│   ├── components/               tarjetas, botones y campos reutilizables
│   └── theme/                    colores y tipografía
│
└── di/Contenedor.kt              arma todo: datos -> dominio -> presentación
```

Flujo de una acción (por ejemplo, registrar litros en ruta):

```
Pantalla ──> AcopioViewModel ──> RegistrarEntregaUseCase ──> AcopioRepository (interfaz)
                                                                   │
                                        AcopioRepositoryImpl <─────┘
                                           │ escribe en BaseLocal y encola
                                           ▼
                           Sincronizador ──> MilkFlowRemoteDataSource ──> API REST
```

La pantalla nunca ve un DTO ni JSON: recibe `EstadoApp` (dominio) desde `viewModel.uiState`.

### Configuración de red por plataforma

- **Android:** `androidApp/src/main/res/xml/network_security_config.xml` permite HTTP solo hacia
  `10.0.2.2`, `127.0.0.1` y `localhost`. Para usar un teléfono físico por Wi-Fi, agrega ahí la IP
  de la computadora de la planta.
- **iOS:** `Info.plist` tiene `NSAllowsLocalNetworking` (HTTP solo en la red local).

**Por qué un documento JSON y no SQLite:** a la escala de Huata (decenas de proveedores, cientos
de entregas por semana) el estado completo cabe de sobra en memoria, el guardado es atómico
(escribe a temporal y renombra) y evita arrastrar un generador de código al proyecto. Si el padrón
creciera a miles de filas, el reemplazo natural es SQLDelight detrás de la misma interfaz `BaseLocal`.

## Roles y pantallas

Cada rol ve exactamente su menú (`Rol.menu`); una pantalla fuera de él no es alcanzable ni
navegando:

| Rol | Pantallas |
|---|---|
| Acopiador | Acopio 4:30 AM, Historial y reportes |
| Productor | Mi acopio, Cambio de zona, Descuentos, Historial de pagos, Calidad |
| Jefe de producción | Caudalímetro, Quesería |
| Inspector de calidad | Panel, Lactoscan y citas |
| Personal de venta | Panel, Ventas, Recibos |
| Personal de pago | Panel, Autorizar pagos, Sobres en ruta, Historial |
| Pagador de campo | Sobres en ruta, Historial de sobres |
| Administración | Panel, Historial, Zonas, Solicitudes, Calidad, Flujo de caja, Recibos, Autorizar pagos, Tarifas, Avisos |
| Jefe general | Todo lo anterior |

Todos tienen además la pantalla **Sincronización** (cola, rechazos y dirección del servidor).

## Ejecutar

```bash
./gradlew :androidApp:assembleDebug
./gradlew :shared:testAndroidHostTest
```

El backend debe estar corriendo (`php artisan serve` en `MilkFlowWeb`). La dirección del servidor
se configura en la pantalla de login:

- **Emulador:** `http://10.0.2.2:8000` (o `adb reverse tcp:8000 tcp:8000` y usar `http://127.0.0.1:8000`)
- **Teléfono físico en la misma red:** la IP de la computadora de la planta, con el servidor
  levantado en `--host=0.0.0.0`

El login se hace con **DNI** (lo que la gente recuerda) o correo. La primera vez hace falta señal;
después la app arranca con lo que ya tiene guardado.

## iOS

El módulo `shared` compila para iOS (`iosArm64`, `iosSimulatorArm64`) y toda la interfaz es
Compose Multiplatform, así que las pantallas son las mismas. La app de iOS solo se puede compilar
y verificar desde macOS; en Windows los targets de iOS se omiten.
