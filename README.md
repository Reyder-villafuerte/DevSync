# MilkFlow

> Registra y controla la entrega diaria de leche de cada productor, sin depender de internet.

## Problema que resuelve

Los centros de acopio o asociaciones de productores lecheros suelen llevar el registro de las entregas diarias en cuadernos físicos. Esto dificulta calcular el pago mensual a cada productor (más aún si el precio por litro varía según la temporada), detectar errores y llevar un historial confiable. Muchas zonas rurales, además, no tienen conexión a internet estable durante el día.

## Público objetivo

Un centro de acopio o asociación de productores lecheros real, con dos tipos de usuario: el **operador**, que registra la entrega diaria de cada productor, y el **administrador**, que supervisa el precio vigente, revisa reportes y gestiona a los productores.

## Funcionalidades previstas

- F1: Registrar el acopio diario de leche (productor, litros, fecha, precio del día)
- F2: Listar y filtrar los acopios por productor y por fecha
- F3: Iniciar sesión con roles diferenciados (operador / administrador)
- F4: Trabajar sin conexión y sincronizar cuando haya internet
- F5: Gestionar el listado de productores/socios
- F6: Actualizar el precio por litro vigente según la temporada (solo administrador)

## Entidad principal del CRUD

**Acopio**: fecha, productor (relación), litros, precio por litro aplicado, observación de calidad (opcional)

## Entidad secundaria

**Productor**: nombre, código de socio, ubicación/comunidad

## Capacidad nativa prevista

Cámara/QR para identificar rápidamente al productor al momento de registrar su entrega (se implementará en la semana 9)

## Equipo DevSync

| Integrante | Código | Rol semana 1 |
|---|---|---|
| Heiner Apaza Apaza | 20220559 | Coordinación |
| Juana Tito Larico | 202414032 | UI |
| Reyder Villafuerte Yupanqui | 202414014 | Lógica y datos |
| Mayda Rocio Carlos | 202122346 | QA y documentación |

## Tecnologías

Kotlin Multiplatform · Compose Multiplatform · targets Android y Desktop

(iOS preparado: requiere macOS para compilar)
