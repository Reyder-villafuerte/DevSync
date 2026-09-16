<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Sale;
use App\Models\ProducerSettlement;
use App\Models\OperationalExpense;
use App\Models\User;

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
            'acopiador', 'jefe_produccion', 'inspector_calidad', 'personal_venta', 'personal_pago', 'pagador_campo'
        ])->where('is_active', true)->orderBy('name')->get();

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

        app(\App\Services\Sistema\SistemaService::class)->registrarEgreso(Auth::user(), $validated);

        return redirect()->route('admin.finanzas.index')
            ->with('success', 'Egreso registrado correctamente en el flujo de caja.');
    }
}
