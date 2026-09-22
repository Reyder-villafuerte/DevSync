<?php

namespace App\Http\Controllers;

use App\Models\OperationalExpense;
use App\Models\ProducerSettlement;
use App\Models\Sale;
use App\Models\User;
use App\Services\Sistema\SistemaService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class FinancialController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'mes'); // hoy, semana, mes, todo, custom
        $startDate = null;
        $endDate = null;

        $now = Carbon::now();

        switch ($period) {
            case 'hoy':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                break;
            case 'semana':
                $startDate = $now->copy()->startOfWeek();
                $endDate = $now->copy()->endOfWeek();
                break;
            case 'todo':
                $startDate = null;
                $endDate = null;
                break;
            case 'custom':
                if ($request->filled('start_date') && $request->filled('end_date')) {
                    $startDate = Carbon::parse($request->get('start_date'))->startOfDay();
                    $endDate = Carbon::parse($request->get('end_date'))->endOfDay();
                } else {
                    $startDate = $now->copy()->startOfMonth();
                    $endDate = $now->copy()->endOfMonth();
                }
                break;
            case 'mes':
            default:
                $period = 'mes';
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                break;
        }

        // 1. INGRESOS: Ventas de Queso
        $salesQuery = Sale::query();
        if ($startDate && $endDate) {
            $salesQuery->whereBetween('created_at', [$startDate, $endDate]);
        }

        $allSales = (clone $salesQuery)->with(['customer', 'seller'])->latest()->get();
        $incomeCash = (clone $salesQuery)->where('payment_method', '!=', 'descuento_leche')->sum('total_amount');
        $incomeMilkCredit = (clone $salesQuery)->where('payment_method', 'descuento_leche')->sum('total_amount');
        $totalIncome = $incomeCash + $incomeMilkCredit;
        $totalMoldsSold = (clone $salesQuery)->sum('cheese_molds_quantity');

        // 2. EGRESOS: Proveedores (Liquidaciones de leche autorizadas o pagadas)
        $settlementsQuery = ProducerSettlement::whereIn('status', ['autorizado', 'pagado']);
        if ($startDate && $endDate) {
            $settlementsQuery->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('paid_at', [$startDate, $endDate])
                    ->orWhere(function ($sub) use ($startDate, $endDate) {
                        $sub->whereNull('paid_at')->whereBetween('created_at', [$startDate, $endDate]);
                    });
            });
        }
        $settlements = (clone $settlementsQuery)->with('producer')->latest()->get();
        $expenseSuppliers = $settlements->sum('net_total');
        $totalLitersPaid = $settlements->sum('total_liters');

        // 3. EGRESOS: Personal y Operativos
        $expensesQuery = OperationalExpense::query();
        if ($startDate && $endDate) {
            $expensesQuery->whereBetween('expense_date', [$startDate->toDateString(), $endDate->toDateString()]);
        }
        $allExpenses = (clone $expensesQuery)->with('staff')->latest('expense_date')->get();
        $expenseStaff = $allExpenses->where('category', 'pago_personal')->sum('amount');
        $expenseOperations = $allExpenses->where('category', '!=', 'pago_personal')->sum('amount');
        $totalOtherExpenses = $expenseStaff + $expenseOperations;

        // 4. TOTALES Y BALANCE NETO
        $totalExpenses = $expenseSuppliers + $totalOtherExpenses;
        $netBalance = $totalIncome - $totalExpenses;

        // Personal disponible para registrar egresos de planilla
        $staffMembers = User::whereIn('role', [
            'acopiador', 'jefe_produccion', 'inspector_calidad', 'personal_venta', 'personal_pago', 'pagador_campo',
        ])->where('is_active', true)->orderBy('name')->get();

        // 5. LOS MOVIMIENTOS, EN UNA SOLA LISTA
        //
        // Ventas, liquidaciones y egresos viven en tres tablas distintas, pero
        // el administrador los lee como un solo libro en orden de fecha. Se
        // normaliza cada origen a la misma forma, se ordena y se pagina a mano,
        // que es la única manera de paginar algo que sale de tres consultas.
        $movimientos = collect();

        foreach ($allSales as $venta) {
            $movimientos->push([
                'id' => $venta->id,
                'tipo' => 'ingreso',
                'fecha' => $venta->created_at,
                'fecha_texto' => $venta->created_at->format('Y-m-d H:i'),
                'etiqueta' => 'Ingreso venta',
                'icono' => 'fa-circle-arrow-down',
                'clase_etiqueta' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                'concepto' => 'Venta de '.$venta->cheese_molds_quantity.' molde(s) de queso',
                'detalle' => 'Recibo: '.($venta->receipt_number ?: '#REC-'.str_pad((string) $venta->id, 5, '0', STR_PAD_LEFT)),
                'beneficiario' => $venta->customer ? $venta->customer->name : 'Cliente mostrador',
                'beneficiario_detalle' => $venta->seller ? 'Cajero: '.$venta->seller->name : null,
                'metodo' => $venta->payment_method === 'descuento_leche' ? 'A cuenta leche' : 'Efectivo caja',
                'clase_metodo' => $venta->payment_method === 'descuento_leche'
                    ? 'bg-amber-100 text-amber-900 border-amber-200'
                    : 'bg-emerald-100 text-emerald-900 border-emerald-200',
                'monto' => (float) $venta->total_amount,
                'signo' => '+',
                'clase_monto' => 'text-emerald-700',
            ]);
        }

        foreach ($settlements as $liquidacion) {
            $cuando = $liquidacion->paid_at ?: $liquidacion->created_at;

            $movimientos->push([
                'id' => $liquidacion->id,
                'tipo' => 'proveedor',
                'fecha' => $cuando,
                'fecha_texto' => $cuando->format('Y-m-d H:i'),
                'etiqueta' => 'Pago proveedor',
                'icono' => 'fa-cow',
                'clase_etiqueta' => 'bg-amber-100 text-amber-900 border-amber-200',
                'concepto' => 'Liquidación semanal: '.$liquidacion->total_liters.' L de leche',
                'detalle' => ($liquidacion->settlement_code ?: '#SOBRE-'.str_pad((string) $liquidacion->id, 5, '0', STR_PAD_LEFT))
                    .' - Desc: S/ '.number_format((float) $liquidacion->deductions_total, 2),
                'beneficiario' => $liquidacion->producer ? $liquidacion->producer->name : 'Productor Huata',
                'beneficiario_detalle' => 'DNI: '.($liquidacion->producer ? ($liquidacion->producer->dni ?: '-') : '-'),
                'metodo' => 'Sobre ruta viernes',
                'clase_metodo' => 'bg-slate-100 text-slate-700 border-slate-200',
                'monto' => (float) $liquidacion->net_total,
                'signo' => '-',
                'clase_monto' => 'text-amber-700',
            ]);
        }

        foreach ($allExpenses as $egreso) {
            $esPlanilla = $egreso->category === 'pago_personal';

            $movimientos->push([
                'id' => $egreso->id,
                'tipo' => 'personal',
                'fecha' => $egreso->expense_date,
                'fecha_texto' => $egreso->expense_date->format('Y-m-d'),
                'etiqueta' => $esPlanilla ? 'Pago personal' : 'Gasto operativo',
                'icono' => $esPlanilla ? 'fa-user-check' : 'fa-gas-pump',
                'clase_etiqueta' => $esPlanilla
                    ? 'bg-purple-100 text-purple-900 border-purple-200'
                    : 'bg-rose-100 text-rose-900 border-rose-200',
                'concepto' => $egreso->description,
                'detalle' => trim(($egreso->receipt_number ? 'Comprobante: '.$egreso->receipt_number.' ' : '').($egreso->notes ?: '')) ?: null,
                'beneficiario' => $egreso->beneficiary_name ?: ($egreso->staff ? $egreso->staff->name : 'Personal'),
                'beneficiario_detalle' => $egreso->staff ? 'Rol: '.ucfirst($egreso->staff->role) : null,
                'metodo' => $egreso->payment_method,
                'clase_metodo' => 'bg-slate-100 text-slate-700 border-slate-200',
                'monto' => (float) $egreso->amount,
                'signo' => '-',
                'clase_monto' => 'text-rose-700',
            ]);
        }

        $tipo = $request->get('tipo', 'todos');

        if (in_array($tipo, ['ingreso', 'proveedor', 'personal'], true)) {
            $movimientos = $movimientos->where('tipo', $tipo);
        }

        if ($request->filled('buscar')) {
            $termino = mb_strtolower($request->buscar);

            $movimientos = $movimientos->filter(function (array $m) use ($termino) {
                return str_contains(mb_strtolower($m['concepto'].' '.$m['beneficiario'].' '.$m['detalle']), $termino);
            });
        }

        $movimientos = $movimientos->sortByDesc('fecha')->values();

        $porPagina = 10;
        $paginaActual = LengthAwarePaginator::resolveCurrentPage();

        $movimientos = new LengthAwarePaginator(
            $movimientos->forPage($paginaActual, $porPagina)->values(),
            $movimientos->count(),
            $porPagina,
            $paginaActual,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.finanzas.index', compact(
            'period',
            'startDate',
            'endDate',
            'incomeCash',
            'incomeMilkCredit',
            'totalIncome',
            'totalMoldsSold',
            'expenseSuppliers',
            'totalLitersPaid',
            'expenseStaff',
            'expenseOperations',
            'totalExpenses',
            'netBalance',
            'allSales',
            'settlements',
            'allExpenses',
            'movimientos',
            'tipo',
            'staffMembers'
        ));
    }

    public function storeExpense(Request $request)
    {
        $validated = $request->validate([
            'category' => ['required', 'in:pago_personal,combustible_ruta,insumos_planta,mantenimiento,otros'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
            'user_id' => ['nullable', 'exists:users,id'],
            'beneficiary_name' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', 'in:efectivo,transferencia'],
            'receipt_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        app(SistemaService::class)->registrarEgreso(Auth::user(), $validated);

        return redirect()->route('admin.finanzas.index')
            ->with('success', 'Egreso registrado correctamente en el flujo de caja.');
    }
}
