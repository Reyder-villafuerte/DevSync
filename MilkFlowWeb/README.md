# 🥛 MilkFlowWeb

MilkFlowWeb es la aplicación web de **DevSync** para gestionar el flujo operativo de la planta de lácteos de **Huata**.

El sistema permite administrar las principales actividades de la empresa:

* 🥛 Acopio de leche
* 🏭 Recepción de leche en planta
* 🧀 Producción
* 🛒 Ventas
* 🧪 Control de calidad
* 💰 Pagos a productores
* 📍 Zonas de acopio
* 📊 Finanzas

---

## 🛠️ Tecnologías

El proyecto está construido con:

* **Laravel 13**
* **PHP 8.3**
* **MySQL 8 / MariaDB**
* **Vite**
* **Tailwind CSS**
* **Bootstrap**
* **Composer**
* **Node.js**
* **npm**

La base de datos incluye **migraciones y seeders con datos de demostración** para facilitar la instalación y las pruebas.

---

# 📋 Requisitos

Antes de ejecutar el proyecto debes tener instalado:

| Herramienta | Versión recomendada       |
| ----------- | ------------------------- |
| PHP         | 8.3                       |
| Composer    | Última versión compatible |
| Node.js     | 18 o superior             |
| npm         | Incluido con Node.js      |
| MySQL       | 8 o superior              |
| MariaDB     | Compatible                |
| Git         | Última versión            |

Puedes comprobar las versiones instaladas con:

```bash
php -v
composer -V
node -v
npm -v
mysql --version
git --version
```

---

# 🚀 Instalación

## 1. Clonar o descargar el proyecto

Si todavía no tienes el proyecto:

```bash
git clone URL_DEL_REPOSITORIO
```

Luego entra a la carpeta:

```bash
cd MilkFlowWeb
```

Si ya tienes el proyecto descargado, simplemente entra a su carpeta:

```bash
cd MilkFlowWeb
```

---

## 2. Instalar dependencias de Laravel

Ejecuta:

```bash
composer install
```

Este comando instala todas las dependencias PHP definidas en:

```text
composer.json
```

---

## 3. Crear el archivo `.env`

Copia el archivo de configuración de ejemplo:

### Windows CMD

```cmd
copy .env.example .env
```

### PowerShell

```powershell
Copy-Item .env.example .env
```

### Linux / macOS

```bash
cp .env.example .env
```

---

## 4. Generar la clave de Laravel

Ejecuta:

```bash
php artisan key:generate
```

Esto genera automáticamente la clave:

```env
APP_KEY=
```

dentro del archivo `.env`.

---

# 🗄️ Configuración de la base de datos

Antes de ejecutar las migraciones, debes tener **MySQL o MariaDB funcionando**.

Crea una base de datos, por ejemplo:

```text
milkflow
```

Después abre el archivo:

```text
.env
```

y configura los datos de conexión.

Ejemplo:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=milkflow
DB_USERNAME=root
DB_PASSWORD=
```

> Los valores de `DB_USERNAME` y `DB_PASSWORD` dependen de la configuración de MySQL de cada computadora.

---

# 🏗️ Crear la base de datos

Cuando MySQL esté funcionando y el `.env` esté configurado correctamente, ejecuta:

```bash
php artisan migrate --seed
```

Este comando realiza dos tareas:

### `migrate`

Crea las tablas definidas en:

```text
database/migrations
```

### `--seed`

Carga los datos iniciales y datos de demostración definidos en:

```text
database/seeders
```

Por ejemplo, pueden cargarse usuarios, productores, zonas, productos y otros datos necesarios para realizar pruebas.

---

# 📦 Instalar dependencias del frontend

Ejecuta:

```bash
npm install
```

Este comando instala las dependencias definidas en:

```text
package.json
```

Se generará la carpeta:

```text
node_modules
```

---

# 🎨 Compilar el frontend

Después de instalar las dependencias:

```bash
npm run build
```

Vite compilará los recursos frontend del proyecto.

---

# ▶️ Ejecutar MilkFlowWeb

Una vez terminada la instalación, inicia Laravel:

```bash
php artisan serve
```

Laravel mostrará una dirección similar a:

```text
http://127.0.0.1:8000
```

Abre esa dirección en el navegador.

---

# ⚡ Instalación rápida

Si ya tienes PHP, Composer, Node.js y MySQL configurados, puedes ejecutar:

```bash
cd MilkFlowWeb

composer install

copy .env.example .env

php artisan key:generate

php artisan migrate --seed

npm install

npm run build

php artisan serve
```

Luego abre:

```text
http://127.0.0.1:8000
```

---

# 🔄 Desarrollo

Durante el desarrollo puedes utilizar:

```bash
php artisan serve
```

y, en otra terminal:

```bash
npm run dev
```

`npm run dev` mantiene Vite ejecutándose para actualizar los recursos frontend mientras desarrollas.

---

# 📁 Estructura principal

```text
MilkFlowWeb/
│
├── app/
│   ├── Http/
│   ├── Models/
│   └── ...
│
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
│
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
│
├── routes/
│   ├── web.php
│   └── api.php
│
├── public/
│
├── storage/
│
├── .env.example
├── artisan
├── composer.json
├── package.json
└── vite.config.js
```

---

# 🧪 Datos de demostración

El proyecto incluye **seeders de demostración**.

Para cargar los datos iniciales:

```bash
php artisan db:seed
```

O, si necesitas crear nuevamente la estructura de la base de datos y cargar los datos desde cero:

```bash
php artisan migrate:fresh --seed
```

> ⚠️ `migrate:fresh --seed` elimina las tablas existentes y vuelve a crearlas. Úsalo solamente en desarrollo o cuando estés seguro de que no necesitas conservar los datos actuales.

---

# 🔧 Comandos útiles

### Ver las rutas

```bash
php artisan route:list
```

### Limpiar caché

```bash
php artisan optimize:clear
```

### Ver migraciones

```bash
php artisan migrate:status
```

### Ejecutar migraciones pendientes

```bash
php artisan migrate
```

### Ejecutar seeders

```bash
php artisan db:seed
```

### Compilar frontend

```bash
npm run build
```

### Ejecutar frontend en desarrollo

```bash
npm run dev
```

### Iniciar Laravel

```bash
php artisan serve
```

---

# 👥 Flujo recomendado para el equipo

Cuando un integrante descargue el proyecto por primera vez:

```text
1. Clonar repositorio
        ↓
2. composer install
        ↓
3. Crear .env
        ↓
4. php artisan key:generate
        ↓
5. Configurar MySQL
        ↓
6. php artisan migrate --seed
        ↓
7. npm install
        ↓
8. npm run build
        ↓
9. php artisan serve
        ↓
10. Abrir http://127.0.0.1:8000
```

---

# ⚠️ Importante

No se debe subir el archivo:

```text
.env
```

al repositorio.

El archivo que sí debe mantenerse en Git es:

```text
.env.example
```

Cada integrante debe crear su propio `.env` a partir de `.env.example`.

También se recomienda no subir:

```text
node_modules/
vendor/
.env
```

Estos archivos y carpetas deben estar incluidos en `.gitignore`.

---

# 📌 Resumen

MilkFlowWeb es el sistema web de gestión de DevSync para el flujo operativo de la planta de lácteos de Huata.

Para ejecutarlo localmente se necesita:

```text
PHP 8.3
Composer
Node.js
npm
MySQL/MariaDB
Git
```

La instalación básica es:

```bash
composer install
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Finalmente, acceder desde el navegador a:

```text
http://127.0.0.1:8000
```
