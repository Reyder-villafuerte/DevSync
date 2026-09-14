<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Zone;
use App\Models\CollectionRecord;
use App\Models\ProducerSettlement;
use App\Models\ProducerDeduction;
use App\Models\LactoscanAnalysis;
use App\Models\SystemPrice;

class FieldPaymentController extends Controller
{
    /**
     * Módulo Exclusivo para el Pagador de Campo (Sueldos en Ruta / Viernes con el Acopiador)
     */
    public function index(Request $request)
    {
        $today = Carbon::today()->format('Y-m-d');
        $zones = Zone::where('is_active', true)->orderBy('code')->get();

        $selectedZoneId = $request->get('zone_id');
        $selectedStatus = $request->get('status', 'todos'); // todos, pendientes, pagados
        $search = trim($request->get('search', ''));

        $systemPrice = SystemPrice::current();
        $basePrice = (float) $systemPrice->price_milk_base;

        $producersQuery = User::where('role', 'productor')
            ->where('is_active', true)
            ->with(['zone']);

        if ($selectedZoneId) {
            $producersQuery->where('zone_id', $selectedZoneId);
        }

        if ($search) {
            $producersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('dni', 'like', "%{$search}%");
            });
        }

        $allProducers = $producersQuery->orderBy('name')->get();

        $envelopes = [];
        $totalSobres = 0;
        $sobresEntregados = 0;
        $sobresPendientes = 0;
        $montoTotalEfectivo = 0;
        $montoEntregado = 0;
        $montoRemanente = 0;

        foreach ($allProducers as $prod) {
            // Buscar liquidación autorizada o pagada del ciclo
            $latestSettlement = ProducerSettlement::where('producer_id', $prod->id)
                ->whereIn('status', ['autorizado', 'pagado'])
                ->orderByDesc('id')
                ->first();

            $status = 'no_autorizado';
            $activeSettlement = null;
            $isAuthorized = false;
            $canDeliver = false;
            $totalLiters = 0.0;
            $gross = 0.0;
            $deductions = 0.0;
            $net = 0.0;

            if ($latestSettlement && $latestSettlement->status === 'pagado') {
                $status = 'pagado';
                $activeSettlement = $latestSettlement;
                $isAuthorized = true;
                $canDeliver = false;
                $totalLiters = (float) $activeSettlement->total_liters;
                $gross = (float) $activeSettlement->gross_total;
                $deductions = (float) $activeSettlement->deductions_total;
                $net = (float) $activeSettlement->net_total;

                $totalSobres++;
                $sobresEntregados++;
                $montoTotalEfectivo += $net;
                $montoEntregado += $net;
            } elseif ($latestSettlement && $latestSettlement->status === 'autorizado') {
                $status = 'autorizado';
                $activeSettlement = $latestSettlement;
                $isAuthorized = true;
                $canDeliver = true;
                $totalLiters = (float) $activeSettlement->total_liters;
                $gross = (float) $activeSettlement->gross_total;
                $deductions = (float) $activeSettlement->deductions_total;
                $net = (float) $activeSettlement->net_total;

                $totalSobres++;
                $sobresPendientes++;
                $montoTotalEfectivo += $net;
                $montoRemanente += $net;
            } else {
                // NO AUTORIZADO POR ADMINISTRACIÓN:
                // El efectivo no debe figurar ni sumarse a custodia de la camioneta
                $status = 'no_autorizado';
                $isAuthorized = false;
                $canDeliver = false;

                // Litros registrados en la semana para referencia
                $startDate = Carbon::now()->subDays(6)->format('Y-m-d');
                $totalLiters = (float) CollectionRecord::where('producer_id', $prod->id)
                    ->whereHas('route', function ($q) use ($startDate, $today) {
                        $q->whereBetween('date', [$startDate, $today]);
                    })
                    ->sum('liters');

                $gross = 0.0;
                $deductions = 0.0;
                $net = 0.0; // Efectivo no visible
            }

            // Filtrado por estado si fue solicitado
            if ($selectedStatus === 'autorizados' && $status !== 'autorizado') {
                continue;
            }
            if ($selectedStatus === 'pendientes' && $status !== 'no_autorizado') {
                continue;
            }
            if ($selectedStatus === 'pagados' && $status !== 'pagado') {
                continue;
            }

            $envelopes[] = [
                'producer' => $prod,
                'liters' => $totalLiters,
                'gross' => $gross,
                'deductions' => $deductions,
                'net' => $net,
                'status' => $status,
                'is_authorized' => $isAuthorized,
                'can_deliver' => $canDeliver,
                'settlement' => $activeSettlement,
            ];
        }

        return view('pagador.index', compact(
            'envelopes',
            'zones',
            'selectedZoneId',
            'selectedStatus',
            'search',
            'totalSobres',
            'sobresEntregados',
            'sobresPendientes',
            'montoTotalEfectivo',
            'montoEntregado',
            'montoRemanente',
            'today'
        ));
    }

    /**
     * Registrar la entrega del sobre con dinero en efectivo en la ruta del viernes
     */
    public function payProducer(Request $request, User $producer)
    {
        try {
            $settlement = app(\App\Services\Pagos\LiquidacionService::class)
                ->entregarSobre($producer, Auth::user());
        } catch (\App\Exceptions\ReglaNegocioException $e) {
            return redirect()->route('pagos.ruta.index', [
                'zone_id' => $request->get('zone_id'),
                'status' => $request->get('status'),
                'search' => $request->get('search'),
            ])->with('error', "No se puede entregar el sobre: El pago de {$producer->name} aún no ha sido autorizado por la Administración.");
        }

        return redirect()->route('pagos.ruta.index', [
            'zone_id' => $request->get('zone_id'),
            'status' => $request->get('status'),
            'search' => $request->get('search'),
        ])->with('success', "Sobre de S/ " . number_format($settlement->net_total, 2) . " entregado en efectivo a {$producer->name}. Recibo emitido.");
    }

    /**
     * Ver e imprimir el comprobante térmico oficial del sobre de pago
     */
    public function receipt(ProducerSettlement $settlement)
    {
        $settlement->load(['producer.zone', 'payer', 'deductions']);
        return view('pagador.receipt', compact('settlement'));
    }

    /**
     * Historial de pagos y sobres entregados en ruta
     */
    public function history(Request $request)
    {
        $search = trim($request->get('search', ''));
        $zoneId = $request->get('zone_id');
        $zones = Zone::where('is_active', true)->orderBy('code')->get();

        $query = ProducerSettlement::with(['producer.zone', 'payer'])
            ->where('status', 'pagado')
            ->orderByDesc('paid_at');

        if ($zoneId) {
            $query->whereHas('producer', function ($q) use ($zoneId) {
                $q->where('zone_id', $zoneId);
            });
        }

        if ($search) {
            $query->whereHas('producer', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('dni', 'like', "%{$search}%");
            });
        }

        $settlements = $query->paginate(20)->appends($request->query());

        $totalEntregadoHistorico = ProducerSettlement::where('status', 'pagado')->sum('net_total');
        $sobresTotalesEntregados = ProducerSettlement::where('status', 'pagado')->count();

        return view('pagador.history', compact('settlements', 'zones', 'search', 'zoneId', 'totalEntregadoHistorico', 'sobresTotalesEntregados'));
    }
}
