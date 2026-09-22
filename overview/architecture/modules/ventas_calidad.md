# 📦 Módulo: Ventas, Despacho, Arqueo Diario y Calidad Lactoscan

## 1. Diagrama de Flujo: Despacho, Cierre de Caja y Control de Calidad

```mermaid
graph TD
    subgraph Ventas y Despacho
        CLI[Cliente / Productor] --> VENTA[Registrar Venta de Queso]
        VENTA --> METODO{Modalidad de Pago}
        METODO -- "Efectivo / Mayorista / Local" --> CAJA[Efectivo Real en Caja]
        METODO -- "descuento_leche (Proveedor)" --> A_CUENTA[A Cuenta de Leche: Deducción Semanal]
        
        VENTA --> RECIBO[/ventas/recibo/:sale]
        VENTA --> HOY[Ventas de Hoy]

        HOY --> CIERRE{Botón Cierre de Caja}
        CIERRE --> ARQUEO[Cuadro de Arqueo: Efectivo Físico vs Crédito Proveedor]
        ARQUEO -- "Confirmar Cierre" --> RESET_HOY[Reseteo de Ventas de Hoy a Blanco]
        VENTA --> HIST_RECIBOS[/ventas/recibos: Historial Completo]
    end

    subgraph Calidad Lactoscan & Citas Técnicas
        ZONA[Selector Zonas 1 a 4] --> FILTRO_PROD[Filtrar Productores con Acopio del Día]
        FILTRO_PROD --> REG_ANALISIS[Registro Lactoscan: Grasa, SNF, Densidad, Agua, Acidez, Temp]
        
        REG_ANALISIS --> VEREDICTO{Veredicto Fisicoquímico}
        VEREDICTO -- "Conforme" --> OK[Aprobado]
        VEREDICTO -- "Agua Detectada" --> PENALIDAD[Penalidad Distrital Semanal]
        VEREDICTO -- "Acidez Alta" --> CITA_AUTO[Agendamiento Automático de Cita Técnica]

        CITA_AUTO --> AGENDA_HOY[Citas Técnicas del Día]
        AGENDA_HOY --> MODAL_RESOLVER[Completar Cita con Informe de Resolución]
    end
```

## 2. Componentes Clave

| Componente | Archivo | Responsabilidad |
|---|---|---|
| Modelo `Sale` | `app/Models/Sale.php` | Registro de venta, decremento de stock, diferenciación efectivo vs descuento leche |
| Modelo `LactoscanAnalysis` | `app/Models/LactoscanAnalysis.php` | Parámetros físico-químicos (grasa, sólidos, densidad, agua, pH/acidez, temperatura) |
| Modelo `TechnicalVisit` | `app/Models/TechnicalVisit.php` | Citas técnicas agendadas y resolución con informe técnico |
| Controlador `SalesController` | `app/Http/Controllers/SalesController.php` | Despacho, arqueo diario de caja, apartado independiente de recibos y reseteo post-cierre |
| Controlador `QualityController` | `app/Http/Controllers/QualityController.php` | Análisis físico-químicos, selector previo de 4 zonas, resolución de citas técnicas y filtros |
| Vistas `ventas/*` | `resources/views/ventas/` | `index.blade.php`, `receipts.blade.php`, `receipt.blade.php` |
| Modelo `OperationalExpense` | `app/Models/OperationalExpense.php` | Registro de pagos de personal (sueldos/honorarios) y egresos operativos |
| Controlador `FinancialController` | `app/Http/Controllers/FinancialController.php` | Consolidación de ingresos (ventas efectivo/crédito), egresos (proveedores y personal) y balance neto |
| Vistas `admin/finanzas/*` | `resources/views/admin/finanzas/` | `index.blade.php` (Flujo de Caja, KPIs de ingresos/egresos, filtros y registro de egresos) |

* **Arqueo y Cierre de Caja:** El dinero físico real en caja nunca incluye los quesos retirados a cuenta por productores (que se descuentan en la liquidación semanal del viernes). El botón de cierre despliega el cuadro consolidado on-demand y tras el cierre el visor de hoy vuelve a blanco.
* **Inspección de Calidad de Campo:** La selección previa de zona cruza los litros acopiados hoy para priorizar a los productores que entregaron leche en esa ruta específica.
* **Permisos por Rol en Calidad:** El formulario de registro de pruebas Lactoscan es exclusivo del `inspector_calidad`. El `admin` tiene vista exclusiva del historial de reportes y auditoría a ancho completo sin controles de ingreso físico. El Caudalímetro y la operación de lotes quedan reservados al `jefe_produccion`; el `admin` ve el reporte de lotes pero no lo mueve.
* **Flujo Financiero del Administrador:** El `admin` no opera la caja de ventas en mostrador ni realiza la entrega de sobres en ruta. En su lugar, supervisa el módulo de Flujo de Caja consolidando los ingresos por ventas de queso y los egresos por liquidaciones a proveedores y pago de personal.
