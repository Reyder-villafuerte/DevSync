# MilkFlowWeb

MilkFlowWeb es la parte web de DevSync para gestionar el flujo operativo de Huata: acopio, recepción en planta, producción, ventas, calidad, pagos, zonas y finanzas.

Está construido con:
- Laravel 13
- PHP 8.3
- MySQL (configuración por defecto)
- Vite + Tailwind + Bootstrap
- Base de datos con seeders de demo

## Requisitos

Antes de iniciar, asegúrate de tener instalado:

- PHP 8.3
- Composer
- Node.js 18+
- npm
- MySQL 8 o MariaDB
- Git

## Inicio rápido

La forma más rápida es esta:

```bash
cd MilkFlowWeb
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
