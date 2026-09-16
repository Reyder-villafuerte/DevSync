<?php

namespace App\Services;

class Navigation
{
    public static function menus(): array
    {
        return [
            'admin' => ['dashboard' => 'Inicio', 'solicitudes' => 'Solicitudes de registro', 'users' => 'Usuarios', 'producers' => 'Productores', 'acopiadores' => 'Acopiadores', 'zones' => 'Zonas', 'sectors' => 'Sectores', 'routes' => 'Rutas', 'entregas' => 'Entregas directas', 'liquidaciones' => 'Liquidaciones', 'rotaciones' => 'Rotación estacional', 'comunicados' => 'Comunicados', 'reports' => 'Reportes', 'settings' => 'Configuración', 'audit' => 'Auditoría'],
            'acopiador' => ['dashboard' => 'Inicio', 'producers' => 'Productores', 'entregas' => 'Entregas hoy', 'entregas/create' => 'Nueva recolección', 'sync' => 'Sincronización', 'ruta/cerrar' => 'Cerrar Ruta y Descargar'],
            'supervisor' => ['dashboard' => 'Inicio', 'calidad' => 'Control de calidad', 'calidad/create' => 'Entregas por revisar', 'problemas' => 'Problemas registrados', 'inspecciones' => 'Inspecciones de hoy', 'capacitaciones' => 'Capacitación BPO', 'reportes' => 'Reportes'],
            'produccion' => ['dashboard' => 'Inicio', 'lotes' => 'Lotes de producción', 'lotes/create' => 'Registrar Lote de Producción', 'hoy' => 'Producción de hoy', 'reportes' => 'Reportes', 'sync' => 'Sincronización'],
            'despacho' => ['dashboard' => 'Inicio', 'ventas' => 'Ventas', 'ventas/create' => 'Registrar Venta de Queso', 'stock' => 'Stock disponible', 'reportes' => 'Reportes'],
            'productor' => ['dashboard' => 'Inicio', 'entregas' => 'Mis entregas', 'calidad' => 'Resultados de calidad', 'liquidaciones' => 'Mis liquidaciones', 'comunicados' => 'Comunicados de planta', 'rotaciones' => 'Rotación estacional', 'perfil' => 'Mi perfil'],
        ];
    }
}
