# 🥛 Módulo: Portal del Productor / Proveedor (Huata)

## 1. Diagrama de Navegación del Proveedor

```mermaid
graph TD
    PROD[Productor / Proveedor] --> ACOPIO[/productor/acopio: Hoy + Acumulado Semanal]
    ACOPIO --> FILTER[Filtros: Día / Semana / Mes]
    PROD --> ZONAS[/productor/zonas: Rotación de Pastoreo]
    PROD --> DESC[/productor/descuentos: Deducciones Queso y Adulteración]
    DESC --> RECIBO_QUESO[/ventas/recibo/:sale: Recibo de Queso a Cuenta]
    PROD --> PAGOS[/productor/pagos: Liquidaciones Semanales]
    PAGOS --> RECIBO_SOBRE[/productor/pagos/:id/recibo: Recibo Formal de Liquidación]
    PROD --> CALIDAD[/productor/calidad: Lactoscan & Citas Técnicas]
```

## 2. Componentes Clave

| Componente | Archivo | Responsabilidad |
|---|---|---|
| Controlador `ProductorController` | `app/Http/Controllers/ProductorController.php` | Lógica de entregas, acumulador semanal con reinicio automático al pagar ciclo, descuentos, pagos y calidad |
| Vistas `productor/*` | `resources/views/productor/` | `acopio.blade.php`, `zonas.blade.php`, `descuentos.blade.php`, `pagos.blade.php`, `recibo_pago.blade.php`, `calidad.blade.php` |

## 3. Reglas Operativas

* **Acumulador Semanal:** Se calcula desde el día siguiente a la última liquidación en estado `pagado`. Al pagarse el sobre, el acumulador semanal se reinicia automáticamente.
* **Transparencia en Descuentos:** El productor puede revisar cada deducción por compra de queso con enlace directo a su recibo formal de venta (`/ventas/recibo/{sale}`) con opción de impresión.
* **Trazabilidad de Calidad:** Reporte de Lactoscan transparente que indica porcentaje de agua, acidez, densidad y visitas técnicas programadas o realizadas.
