# 🥛 Cuadro comparativo — MilkFlow

## 📱 KMP vs Flutter vs React Native

**Equipo:** DevSync — Semana 1
**Proyecto:** MilkFlow — Gestión del acopio de leche

| **Dimensión**                          | **Kotlin Multiplatform (KMP)**                                                                                                                                                                              | **Flutter**                                                                                                                              | **React Native**                                                                                                           |
| -------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------- |
| **Lenguaje y curva de aprendizaje**    | Kotlin. Es una buena alternativa si el equipo ya tiene conocimientos de Java/Kotlin. Permite desarrollar la lógica de MilkFlow utilizando un lenguaje conocido y facilita el aprendizaje progresivo de KMP. | Dart. Requiere aprender un nuevo lenguaje, aunque cuenta con una sintaxis sencilla y documentación amplia.                               | JavaScript/TypeScript. Es conveniente si el equipo tiene experiencia previa con desarrollo web y React.                    |
| **Estrategia de UI**                   | Permite utilizar **Compose Multiplatform**, facilitando la creación de una interfaz moderna para registrar productores, acopios y entregas de leche.                                                        | Utiliza widgets propios de Flutter para construir la interfaz de la aplicación. Permite desarrollar una UI compartida entre plataformas. | Utiliza componentes basados en React y permite construir interfaces reutilizables para la aplicación móvil.                |
| **Qué se comparte entre plataformas**  | Permite compartir la lógica de negocio, modelos, almacenamiento y otras funcionalidades. También puede compartirse la interfaz mediante Compose Multiplatform.                                              | Permite compartir gran parte del código de la aplicación entre diferentes plataformas.                                                   | Permite compartir principalmente la lógica y componentes de la interfaz, utilizando módulos nativos cuando son necesarios. |
| **Funcionamiento offline**             | ⭐ Muy adecuado. Permite implementar almacenamiento local para registrar datos de productores, litros de leche y acopios cuando no exista conexión a internet.                                               | ⭐ Adecuado. Cuenta con diferentes alternativas para almacenamiento local y aplicaciones offline.                                         | ⭐ Adecuado. Puede utilizar bases de datos y almacenamiento local mediante librerías del ecosistema.                        |
| **Rendimiento**                        | 🚀 Cercano al rendimiento nativo, especialmente conveniente para una aplicación que debe funcionar correctamente en dispositivos Android utilizados en zonas rurales.                                       | 🚀 Buen rendimiento gracias a su propio motor de renderizado.                                                                            | ⚡ Buen rendimiento mediante su arquitectura moderna y componentes nativos.                                                 |
| **Base de datos local**                | Permite integrar soluciones como **Room** y otras alternativas compatibles con Kotlin Multiplatform para almacenar información localmente.                                                                  | Cuenta con diferentes paquetes para SQLite, almacenamiento local y otras bases de datos.                                                 | Dispone de múltiples librerías de almacenamiento local dentro del ecosistema npm.                                          |
| **Acceso a funciones del dispositivo** | Permite acceder a funcionalidades nativas mediante APIs específicas de cada plataforma, algo útil para MilkFlow si posteriormente se incorpora cámara, ubicación o notificaciones.                          | Dispone de numerosos plugins para acceder a funciones nativas del dispositivo.                                                           | Permite utilizar módulos nativos y librerías para acceder a funcionalidades del dispositivo.                               |
| **Ecosistema**                         | Ecosistema en crecimiento, especialmente orientado a proyectos Kotlin y desarrollo multiplataforma.                                                                                                         | Ecosistema amplio con una gran cantidad de paquetes y widgets.                                                                           | Ecosistema muy amplio gracias a JavaScript, npm y React.                                                                   |
| **Empresa respaldante**                | JetBrains, con fuerte colaboración de Google en el ecosistema Android.                                                                                                                                      | Google.                                                                                                                                  | Meta.                                                                                                                      |
| **Requisitos en Windows**              | Android Studio, JDK y Android SDK. Para el desarrollo Android de MilkFlow, Windows es suficiente.                                                                                                           | Flutter SDK, Android Studio o Android SDK y las herramientas necesarias para ejecutar el proyecto.                                       | Node.js, npm, Android Studio y Android SDK.                                                                                |
| **Adecuación para MilkFlow**           | ⭐⭐⭐⭐⭐ Excelente. Se adapta al requisito de trabajar **offline-first**, permite utilizar Kotlin y facilita el acceso a funcionalidades nativas.                                                              | ⭐⭐⭐⭐ Buena alternativa para desarrollar rápidamente una aplicación multiplataforma.                                                      | ⭐⭐⭐⭐ Buena alternativa, especialmente si el equipo tiene experiencia previa con JavaScript/TypeScript.                     |

---

## 📚 Fuentes (APA 7)

* JetBrains. (2026). *Kotlin Multiplatform documentation*. Kotlin Documentation.
* JetBrains. (2026). *Kotlin Multiplatform vs. React Native: A cross-platform comparison*. Kotlin Documentation.
* Flutter. (2026). *Flutter documentation*. Google.
* React Native. (2026). *React Native documentation*. Meta.
* Android Developers. (2026). *Build an offline-first app*. Google.

---

## 🎯 Conclusión del equipo

Para **MilkFlow**, nuestro equipo **DevSync** eligió **Kotlin Multiplatform (KMP) junto con Compose Multiplatform**.

La elección se debe principalmente a que el proyecto está orientado al **acopio de leche**, donde la conectividad a internet puede ser limitada. Por esta razón, consideramos importante que la aplicación pueda trabajar bajo un enfoque **offline-first**, permitiendo registrar productores, entregas y cantidades de leche incluso cuando no exista conexión.

Además, KMP nos permite trabajar con **Kotlin**, lenguaje que resulta adecuado para el desarrollo Android y que facilita la integración con funcionalidades nativas del dispositivo.

🥛 **MilkFlow busca digitalizar y mejorar el control del acopio de leche**, reemplazando progresivamente los registros manuales realizados en cuadernos por una solución móvil organizada, accesible y preparada para trabajar en entornos con conectividad limitada.

### ✅ Tecnología seleccionada

**Kotlin Multiplatform + Compose Multiplatform**

**Motivos principales:**

* 📱 Desarrollo orientado principalmente a Android.
* 📴 Soporte para una estrategia **offline-first**.
* 🗄️ Posibilidad de almacenamiento local.
* ⚡ Buen rendimiento.
* 🔐 Integración con funcionalidades nativas.
* 👨‍💻 Uso de Kotlin.
* 🥛 Adaptación directa a las necesidades de **MilkFlow**.

