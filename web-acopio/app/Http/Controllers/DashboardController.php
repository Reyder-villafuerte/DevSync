<?php

namespace App\Http\Controllers;

use App\Models as M;
use App\Services\BusinessWeek;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();

        if (!$user) {
            abort(401);
        }

        $role = $user->roles->first();

        if (!$role) {
            abort(403, 'El usuario no tiene un rol asignado.');
        }

        $slug = $role->slug;

        [$start, $end] = BusinessWeek::bounds();

        $deliveries = M\Entrega::whereDate('fecha_hora', today());

        $quality = M\PruebaCalidad::whereDate('fecha_hora', today());

        $production = M\LoteProduccion::whereDate('fecha', today());

        $sales = M\Venta::whereDate('fecha', today());

        $stats = match ($slug) {

            'admin' => [
                'Usuarios' => M\User::count(),

                'Solicitudes pendientes' =>
                M\User::where('status', 'PENDIENTE')->count(),

                'Productores activos' =>
                M\Productor::where('activo', true)->count(),

                'Acopiadores activos' =>
                M\Acopiador::where('activo', true)->count(),

                'Entregas del día' => (clone $deliveries)->count(),

                'Litros recibidos' => (clone $deliveries)->sum('litros'),

                'Alertas' =>
                M\ProblemaLeche::where('estado', 'ABIERTO')->count(),
            ],

            'acopiador' => [
                'Litros recogidos hoy' => (clone $deliveries)
                    ->where('acopiador_id', $user->acopiador?->id ?? 0)
                    ->sum('litros'),

                'Recojos realizados' => (clone $deliveries)
                    ->where('acopiador_id', $user->acopiador?->id ?? 0)
                    ->count(),
            ],

            'supervisor' => [
                'Muestras evaluadas' => (clone $quality)->count(),

                'Adulteraciones' => (clone $quality)
                    ->where('agua_agregada_porcentaje', '>', 0)
                    ->count(),

                'Rechazos por acidez' => (clone $quality)
                    ->where('ph', '<', 6.5)
                    ->count(),
            ],

            'produccion' => [
                'Leche procesada (L)' => (clone $production)->sum('litros_leche'),

                'Quesos producidos' => (clone $production)
                    ->where('tipo_producto', '!=', 'Yogurt')
                    ->sum('moldes_obtenidos'),

                'Rendimiento (%)' => round(
                    (clone $production)->sum('moldes_obtenidos')
                        /
                        max(
                            1,
                            (clone $production)->sum('litros_leche')
                        )
                        * 100,
                    2
                ),
            ],

            'despacho' => [
                'Stock disponible' =>
                M\StockQueso::sum('cantidad'),

                'Quesos vendidos hoy' => (clone $sales)->sum('cantidad'),

                'Ventas del día (S/)' => (clone $sales)->sum('total'),
            ],

            'productor' => [
                'Litros entregados esta semana' =>
                M\Entrega::where(
                    'productor_id',
                    $user->productor?->id ?? 0
                )
                    ->whereBetween('fecha_hora', [$start, $end])
                    ->sum('litros'),

                'Entregas realizadas' =>
                M\Entrega::where(
                    'productor_id',
                    $user->productor?->id ?? 0
                )
                    ->whereBetween('fecha_hora', [$start, $end])
                    ->count(),

                'Liquidaciones por cobrar' =>
                M\Liquidacion::where(
                    'productor_id',
                    $user->productor?->id ?? 0
                )
                    ->where('estado', 'Calculada')
                    ->sum('total'),
            ],

            default => [],
        };

        $primary = match ($slug) {

            'admin' => [
                'solicitudes',
                'SOLICITUDES DE REGISTRO',
            ],

            'acopiador' => [
                'entregas/create',
                'Nueva recolección',
            ],

            'supervisor' => [
                'calidad/create',
                'Control de calidad',
            ],

            'produccion' => [
                'lotes/create',
                'Registrar Lote de Producción',
            ],

            'despacho' => [
                'ventas/create',
                'Registrar Venta de Queso',
            ],

            'productor' => [
                'entregas',
                'Ver mis entregas',
            ],

            default => [
                'dashboard',
                'Dashboard',
            ],
        };

        $module = match ($slug) {

            'supervisor' => 'calidad',

            'produccion' => 'lotes',

            'despacho' => 'ventas',

            default => 'entregas',
        };

        $recent = app(ModuleController::class)
            ->query($module)
            ->latest('id')
            ->limit(5)
            ->get();

        $routes = $slug === 'acopiador'
            ? M\Ruta::with('sectores')
            ->where(
                'acopiador_id',
                $user->acopiador?->id ?? 0
            )
            ->where('activo', true)
            ->get()
            : collect();

        $pending = M\SyncRecord::where(
            'user_id',
            $user->id
        )
            ->where('estado', '!=', 'ENVIADO')
            ->count();

        return view(
            'dashboard',
            compact(
                'user',
                'slug',
                'stats',
                'primary',
                'recent',
                'routes',
                'pending',
                'start',
                'end',
                'module'
            )
        );
    }
}
