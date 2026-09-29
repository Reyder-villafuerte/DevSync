<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Exceptions\ReglaNegocioException;
use App\Models\User;
use App\Models\SystemPrice;
use App\Services\Pagos\LiquidacionService;

class PaymentAuthorizationController extends Controller
{
    private const DIAS_SEMANA = [
        0 => 'Domingo',
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
    ];

    public function __construct(private LiquidacionService $liquidaciones)
    {
    }

    /**
     * Panel de Autorización y Liquidación de Pagos a Proveedores.
     *
     * El cálculo del sobre vive en LiquidacionService: aquí solo se le agregan
     * los datos de presentación (desglose día por día y detalle de adulteración).
     */
    public function index()
    {
        $producers = User::where('role', 'productor')
            ->where('is_active', true)
            ->with(['zone'])
            ->orderBy('name')
            ->get();

        $today = Carbon::today()->format('Y-m-d');
        $pendientesList = [];

        $totalLitrosPendientes = 0;
        $totalBrutoPendiente = 0;
        $totalDeduccionesPendientes = 0;
        $totalNetoPendiente = 0;

        $systemPrice = SystemPrice::current();

        foreach ($producers as $prod) {
            // Si ya cuenta con una liquidación autorizada esperando pago en campo, omitir de pendientes
            if ($this->liquidaciones->sobreAutorizado($prod)) {
                continue;
            }

            $ciclo = $this->liquidaciones->calcularCiclo($prod);

            if ($ciclo['liters'] <= 0 && $ciclo['total_deductions'] <= 0) {
                continue;
            }

            $dailyBreakdown = $ciclo['records']->map(function ($rec) {
                $cDate = Carbon::parse($rec->route->date);

                return (object) [
                    'date' => $rec->route->date,
                    'date_formatted' => $cDate->format('d/m/Y'),
                    'day_name' => self::DIAS_SEMANA[$cDate->dayOfWeek] ?? $cDate->format('l'),
                    'liters' => (float) $rec->liters,
                    'time' => $rec->collected_at ? substr($rec->collected_at, 0, 5) : '04:30',
                    'collector' => $rec->route->collector ? $rec->route->collector->name : 'Acopiador Huata',
                    'zone' => $rec->route->zone ? $rec->route->zone->name : 'Zona General',
                    'notes' => $rec->notes,
                ];
            })->values();

            $adulterationDetails = null;
            if ($ciclo['adulteration_found']) {
                $peor = $ciclo['worst_analysis'];
                $cDateWater = Carbon::parse($peor->analysis_date);

                $adulterationDetails = (object) [
                    'date' => $peor->analysis_date,
                    'date_formatted' => $cDateWater->format('d/m/Y'),
                    'day_name' => self::DIAS_SEMANA[$cDateWater->dayOfWeek] ?? '',
                    'water_percentage' => (float) $peor->water_addition_percentage,
                    'penalty_per_liter' => $ciclo['penalty_per_liter'],
                    'penalty_total' => $ciclo['water_penalty_total'],
                    'penalty_type' => $ciclo['price_info']['penalty_type'],
                    'effective_price' => $ciclo['effective_price'],
                    'base_price' => $ciclo['base_price'],
                ];
            }

            $pendientesList[] = (object) [
                'producer' => $prod,
                'start_date' => $ciclo['start_date'],
                'end_date' => $ciclo['end_date'],
                'liters' => $ciclo['liters'],
                'daily_breakdown' => $dailyBreakdown,
                'price_info' => $ciclo['price_info'],
                'base_price' => $ciclo['base_price'],
                'effective_price' => $ciclo['effective_price'],
                'gross_base' => $ciclo['gross_base'],
                'cheese_deductions' => $ciclo['cheese_deductions'],
                'cheese_deductions_total' => $ciclo['cheese_deductions_total'],
                'adulteration_found' => $ciclo['adulteration_found'],
                'adulteration_details' => $adulterationDetails,
                'water_penalty_total' => $ciclo['water_penalty_total'],
                'total_deductions' => $ciclo['total_deductions'],
                'net' => $ciclo['net'],
            ];

            $totalLitrosPendientes += $ciclo['liters'];
            $totalBrutoPendiente += $ciclo['gross_base'];
            $totalDeduccionesPendientes += $ciclo['total_deductions'];
            $totalNetoPendiente += $ciclo['net'];
        }

        return view('admin.pagos.autorizacion', compact(
            'pendientesList',
            'totalLitrosPendientes',
            'totalBrutoPendiente',
            'totalDeduccionesPendientes',
            'totalNetoPendiente',
            'systemPrice',
            'today'
        ));
    }

    /** Autorizar la liquidación de un productor individual. */
    public function authorizeSingle(Request $request, $producerId)
    {
        $producer = User::where('role', 'productor')->findOrFail($producerId);

        try {
            $settlement = $this->liquidaciones->autorizar($producer, Auth::user());
        } catch (ReglaNegocioException $e) {
            return back()->with('error', $e->getMessage());
        }

        if (!$settlement) {
            return back()->with('error', "No hay litros ni deducciones pendientes para liquidar a {$producer->name}.");
        }

        return back()->with('success', "Pago liquidado y cancelado a {$producer->name} por S/ {$settlement->net_total}. Ciclo semanal reiniciado con éxito.");
    }

    /** Autorizar de golpe todos los pagos pendientes. */
    public function authorizeAll(Request $request)
    {
        $producers = User::where('role', 'productor')->where('is_active', true)->get();
        $count = 0;

        foreach ($producers as $producer) {
            if ($this->liquidaciones->sobreAutorizado($producer)) {
                continue;
            }

            if ($this->liquidaciones->autorizar($producer, Auth::user())) {
                $count++;
            }
        }

        return back()->with('success', "Se autorizaron y pagaron exitosamente las liquidaciones de {$count} productores. Todos los acumuladores fueron reiniciados.");
    }
}
