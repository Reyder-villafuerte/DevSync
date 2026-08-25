# MilkFlow

## Descripción
Aplicación móvil multiplataforma orientada a la gestión del acopio de leche en zonas rurales. Permite digitalizar el registro de entregas diarias, gestionar a los productores (socios) y administrar los precios vigentes.

## Problema que busca resolver
Sustituir los registros manuales en cuadernos físicos en centros de acopio, reduciendo errores en el cálculo de pagos, facilitando el acceso a historiales confiables y permitiendo el trabajo en zonas con conectividad limitada mediante un enfoque **offline-first**.

## Tecnologías
- **Kotlin Multiplatform (KMP)**: Lógica compartida entre plataformas.
- **Compose Multiplatform**: Interfaz de usuario declarativa compartida.
- **Material 3**: Sistema de diseño moderno.
- **Kotlin Coroutines**: Gestión de asincronía.

## Plataformas
- **Android**: Aplicación móvil principal.
- **Desktop (JVM)**: Versión para administración en oficina.
- **iOS**: Soporte base preparado.

## Estructura del proyecto
- `shared/`: Contiene la lógica de negocio y UI compartida.
    - `commonMain/`: Modelos de dominio y pantallas compartidas.
    - `androidMain/`, `iosMain/`, `jvmMain/`: Implementaciones específicas de plataforma.
- `androidApp/`: Punto de entrada para la aplicación Android.

## Modelo de dominio actual
- **Acopio**: Registro de litros, precio y productor.
- **Productor**: Datos del socio (nombre, código, comunidad).
- **Usuario**: Roles de Administrador y Operador.
- **PrecioLecheVigente**: Gestión de precios por temporada.

## Funcionalidades actualmente implementadas
- [x] Modelado de dominio completo.
- [x] Pruebas unitarias para reglas de negocio.
- [x] Estructura base multiplataforma configurada.
- [x] Flujo offline-first conceptualizado en el dominio.

## Estado actual
El proyecto ha completado su fase de migración y limpieza, eliminando todo el código de demostración anterior. La base está lista para la implementación de la navegación y la persistencia de datos.

## Próximos pasos
- Implementación de navegación multiplataforma (Semana 4).
- Persistencia local con base de datos (offline-first).
- Integración de escaneo QR para identificación de productores.
