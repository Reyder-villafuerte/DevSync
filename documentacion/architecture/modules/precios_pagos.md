# 💰 Módulo: Precios Dinámicos, Autorización Administrativa y Pagos en Ruta (Sobres de Efectivo)

> **Responsabilidad:** Tarifas de leche y queso por temporada, penalidades por agua (Lactoscan), deducciones exclusivas (quesos a cuenta), autorización por la Administración y entrega física de sobres con dinero en efectivo en la ruta de los viernes (`pagador_campo`).

---

## 1. Diagrama del Flujo de Ciclo Semanal y Pagos de Huata

```mermaid
graph TD
    subgraph Miércoles: Cierre de Ciclo
        LECHE[Acopios Semanales] --> CALC[Cálculo de Litros]
        CALIDAD[Lactoscan / Penalidad Agua] --> DEDUC[Deducciones Semanales]
        VENTA_Q[Compras Queso a Cuenta] --> DEDUC
        CALC --> ADM_PANEL[Admin /admin/pagos/autorizacion]
        DEDUC --> ADM_PANEL
        ADM_PANEL -- "Autorizar Pago" --> SETTLE_AUT[ProducerSettlement: autorizado]
    end

    subgraph Jueves: Armado de Sobres
        SETTLE_AUT --> CONTEO[Conteo de Efectivo y Armado Físico de Sobres]
    end

    subgraph Viernes: Pago en Ruta de Acopio
        CONTEO --> CAMIONETA[Custodia de Efectivo en Camioneta]
        SETTLE_AUT --> VISTA_PAGADOR[/pagos/ruta: Planilla de Sobres]
        CAMIONETA --> VISTA_PAGADOR

        VISTA_PAGADOR -- "No Autorizado" --> BLOQUEO[Efectivo Oculto: — Sin Autorizar / Entrega Bloqueada]
        VISTA_PAGADOR -- "Autorizado" --> HABILITADO[Efectivo Visible S/ XXX / Botón Entregar Habilitado]
        
        HABILITADO -- "Entregar Sobre" --> ENTREGA[ProducerSettlement: pagado / paid_by = pagador]
        ENTREGA --> RECIBO_TERMICO[/pagos/ruta/recibo/:id]
        ENTREGA --> HISTORIAL[/pagos/ruta/historial]
    end
```

---

## 2. Componentes Clave

| Componente | Tipo | Archivo | Responsabilidad |
|---|---|---|---|
| `SystemPrice` | Modelo Eloquent | `app/Models/SystemPrice.php` | Tarifas estacionales vigentes de leche y queso |
| `PriceController` | Controlador HTTP | `app/Http/Controllers/PriceController.php` | Actualización de tarifas de temporada |
| `PaymentAuthorizationController` | Controlador HTTP | `app/Http/Controllers/PaymentAuthorizationController.php` | Autorización semanal del Admin (pasa a estado `autorizado`) |
| `FieldPaymentController` | Controlador HTTP | `app/Http/Controllers/FieldPaymentController.php` | Módulo operativo del `pagador_campo`: entrega de sobres, custodia en camioneta, recibos e historial |
| `pagador.index` | Vista Blade | `resources/views/pagador/index.blade.php` | Planilla de sobres por zonas, métricas de efectivo en custodia y acción "Entregar Sobre" |
| `pagador.receipt` | Vista Blade | `resources/views/pagador/receipt.blade.php` | Recibo térmico imprimible del sobre de pago con firmas de conformidad |
| `pagador.history` | Vista Blade | `resources/views/pagador/history.blade.php` | Historial de pagos en campo con reimpresión de comprobantes |

---

## 3. Reglas Operativas Estrictas de Huata

1. **Condición de Autorización Previa:**
   - Si una liquidación **no está autorizada** por la Administración: el efectivo no figura en la planilla (`— Sin Autorizar`), no se suma a la custodia de la camioneta y la entrega está bloqueada a nivel de vista y backend.
   - Si **está autorizada** (`status = autorizado`): el pagador visualiza el importe exacto en efectivo, se habilita el botón "Entregar Sobre", y al confirmarse pasa a `status = pagado` registrando al pagador como responsable físico (`paid_by`).
2. **Exclusividad de Rol "Solo de Pagos":**
   - El `pagador_campo` cuenta con navegación restringida absoluta: no visualiza ni accede a ventas, producción, caudalímetro o tarifas; su dashboard redirige a `/pagos/ruta`.
3. **Penalidad por Agua Semanal (Regla Distrital):**
   - Si en cualquier día del ciclo se detecta agua en Lactoscan (>0%), la penalidad se aplica a la totalidad de litros semanales.
