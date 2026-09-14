<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use App\Exceptions\ReglaNegocioException;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\InventoryStock;
use App\Models\DailyCashClosure;
use App\Services\Ventas\VentaService;

class SalesController extends Controller
{
    public function __construct(private VentaService $ventas)
    {
    }

    public function index(Request $request)
    {
        if (Auth::user()->role === 'admin') {
            return redirect()->route('admin.finanzas.index')
                ->with('info', 'El administrador supervisa las finanzas generales. Ha sido canalizado al Flujo de Caja.');
        }

        $today = date('Y-m-d');
        $fechaCierre = $request->get('fecha', $today);
        $stockQueso = InventoryStock::getStock('CHEESE_MOLD_UNITS');

        // Cierre previo de hoy si existe
        $closureToday = DailyCashClosure::whereDate('date', $today)->latest()->first();

        // Ventas de hoy: ÚNICAMENTE las ventas de hoy pendientes de cierre (sin closure_id)
        // Una vez cerrada la caja, este conjunto queda en blanco.
        $queryHoy = Sale::with(['customer', 'seller'])
            ->whereDate('sold_at', $today)
            ->whereNull('closure_id')
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $queryHoy->where(function ($q) use ($search) {
                $q->whereHas('customer', function ($cq) use ($search) {
                    $cq->where('last_name', 'like', "%{$search}%")
                       ->orWhere('first_name', 'like', "%{$search}%")
                       ->orWhere('dni_ruc', 'like', "%{$search}%");
                })->orWhere('receipt_number', 'like', "%{$search}%");
            });
        }

        $ventasHoy = $queryHoy->get();

        // Cálculo de Arqueo para la fecha seleccionada ($fechaCierre)
        // Si es hoy y no hay pendientes pero hay cierre previo, consultamos las ventas de la fecha
        $ventasArqueo = Sale::with('customer')
            ->whereDate('sold_at', $fechaCierre)
            ->get();

        // Si se consulta hoy y no se ha cerrado la caja, el arqueo es de las pendientes
        $ventasParaCalculo = ($fechaCierre === $today && $ventasHoy->isNotEmpty())
            ? $ventasHoy
            : $ventasArqueo;

        $efectivoTotal = $ventasParaCalculo->where('payment_method', 'efectivo')->sum('total_amount');
        $descuentoLecheTotal = $ventasParaCalculo->where('payment_method', 'descuento_leche')->sum('total_amount');
        $totalMontoDia = $ventasParaCalculo->sum('total_amount');
        $totalMoldesDia = $ventasParaCalculo->sum('cheese_molds_quantity');
        $totalTransaccionesDia = $ventasParaCalculo->count();

        // Desglose por categoría de cliente
        $resumenClientes = [
            'proveedor' => [
                'nombre' => 'Proveedores de Leche',
                'tag' => 'Descuento / S/ 18',
                'moldes' => 0,
                'efectivo' => 0.0,
                'descuento_leche' => 0.0,
                'total_monto' => 0.0,
            ],
            'mayorista' => [
                'nombre' => 'Clientes Mayoristas',
                'tag' => 'Efectivo / S/ 19',
                'moldes' => 0,
                'efectivo' => 0.0,
                'descuento_leche' => 0.0,
                'total_monto' => 0.0,
            ],
            'local' => [
                'nombre' => 'Clientes Locales / Detal',
                'tag' => 'Efectivo / S/ 20',
                'moldes' => 0,
                'efectivo' => 0.0,
                'descuento_leche' => 0.0,
                'total_monto' => 0.0,
            ],
        ];

        foreach ($ventasParaCalculo as $v) {
            $tipo = $v->customer ? ($v->customer->type ?: 'local') : 'local';
            if (!isset($resumenClientes[$tipo])) {
                $tipo = 'local';
            }

            $resumenClientes[$tipo]['moldes'] += (int) $v->cheese_molds_quantity;
            $resumenClientes[$tipo]['total_monto'] += (float) $v->total_amount;

            if ($v->payment_method === 'descuento_leche') {
                $resumenClientes[$tipo]['descuento_leche'] += (float) $v->total_amount;
            } else {
                $resumenClientes[$tipo]['efectivo'] += (float) $v->total_amount;
            }
        }

        return view('ventas.index', compact(
            'ventasHoy',
            'closureToday',
            'stockQueso',
            'today',
            'fechaCierre',
            'efectivoTotal',
            'descuentoLecheTotal',
            'totalMontoDia',
            'totalMoldesDia',
            'totalTransaccionesDia',
            'resumenClientes'
        ));
    }

    /**
     * Ejecutar y confirmar el Cierre de Caja del día:
     * Liquida las ventas activas de hoy y deja la bandeja de Ventas de Hoy en blanco.
     */
    public function closeCashRegister(Request $request)
    {
        try {
            $closure = $this->ventas->cerrarCaja(Auth::user(), $request->get('notes'));
        } catch (ReglaNegocioException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()]);
        }

        $totalCash = (float) $closure->total_cash;
        $totalMolds = (int) $closure->cheese_molds_quantity;

        return redirect()->route('ventas.index')
            ->with('success', "Cierre de caja realizado con éxito. Se conciliaron S/ " . number_format($totalCash, 2) . " en efectivo y {$totalMolds} moldes despachados. La lista de ventas de hoy volvió a blanco.");
    }

    /**
     * Apartado de Recibos: Listado histórico de TODOS los recibos emitidos
     */
    public function receiptsHistory(Request $request)
    {
        $query = Sale::with(['customer', 'seller', 'closure'])->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('customer', function ($cq) use ($search) {
                    $cq->where('last_name', 'like', "%{$search}%")
                       ->orWhere('first_name', 'like', "%{$search}%")
                       ->orWhere('dni_ruc', 'like', "%{$search}%");
                })->orWhere('receipt_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('fecha')) {
            $query->whereDate('sold_at', $request->fecha);
        }

        if ($request->filled('tipo_cliente')) {
            $tipo = $request->tipo_cliente;
            $query->whereHas('customer', function ($cq) use ($tipo) {
                $cq->where('type', $tipo);
            });
        }

        $sales = $query->paginate(20)->appends($request->all());
        $totalRecibos = Sale::count();
        $totalRecaudadoEfectivo = Sale::where('payment_method', 'efectivo')->sum('total_amount');
        $totalDeducidoLeche = Sale::where('payment_method', 'descuento_leche')->sum('total_amount');
        $totalMoldesHistorico = Sale::sum('cheese_molds_quantity');

        return view('ventas.receipts', compact(
            'sales',
            'totalRecibos',
            'totalRecaudadoEfectivo',
            'totalDeducidoLeche',
            'totalMoldesHistorico'
        ));
    }

    public function create()
    {
        $stockQueso = InventoryStock::getStock('CHEESE_MOLD_UNITS');
        $customers = Customer::orderBy('last_name')->get();
        return view('ventas.create', compact('stockQueso', 'customers'));
    }

    // Endpoint JSON para búsqueda ágil de clientes por apellido o DNI
    public function searchCustomer(Request $request)
    {
        $term = $request->query('q', '');
        $customers = Customer::where('last_name', 'like', "%{$term}%")
            ->orWhere('first_name', 'like', "%{$term}%")
            ->orWhere('dni_ruc', 'like', "%{$term}%")
            ->take(15)
            ->get();

        return response()->json($customers);
    }

    // Calcular tarifa dinámica antes de confirmar venta
    public function calculatePrice(Request $request)
    {
        $customerId = $request->customer_id;
        $quantity = (int) $request->quantity;

        if ($customerId) {
            $customer = Customer::find($customerId);
            $unitPrice = $customer ? $customer->determineUnitPrice($quantity) : 20.00;
        } else {
            // Cliente nuevo sin registrar aún
            $isWholesale = $request->boolean('is_wholesale') || $quantity >= 10;
            $unitPrice = $isWholesale ? 19.00 : 20.00;
        }

        return response()->json([
            'unit_price' => $unitPrice,
            'total' => $unitPrice * $quantity,
        ]);
    }

    // Procesar venta en efectivo y generar recibo
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            // Si es cliente nuevo:
            'new_first_name' => ['required_without:customer_id', 'nullable', 'string', 'max:100'],
            'new_last_name' => ['required_without:customer_id', 'nullable', 'string', 'max:100'],
            'new_dni_ruc' => ['nullable', 'string', 'max:20'],
            'new_phone' => ['nullable', 'string', 'max:30'],
            'new_type' => ['nullable', 'in:proveedor,mayorista,local'],
            // Venta:
            'cheese_molds_quantity' => ['required', 'integer', 'min:1'],
        ]);

        $validated['payment_method'] = $request->get('payment_method', 'efectivo');

        try {
            $sale = $this->ventas->registrarVenta(Auth::user(), $validated);
        } catch (ReglaNegocioException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()])->withInput();
        }

        return redirect()->route('ventas.receipt', $sale->id)
            ->with('success', "Venta registrada con éxito. Recibo {$sale->receipt_number} generado.");
    }

    // Mostrar recibo bien estructurado para impresión o consulta
    public function showReceipt(Sale $sale)
    {
        $sale->load(['customer', 'seller']);

        $user = Auth::user();
        if ($user && $user->role === 'productor') {
            if ($sale->customer->linked_user_id !== $user->id) {
                abort(403, 'No tienes autorización para consultar este recibo.');
            }
        }

        return view('ventas.receipt', compact('sale'));
    }
}
