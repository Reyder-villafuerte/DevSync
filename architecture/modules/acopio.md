# 📦 Módulo: Acopio y Zonas Huata (4:30 AM)

## 1. Diagrama de Relaciones y Flujo Operativo

```mermaid
graph TD
    Z[Zonas 1 a 4] --> CR[CollectionRoute: 04:30 AM]
    U_C[Acopiador] --> CR
    U_P[Productores] --> REC[CollectionRecord: Litros en Ruta]
    CR --> REC
    
    REC --> REORDEN[Reordenamiento Dinámico: Registrados bajan, Pendientes arriba]
    
    CR -- "Cerrar Ruta" --> DESCARGA[Status: descargada_planta / Bloqueo Inmutable]
    DESCARGA --> PLANTA[Recepción Planta: Caudalímetro]
    PLANTA --> CUADRE[Cuadre: Litros Campo vs Caudalímetro -> Merma / Observaciones]
    CUADRE --> HISTORIAL[/acopio/historial: Reporte con Diferencias y Detalle con Ojo]
```

## 2. Componentes Clave

| Componente | Archivo | Responsabilidad |
|---|---|---|
| Modelo `Zone` | `app/Models/Zone.php` | 4 zonas de Huata (Urbana Central, Sur, Norte, Faón/Jhochi/Karata) |
| Modelo `CollectionRoute` | `app/Models/CollectionRoute.php` | Planilla diaria por zona a las 4:30 AM con bloqueo tras cierre |
| Modelo `CollectionRecord` | `app/Models/CollectionRecord.php` | Litros individuales por productor con hora y observaciones |
| Modelo `ZoneChangeRequest` | `app/Models/ZoneChangeRequest.php` | Solicitudes de cambio de zona por rotación de pastoreo |
| Controlador `CollectionController` | `app/Http/Controllers/CollectionController.php` | Planilla de 4:30 AM, buscador en vivo, cierre de ruta e historial de mermas |
| Controlador `ZoneController` | `app/Http/Controllers/ZoneController.php` | Gestión de zonas y aprobación/rechazo de solicitudes |
| Vistas `acopio/*` | `resources/views/acopio/` | `index.blade.php`, `history.blade.php` |

## 3. Reglas Operativas

1. **Aislamiento del Acopiador:** El acopiador no ve el Dashboard general ni la gestión de zonas; al ingresar es redirigido directamente a `/acopio`.
2. **Ergonomía de Campo:** Buscador en tiempo real por DNI/nombre, ordenamiento dinámico donde los productores registrados se desplazan al final y modal para carga ágil de litros.
3. **Historial y Cuadre de Mermas:** Visor inferior e historial dedicado que reporta los litros de campo vs los medidos por el caudalímetro en planta, visibilizando mermas y notas del Jefe de Planta con modal de desglose individual (ícono de ojo).
