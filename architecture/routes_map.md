# 🧭 Mapa Global de Rutas y Navegación — MilkFlow Huata

## 1. Diagrama de Navegación Web y API

```mermaid
graph TD
    LOGIN[/login] --> AUTH{Autenticación}
    AUTH -- Error --> LOGIN
    AUTH -- Productor --> P_ACOP[/productor/acopio]
    AUTH -- Acopiador --> ACOPIO[/acopio]
    AUTH -- Jefe Prod --> PLANTA[/planta/verificacion]
    AUTH -- Pagador --> PAGOS_R[/pagos/ruta]
    AUTH -- Admin/Ventas/Otros --> DASH[/dashboard]

    subgraph Portal Productor
        P_ACOP --> P_ZONAS[/productor/zonas]
        P_ACOP --> P_DESC[/productor/descuentos]
        P_ACOP --> P_PAGOS[/productor/pagos]
        P_PAGOS --> P_RECIBO[/productor/pagos/:id/recibo]
        P_ACOP --> P_CAL[/productor/calidad]
    end

    subgraph Operaciones Campo y Planta
        ACOPIO --> A_HIST[/acopio/historial]
        PLANTA --> CAT[/produccion/categorias]
        PLANTA --> ALM[/produccion/almacen]
        PLANTA --> COMP[/produccion/compras]
        PLANTA --> PRODU[/produccion/productos]
        PLANTA --> LOTES[/produccion/lotes]
    end

    subgraph Despacho y Calidad
        DASH --> VENTAS[/ventas]
        VENTAS --> V_CIERRE[/ventas/cierre-caja]
        VENTAS --> V_RECIBOS[/ventas/recibos]
        VENTAS --> V_REC[/ventas/recibo/:sale]
        DASH --> CALIDAD[/calidad]
        CALIDAD --> C_ANALISIS[/calidad/analisis]
        CALIDAD --> C_CITA[/calidad/cita/:id/completar]
    end

    subgraph Pagos y Administración
        PAGOS_R --> PR_PAGAR[/pagos/ruta/pagar/:id]
        PAGOS_R --> PR_RECIBO[/pagos/ruta/recibo/:id]
        PAGOS_R --> PR_HIST[/pagos/ruta/historial]
        DASH --> ADM_PAGOS[/admin/pagos/autorizacion]
        DASH --> ADM_PRECIOS[/admin/precios]
        DASH --> ZONAS[/zonas]
        DASH --> ANUNCIOS[/anuncios]
    end

    subgraph API Móvil Sanctum
        API_LOGIN[/api/sync/login: DNI o correo] --> API_AUTH{Token}
        API_AUTH --> API_PULL[/api/sync/pull: deltas por cursor]
        API_AUTH --> API_PUSH[/api/sync/push: cola de comandos]
        API_AUTH --> API_CICLOS[/api/sync/ciclos-pago]
        API_AUTH --> API_OUT[/api/sync/logout]
        API_LEGADO[/api/mobile/login] --> API_AUTH
        API_AUTH --> API_ROUTE[/api/mobile/collector/route]
        API_AUTH --> API_SYNC[/api/mobile/collector/sync]
        API_AUTH --> API_PROD[/api/mobile/producer/deliveries]
    end
```

## 2. Matriz Exhaustiva de Endpoints y Controladores

| Método | URI | Controlador | Middleware | Rol Permitido |
|---|---|---|---|---|
| GET/POST | `/login` | `AuthController` | guest | Público |
| POST | `/logout` | `AuthController` | auth | Todos |
| GET | `/dashboard` | `DashboardController@index` | auth | Despacho según rol (aislado para productor, acopiador, jefe planta y pagador) |
| GET | `/acopio` | `CollectionController@index` | auth | Acopiador, Admin, Jefe General |
| POST | `/acopio/ruta/{route}/entrega` | `CollectionController@recordProducerDelivery` | auth | Acopiador |
| POST | `/acopio/ruta/{route}/descargar` | `CollectionController@completeAndSendToPlant` | auth | Acopiador |
| GET | `/acopio/historial` | `CollectionController@history` | auth | Acopiador, Admin |
| GET | `/planta/verificacion` | `PlantReceptionController@index` | auth | Jefe Producción, Admin |
| POST | `/planta/verificar/{route}` | `PlantReceptionController@verifyReception` | auth | Jefe Producción |
| GET | `/calidad` | `QualityController@index` | auth | Inspector Calidad, Admin |
| GET/POST | `/calidad/analisis` | `QualityController@store` | auth | Inspector Calidad, Admin |
| POST | `/calidad/cita/{visit}/completar` | `QualityController@completeVisit` | auth | Inspector Calidad, Admin |
| GET | `/ventas` | `SalesController@index` | auth | Personal Venta, Admin |
| POST | `/ventas` | `SalesController@store` | auth | Personal Venta, Admin |
| POST | `/ventas/cierre-caja` | `SalesController@closeCashRegister` | auth | Personal Venta, Admin |
| GET | `/ventas/recibos` | `SalesController@receipts` | auth | Personal Venta, Admin |
| GET | `/ventas/recibo/{sale}` | `SalesController@showReceipt` | auth | Personal Venta, Productor, Admin |
| GET | `/pagos/ruta` | `FieldPaymentController@index` | auth | Pagador de Campo, Admin |
| POST | `/pagos/ruta/pagar/{producer}` | `FieldPaymentController@payProducer` | auth | Pagador de Campo |
| GET | `/pagos/ruta/recibo/{settlement}`| `FieldPaymentController@receipt` | auth | Pagador de Campo, Admin |
| GET | `/pagos/ruta/historial` | `FieldPaymentController@history` | auth | Pagador de Campo, Admin |
| GET | `/admin/pagos/autorizacion` | `PaymentAuthorizationController@index` | auth | Personal Pago, Admin |
| POST | `/admin/pagos/autorizar/{producer}` | `PaymentAuthorizationController@authorizeSingle` | auth | Personal Pago, Admin |
| POST | `/admin/pagos/autorizar-todos` | `PaymentAuthorizationController@authorizeAll` | auth | Personal Pago, Admin |
| GET/POST | `/admin/precios` | `PriceController` | auth | Admin |
| GET/POST | `/zonas` | `ZoneController@index` | auth | Admin, Jefe General |
| GET | `/zonas/solicitudes` | `ZoneController@requests` | auth | Admin, Jefe General |
| POST | `/zonas/solicitudes/{id}/aprobar` | `ZoneController@approveRequest` | auth | Admin |
| POST | `/zonas/solicitudes/{id}/rechazar` | `ZoneController@rejectRequest` | auth | Admin |
| GET | `/productor/acopio` | `ProductorController@acopio` | auth | Productor |
| GET/POST | `/productor/zonas` | `ProductorController@zonas` | auth | Productor |
| GET | `/productor/descuentos` | `ProductorController@descuentos` | auth | Productor |
| GET | `/productor/pagos` | `ProductorController@pagos` | auth | Productor |
| GET | `/productor/pagos/{id}/recibo` | `ProductorController@reciboPago` | auth | Productor |
| GET | `/productor/calidad` | `ProductorController@calidad` | auth | Productor |
| GET/POST | `/anuncios` | `AnnouncementController` | auth | Admin |
| POST | `/api/mobile/login` | `MobileSyncController@login` | api | App Móvil |
| GET | `/api/mobile/collector/route` | `MobileSyncController@getCollectorRoute` | auth:sanctum | Acopiador |
| POST | `/api/mobile/collector/sync` | `MobileSyncController@syncDeliveries` | auth:sanctum | Acopiador |
| GET | `/api/mobile/producer/deliveries` | `MobileSyncController@getProducerDeliveries` | auth:sanctum | Productor |
| POST | `/api/sync/login` | `SyncController@login` | api | App Móvil (DNI o correo) |
| POST | `/api/sync/logout` | `SyncController@logout` | auth:sanctum | App Móvil |
| GET·POST | `/api/sync/pull` | `SyncController@pull` | auth:sanctum | Todos los roles, acotado por ámbito |
| POST | `/api/sync/push` | `SyncController@push` | auth:sanctum | Según comando (`config/sync.php`) |
| GET | `/api/sync/ciclos-pago` | `SyncController@ciclosPago` | auth:sanctum | Pagos, Pagador, Admin, Jefe General |

> Los endpoints `/api/mobile/*` son los del primer prototipo y siguen operativos. La app actual usa exclusivamente `/api/sync/*`; el detalle del contrato está en [Módulo Sincronización Offline-First](modules/sincronizacion.md).
