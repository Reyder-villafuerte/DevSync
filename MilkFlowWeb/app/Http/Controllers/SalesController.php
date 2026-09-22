<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\ClientType;
use App\Models\Customer;
use App\Models\DailyCashClosure;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\Sale;
use App\Services\Acopio\JornadaOperativa;
use App\Services\Ventas\VentaService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Caja de despacho. Una venta lleva renglones, así que el mostrador vende
 * cualquier producto terminado del catálogo, no solo moldes de queso.
 */
class SalesController extends Controller
{
    public function __construct(private VentaService $ventas) {}

    public function index(Request $request)
    {
        if (Auth::user()->role === 'admin') {
            return redirect()->route('admin.finanzas.index')
                ->with('info', 'El administrador supervisa las finanzas generales. Ha sido canalizado al Flujo de Caja.');
        }

        $today = app(JornadaOperativa::class)->fecha();
        $fechaCierre = $request->get('fecha', $today);
        $productos = $this->productosVendibles();
        $unidadesEnAlmacen = $productos->sum('stock_actual');

        // Cierre previo de hoy si existe
        $closureToday = DailyCashClosure::whereDate('date', $today)->latest()->first();

        // Ventas de hoy: ÚNICAMENTE las ventas de hoy pendientes de cierre (sin closure_id)
        // Una vez cerrada la caja, este conjunto queda en blanco.
        $queryHoy = Sale::with(['customer.clientType', 'seller', 'items.product'])
            ->deLaJornada($today)
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
        $ventasArqueo = Sale::with(['customer', 'items.product'])
            ->deLaJornada($fechaCierre)
            ->get();

        // Si se consulta hoy y no se ha cerrado la caja, el arqueo es de las pendientes
        $ventasParaCalculo = ($fechaCierre === $today && $ventasHoy->isNotEmpty())
            ? $ventasHoy
            : $ventasArqueo;

        $efectivoTotal = $ventasParaCalculo->where('payment_method', 'efectivo')->sum('total_amount');
        $descuentoLecheTotal = $ventasParaCalculo->where('payment_method', 'descuento_leche')->sum('total_amount');
        $totalMontoDia = $ventasParaCalculo->sum('total_amount');
        $totalUnidadesDia = $ventasParaCalculo->sum('cheese_molds_quantity');
        $totalTransaccionesDia = $ventasParaCalculo->count();

        // Desglose por tipo de cliente. Se arma desde `client_types` y no desde
        // los tres slugs de siempre, porque el administrador puede crear tipos
        // nuevos y sus ventas tienen que caer en su propia fila, no en «local».
        $tiposDeCliente = ClientType::where('is_active', true)
            ->orderBy('min_quantity')
            ->orderBy('name')
            ->get();

        $resumenClientes = [];

        foreach ($tiposDeCliente as $tipoCliente) {
            $resumenClientes[$tipoCliente->id] = [
                'id' => $tipoCliente->id,
                'nombre' => $tipoCliente->name,
                'tag' => $tipoCliente->min_quantity > 1
                    ? 'Desde '.rtrim(rtrim(number_format($tipoCliente->min_quantity, 2, '.', ''), '0'), '.').' unidades'
                    : 'Tarifa propia por producto',
                'unidades' => 0,
                'efectivo' => 0.0,
                'descuento_leche' => 0.0,
                'total_monto' => 0.0,
            ];
        }

        // Los clientes viejos que todavía no tienen tipo asignado.
        $resumenClientes[0] = [
            'id' => null,
            'nombre' => 'Sin tipo asignado',
            'tag' => 'Clientes del padrón antiguo',
            'unidades' => 0,
            'efectivo' => 0.0,
            'descuento_leche' => 0.0,
            'total_monto' => 0.0,
        ];

        foreach ($ventasParaCalculo as $v) {
            $clave = $v->customer?->client_type_id;

            if ($clave === null || ! isset($resumenClientes[$clave])) {
                // Puente para el padrón antiguo: el slug heredado del cliente.
                $porSlug = $tiposDeCliente->firstWhere('slug', $v->customer?->type);
                $clave = $porSlug->id ?? 0;
            }

            $resumenClientes[$clave]['unidades'] += (int) $v->cheese_molds_quantity;
            $resumenClientes[$clave]['total_monto'] += (float) $v->total_amount;

            if ($v->payment_method === 'descuento_leche') {
                $resumenClientes[$clave]['descuento_leche'] += (float) $v->total_amount;
            } else {
                $resumenClientes[$clave]['efectivo'] += (float) $v->total_amount;
            }
        }

        // La fila «sin tipo» solo estorba cuando no hay ninguna venta así.
        if ($resumenClientes[0]['unidades'] === 0 && $resumenClientes[0]['total_monto'] === 0.0) {
            unset($resumenClientes[0]);
        }

        return view('ventas.index', compact(
            'ventasHoy',
            'closureToday',
            'productos',
            'unidadesEnAlmacen',
            'today',
            'fechaCierre',
            'efectivoTotal',
            'descuentoLecheTotal',
            'totalMontoDia',
            'totalUnidadesDia',
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
        $totalUnidades = (int) $closure->cheese_molds_quantity;

        return redirect()->route('ventas.index')
            ->with('success', 'Cierre de caja realizado con éxito. Se conciliaron S/ '.number_format($totalCash, 2)." en efectivo y {$totalUnidades} unidades despachadas. La lista de ventas de hoy volvió a blanco.");
    }

    /**
     * Apartado de Recibos: Listado histórico de TODOS los recibos emitidos
     */
    public function receiptsHistory(Request $request)
    {
        $query = Sale::with(['customer', 'seller', 'closure', 'items.product'])->latest();

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
            $query->deLaJornada($request->fecha);
        }

        if ($request->filled('tipo_cliente')) {
            $tipo = $request->tipo_cliente;
            $query->whereHas('customer', function ($cq) use ($tipo) {
                $cq->where('type', $tipo);
            });
        }

        $sales = $query->paginate(10)->withQueryString();
        $totalRecibos = Sale::count();
        $totalRecaudadoEfectivo = Sale::where('payment_method', 'efectivo')->sum('total_amount');
        $totalDeducidoLeche = Sale::where('payment_method', 'descuento_leche')->sum('total_amount');
        $totalUnidadesHistorico = Sale::sum('cheese_molds_quantity');

        return view('ventas.receipts', compact(
            'sales',
            'totalRecibos',
            'totalRecaudadoEfectivo',
            'totalDeducidoLeche',
            'totalUnidadesHistorico'
        ));
    }

    public function create()
    {
        $productos = $this->productosVendibles();
        $customers = Customer::orderBy('last_name')->get();

        // El mostrador tarifa en el navegador con las mismas reglas que el
        // servidor, así que necesita los tipos y la tarifa de cada producto.
        $tiposCliente = ClientType::where('is_active', true)
            ->with('productPrices')
            ->orderBy('min_quantity')
            ->get();

        return view('ventas.create', compact('productos', 'customers', 'tiposCliente'));
    }

    /**
     * Lo que el mostrador puede despachar hoy: productos terminados activos con
     * el saldo que tienen en almacén, en una sola consulta de stock.
     *
     * @return Collection<int, Product>
     */
    private function productosVendibles(): Collection
    {
        $stocks = InventoryStock::pluck('current_stock', 'item_code');

        return Product::where('is_active', true)
            ->orderBy('name')
            ->get()
            ->each(function (Product $producto) use ($stocks) {
                $producto->stock_actual = (float) $stocks->get($producto->item_code, 0);
            });
    }

    /**
     * Busca un cliente por nombre completo exacto.
     *
     * El mostrador lo llama mientras se escribe el alta de un cliente nuevo:
     * si ya está registrado, se le reconoce su tarifa en vez de duplicarlo.
     */
    public function matchCustomer(Request $request)
    {
        $apellidos = trim((string) $request->query('last_name', ''));
        $nombres = trim((string) $request->query('first_name', ''));

        if ($apellidos === '') {
            return response()->json(null);
        }

        $cliente = Customer::with('linkedUser')
            ->whereRaw('LOWER(TRIM(last_name)) = ?', [mb_strtolower($apellidos)])
            ->whereRaw('LOWER(TRIM(first_name)) = ?', [mb_strtolower($nombres)])
            ->first();

        if (! $cliente) {
            return response()->json(null);
        }

        $datos = $cliente->toArray();
        $datos['linked_role'] = $cliente->linkedUser?->role;

        return response()->json($datos);
    }

    // Endpoint JSON para búsqueda ágil de clientes por apellido o DNI
    public function searchCustomer(Request $request)
    {
        $term = $request->query('q', '');
        $customers = Customer::with('linkedUser')
            ->where(function ($q) use ($term) {
                $q->where('last_name', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('dni_ruc', 'like', "%{$term}%");
            })
            ->take(15)
            ->get()
            ->map(function (Customer $cliente) {
                // El rol viaja porque la caja lo necesita para reconocer la
                // tarifa que le corresponde sin preguntar al servidor.
                $datos = $cliente->toArray();
                $datos['linked_role'] = $cliente->linkedUser?->role;

                return $datos;
            });

        return response()->json($customers);
    }

    // Calcular la tarifa de un producto para ese cliente antes de confirmar
    public function calculatePrice(Request $request)
    {
        $quantity = max(1, (int) $request->quantity);
        $customer = $request->customer_id ? Customer::find($request->customer_id) : null;
        $producto = Product::find($request->product_id);

        if (! $producto) {
            return response()->json(['message' => 'Producto no encontrado.'], 404);
        }

        $unitPrice = $producto->priceForCustomer($customer, $quantity);

        return response()->json([
            'product_id' => $producto->id,
            'unit_price' => $unitPrice,
            'total' => round($unitPrice * $quantity, 2),
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
            // Renglones de la venta:
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
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
        $sale->load(['customer', 'seller', 'items.product']);

        $user = Auth::user();
        if ($user && $user->role === 'productor') {
            if ($sale->customer->linked_user_id !== $user->id) {
                abort(403, 'No tienes autorización para consultar este recibo.');
            }
        }

        return view('ventas.receipt', compact('sale'));
    }
}
