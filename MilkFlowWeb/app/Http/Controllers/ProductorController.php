<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Zone;
use App\Models\ZoneChangeRequest;
use App\Models\CollectionRecord;
use App\Models\ProducerSettlement;
use App\Models\ProducerDeduction;
use App\Models\LactoscanAnalysis;
use App\Models\TechnicalVisit;
use App\Models\SystemPrice;

class ProductorController extends Controller
{
    /**
     * Vista central de Acopio para el Proveedor/Productor
     */
    public function acopio(Request $request)
    {
        $user = Auth::user();
        $today = Carbon::today()->format('Y-m-d');
        $precioLitro = 1.40; // Tarifa estándar de compra de leche fresca en Huata (S/ 1.40 por litro)

        // 1. Leche entregada HOY
        $entregaHoy = CollectionRecord::where('producer_id', $user->id)
            ->whereHas('route', fn($q) => $q->where('date', $today))
            ->with(['route.collector', 'route.zone'])
            ->first();

        $litrosHoy = $entregaHoy ? (float)$entregaHoy->liters : 0.00;

        // 2. Ciclo semanal activo (Litros entregados acumulados hasta que se cierre el pago)
        // Buscamos la última liquidación que esté en estado 'pagado'
        $ultimaLiquidacionPagada = ProducerSettlement::where('producer_id', $user->id)
            ->where('status', 'pagado')
            ->orderByDesc('end_date')
            ->first();

        if ($ultimaLiquidacionPagada) {
            // El ciclo activo empieza el día siguiente al fin de la última liquidación pagada
            $inicioCicloActivo = Carbon::parse($ultimaLiquidacionPagada->end_date)->addDay()->startOfDay();
        } else {
            // Si no hay pagos previos, tomamos desde el inicio de la semana actual o primeros registros
            $inicioCicloActivo = Carbon::now()->startOfWeek();
        }

        // Tarifa dinámica por temporada y calidad Lactoscan (penalidad si agua > 0%)
        $priceInfo = SystemPrice::getMilkPriceForProducer($user->id, $inicioCicloActivo->format('Y-m-d'), $today);
        $precioLitro = $priceInfo['price'];

        // Litros de la semana / ciclo activo
        $litrosSemana = (float)CollectionRecord::where('producer_id', $user->id)
            ->whereHas('route', function ($q) use ($inicioCicloActivo) {
                $q->where('date', '>=', $inicioCicloActivo->format('Y-m-d'));
            })
            ->sum('liters');

        // Descuentos pendientes que se aplicarán a la liquidación de la semana
        $descuentosPendientes = (float)ProducerDeduction::where('producer_id', $user->id)
            ->where('status', 'pendiente')
            ->sum('amount');

        // Pago sumatorio acumulado de la semana
        $pagoBrutoSemana = $litrosSemana * $precioLitro;
        $pagoNetoSemana = max(0, $pagoBrutoSemana - $descuentosPendientes);

        // 3. Filtros temporales: 'dia', 'semana', 'mes', 'rango'
        $filtro = $request->get('filtro', 'dia');
        $fechaDesde = $request->get('desde');
        $fechaHasta = $request->get('hasta');

        $query = CollectionRecord::where('producer_id', $user->id)
            ->with(['route.collector', 'route.zone', 'route.reception']);

        if ($fechaDesde) {
            $query->whereHas('route', fn($q) => $q->where('date', '>=', $fechaDesde));
        }
        if ($fechaHasta) {
            $query->whereHas('route', fn($q) => $q->where('date', '<=', $fechaHasta));
        }

        // Datos según filtro
        $registrosDiarios = null;
        $resumenSemanas = null;
        $resumenMeses = null;

        if ($filtro === 'semana') {
            // Agrupación por semana usando SQL o colección
            $todos = $query->get();
            $resumenSemanas = $todos->groupBy(function ($item) {
                $date = Carbon::parse($item->route->date);
                return $date->year . '-W' . str_pad($date->weekOfYear, 2, '0', STR_PAD_LEFT);
            })->map(function ($items, $key) use ($precioLitro) {
                $primero = Carbon::parse($items->first()->route->date);
                $startOfWeek = $primero->copy()->startOfWeek()->format('d M');
                $endOfWeek = $primero->copy()->endOfWeek()->format('d M Y');
                $litros = $items->sum('liters');
                return (object)[
                    'semana' => $key,
                    'rango' => "$startOfWeek - $endOfWeek",
                    'dias_entregados' => $items->count(),
                    'total_litros' => $litros,
                    'promedio_diario' => round($litros / max(1, $items->count()), 2),
                    'total_bruto' => round($litros * $precioLitro, 2),
                ];
            })->values();
        } elseif ($filtro === 'mes') {
            $todos = $query->get();
            $resumenMeses = $todos->groupBy(function ($item) {
                return Carbon::parse($item->route->date)->format('Y-m');
            })->map(function ($items, $key) use ($precioLitro) {
                $date = Carbon::parse($key . '-01');
                $litros = $items->sum('liters');
                return (object)[
                    'mes_key' => $key,
                    'mes_nombre' => $date->translatedFormat('F Y'),
                    'dias_entregados' => $items->count(),
                    'total_litros' => $litros,
                    'promedio_diario' => round($litros / max(1, $items->count()), 2),
                    'total_bruto' => round($litros * $precioLitro, 2),
                ];
            })->values();
        } else {
            // Por día (predeterminado)
            $registrosDiarios = $query->orderByDesc('id')->paginate(15)->appends($request->all());
        }

        $totalLitrosHistorico = CollectionRecord::where('producer_id', $user->id)->sum('liters');
        $zonasDisponibles = Zone::where('is_active', true)->get();

        return view('productor.acopio', compact(
            'user',
            'today',
            'precioLitro',
            'entregaHoy',
            'litrosHoy',
            'litrosSemana',
            'inicioCicloActivo',
            'pagoBrutoSemana',
            'descuentosPendientes',
            'pagoNetoSemana',
            'priceInfo',
            'filtro',
            'fechaDesde',
            'fechaHasta',
            'registrosDiarios',
            'resumenSemanas',
            'resumenMeses',
            'totalLitrosHistorico',
            'zonasDisponibles'
        ));
    }

    /**
     * Módulo Cambio de Zona: Formulario e historial de solicitudes
     */
    public function zonas()
    {
        $user = Auth::user();
        $miZona = $user->zone;
        $zonasDisponibles = Zone::where('is_active', true)->get();
        $solicitudes = ZoneChangeRequest::where('producer_id', $user->id)
            ->with(['currentZone', 'requestedZone', 'reviewer'])
            ->latest()
            ->get();

        return view('productor.zonas', compact('user', 'miZona', 'zonasDisponibles', 'solicitudes'));
    }

    /**
     * Procesar solicitud de cambio de zona
     */
    public function solicitarCambioZona(Request $request)
    {
        $request->validate([
            'requested_zone_id' => 'required|exists:zones,id',
            'reason' => 'required|string|max:500',
        ]);

        $user = Auth::user();

        try {
            app(\App\Services\Zonas\ZonaService::class)->solicitarCambio(
                $user,
                (int) $request->requested_zone_id,
                $request->reason
            );
        } catch (\App\Exceptions\ReglaNegocioException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()])->withInput();
        }

        return redirect()->route('productor.zonas')->with('success', 'Solicitud de cambio de zona enviada a administración para revisión.');
    }

    /**
     * Módulo Descuentos: Anticipos, préstamos y compras de insumos
     */
    public function descuentos()
    {
        $user = Auth::user();

        $descuentos = ProducerDeduction::where('producer_id', $user->id)
            ->with(['settlement', 'sale.customer', 'sale.seller'])
            ->orderByDesc('date')
            ->get();

        $totalPendiente = ProducerDeduction::where('producer_id', $user->id)
            ->where('status', 'pendiente')
            ->sum('amount');

        $totalDescontado = ProducerDeduction::where('producer_id', $user->id)
            ->where('status', 'descontado')
            ->sum('amount');

        return view('productor.descuentos', compact('user', 'descuentos', 'totalPendiente', 'totalDescontado'));
    }

    /**
     * Módulo Historial de Pagos y Liquidaciones Semanales
     */
    public function pagos()
    {
        $user = Auth::user();

        $liquidaciones = ProducerSettlement::where('producer_id', $user->id)
            ->with('deductions')
            ->orderByDesc('end_date')
            ->get();

        $totalPagadoHistorico = ProducerSettlement::where('producer_id', $user->id)
            ->where('status', 'pagado')
            ->sum('net_total');

        $totalPendienteCobro = ProducerSettlement::where('producer_id', $user->id)
            ->where('status', 'pendiente')
            ->sum('net_total');

        return view('productor.pagos', compact('user', 'liquidaciones', 'totalPagadoHistorico', 'totalPendienteCobro'));
    }

    /**
     * Ver/Imprimir comprobante formal de liquidación
     */
    public function reciboPago($id)
    {
        $user = Auth::user();
        $settlement = ProducerSettlement::where('producer_id', $user->id)
            ->with(['producer.zone', 'deductions', 'payer'])
            ->findOrFail($id);

        return view('productor.recibo_pago', compact('settlement'));
    }

    /**
     * Módulo de Calidad Lactoscan: Reporte de análisis y citas técnicas
     */
    public function calidad()
    {
        $user = Auth::user();

        $analisis = LactoscanAnalysis::where('producer_id', $user->id)
            ->with(['inspector', 'technicalVisits.inspector'])
            ->orderByDesc('analysis_date')
            ->paginate(10);

        // Promedios de calidad
        $promedioGrasa = LactoscanAnalysis::where('producer_id', $user->id)->avg('fat_percentage') ?: 3.5;
        $promedioDensidad = LactoscanAnalysis::where('producer_id', $user->id)->avg('density') ?: 1.029;
        $promedioAcidez = LactoscanAnalysis::where('producer_id', $user->id)->avg('ph_or_acidity') ?: 16.5;

        // Visitas técnicas activas
        $visitasPendientes = TechnicalVisit::where('producer_id', $user->id)
            ->whereIn('status', ['programada', 'en_curso'])
            ->with('inspector')
            ->get();

        return view('productor.calidad', compact(
            'user',
            'analisis',
            'promedioGrasa',
            'promedioDensidad',
            'promedioAcidez',
            'visitasPendientes'
        ));
    }

    /**
     * Acción para cerrar/marcar una liquidación como pagada (reinicia el acumulador semanal)
     */
    public function liquidarSemana(Request $request, $userId)
    {
        $producer = User::where('role', 'productor')->findOrFail($userId);

        // Última liquidación pagada para calcular rango
        $ultimaLiquidacion = ProducerSettlement::where('producer_id', $producer->id)
            ->where('status', 'pagado')
            ->orderByDesc('end_date')
            ->first();

        $startDate = $ultimaLiquidacion 
            ? Carbon::parse($ultimaLiquidacion->end_date)->addDay()->format('Y-m-d')
            : Carbon::now()->subDays(6)->format('Y-m-d');

        $endDate = Carbon::today()->format('Y-m-d');

        $priceInfo = SystemPrice::getMilkPriceForProducer($producer->id, $startDate, $endDate);
        $precioLitro = $priceInfo['price'];

        $litros = (float)CollectionRecord::where('producer_id', $producer->id)
            ->whereHas('route', function($q) use ($startDate, $endDate) {
                $q->whereBetween('date', [$startDate, $endDate]);
            })
            ->sum('liters');

        if ($litros <= 0) {
            return back()->with('error', 'No hay entregas pendientes en el período seleccionado para liquidar.');
        }

        $gross = round($litros * $precioLitro, 2);

        // Deducciones pendientes
        $deductions = ProducerDeduction::where('producer_id', $producer->id)
            ->where('status', 'pendiente')
            ->get();

        $totalDeductions = round($deductions->sum('amount'), 2);
        $net = max(0, $gross - $totalDeductions);

        $settlement = ProducerSettlement::create([
            'settlement_code' => 'LIQ-HUATA-' . date('Ymd-His'),
            'producer_id' => $producer->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_liters' => $litros,
            'price_per_liter' => $precioLitro,
            'gross_total' => $gross,
            'deductions_total' => $totalDeductions,
            'net_total' => $net,
            'status' => 'pagado',
            'paid_at' => Carbon::now(),
            'paid_by' => Auth::id(),
            'payment_method' => 'efectivo',
            'notes' => 'Liquidación semanal cerrada y pagada. Se reinicia el acumulador de litros para el siguiente ciclo.',
        ]);

        foreach ($deductions as $d) {
            $d->update([
                'status' => 'descontado',
                'settlement_id' => $settlement->id,
            ]);
        }

        return back()->with('success', "Liquidación {$settlement->settlement_code} pagada exitosamente. El acumulador semanal se reinició para el nuevo ciclo.");
    }
}
