<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Services\Produccion\ProduccionService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Lotes de producción: planificar, iniciar (descuenta insumos), esperar el
 * proceso y cerrar sumando el producto terminado.
 *
 * Quien opera la planta es el jefe de producción: es el que está parado
 * frente a la tina y el único que sabe si el lote arrancó o se echó a perder.
 * La administración entra a leer el reporte, no a mover lotes.
 */
class ProductionOrderController extends Controller
{
    /** Quienes pueden ver el reporte de lotes. */
    public const ROLES_LECTURA = ['jefe_produccion', 'admin', 'jefe_general'];

    /** Quien de verdad opera la planta. */
    public const ROLES_OPERACION = ['jefe_produccion'];

    public function __construct(private ProduccionService $produccion) {}

    public function index(Request $request)
    {
        if ($redirect = $this->bloquearSiNoPuedeLeer()) {
            return $redirect;
        }

        $productos = Product::with(['recipeItems.supply', 'recipeItems.componentProduct'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // La receta y el saldo de cada ingrediente viajan a la pantalla para
        // que el formulario pueda repartir el mismo almacén entre los
        // renglones: si el queso se lleva la leche, al yogur le queda lo que
        // sobra y no el total otra vez.
        $recetas = $productos->mapWithKeys(fn (Product $producto) => [
            $producto->id => [
                'unidad' => $producto->unit,
                'ingredientes' => $producto->recipeItems
                    ->filter(fn ($item) => $item->ingrediente() !== null)
                    ->map(fn ($item) => [
                        'clave' => $item->ingrediente()->item_code,
                        'nombre' => $item->nombreIngrediente(),
                        'unidad' => $item->unidadIngrediente(),
                        'porUnidad' => (float) $item->quantity_per_unit,
                    ])->values(),
            ],
        ]);

        $stockIngredientes = $productos->flatMap(fn (Product $producto) => $producto->recipeItems)
            ->filter(fn ($item) => $item->ingrediente() !== null)
            ->mapWithKeys(fn ($item) => [$item->ingrediente()->item_code => $item->stockDisponible()]);

        $puedeOperar = in_array(Auth::user()->role, self::ROLES_OPERACION, true);

        $abiertos = ProductionOrder::with(['product', 'supervisor', 'items.supply', 'items.componentProduct'])
            ->whereIn('status', ['planificada', 'en_proceso'])
            ->orderBy('expected_ready_at')
            ->paginate(10, ['*'], 'pagina_abiertos')
            ->withQueryString();

        $cerrados = ProductionOrder::with(['product', 'supervisor'])
            ->whereIn('status', ['terminada', 'cancelada'])
            ->when($request->filled('buscar'), fn ($q) => $q->where('batch_number', 'like', '%'.$request->buscar.'%'))
            ->when($request->filled('estado'), fn ($q) => $q->where('status', $request->estado))
            ->latest('finished_at')
            ->paginate(10, ['*'], 'pagina_cerrados')
            ->withQueryString();

        return view('produccion.lotes', compact(
            'productos',
            'abiertos',
            'cerrados',
            'recetas',
            'stockIngredientes',
            'puedeOperar'
        ));
    }

    public function store(Request $request)
    {
        if ($redirect = $this->bloquearSiNoPuedeOperar()) {
            return $redirect;
        }

        $datos = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.planned_quantity' => ['nullable', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'iniciar_ahora' => ['nullable', 'boolean'],
        ], [
            'items.required' => 'Agrega al menos un producto al lote.',
            'items.*.planned_quantity.gt' => 'Cada producto necesita una cantidad mayor que cero.',
        ]);

        $renglones = $this->renglonesDesde($datos['items']);

        if ($renglones === []) {
            return back()->withErrors(['items' => 'Agrega al menos un producto al lote.'])->withInput();
        }

        try {
            $ordenes = $this->produccion->planificarVarios(
                $renglones,
                Auth::user(),
                $datos['notes'] ?? null,
                $request->boolean('iniciar_ahora')
            );
        } catch (ReglaNegocioException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()])->withInput();
        }

        return back()->with('success', $this->resumenDe($ordenes));
    }

    /**
     * Los renglones del formulario, ya limpios.
     *
     * La pantalla deja renglones vacíos mientras se arma la tanda; esos se
     * descartan aquí en vez de hacer fallar la validación entera.
     *
     * @param  array<int, array<string, mixed>>  $filas
     * @return array<int, array{product: Product, quantity: float}>
     */
    private function renglonesDesde(array $filas): array
    {
        $renglones = [];

        foreach ($filas as $fila) {
            $productoId = (int) ($fila['product_id'] ?? 0);
            $cantidad = (float) ($fila['planned_quantity'] ?? 0);

            if ($productoId <= 0 || $cantidad <= 0) {
                continue;
            }

            $renglones[] = [
                'product' => Product::findOrFail($productoId),
                'quantity' => $cantidad,
            ];
        }

        return $renglones;
    }

    /**
     * El aviso de lo que quedó guardado, con su número de lote y su contenido.
     *
     * @param  Collection<int, ProductionOrder>  $ordenes
     */
    private function resumenDe($ordenes): string
    {
        $detalle = $ordenes->map(function (ProductionOrder $orden) {
            $cantidad = rtrim(rtrim(number_format((float) $orden->planned_quantity, 2, '.', ''), '0'), '.');

            return "#{$orden->id} {$orden->batch_number} ({$cantidad} {$orden->product->unit} de {$orden->product->name})";
        })->implode(' · ');

        $enProceso = $ordenes->first()?->status === 'en_proceso';

        return $enProceso
            ? "En proceso: {$detalle}."
            : "Planificado: {$detalle}. Inicia cada lote cuando la planta esté lista.";
    }

    public function start(ProductionOrder $order)
    {
        if ($redirect = $this->bloquearSiNoPuedeOperar()) {
            return $redirect;
        }

        try {
            $order = $this->produccion->iniciar($order);
        } catch (ReglaNegocioException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()]);
        }

        $listo = $order->expected_ready_at ? $order->expected_ready_at->format('d/m H:i') : 'de inmediato';

        return back()->with('success', "Lote {$order->batch_number} iniciado. Insumos descontados del almacén. Listo: {$listo}.");
    }

    public function finish(Request $request, ProductionOrder $order)
    {
        if ($redirect = $this->bloquearSiNoPuedeOperar()) {
            return $redirect;
        }

        $datos = $request->validate([
            'produced_quantity' => ['nullable', 'numeric', 'gt:0'],
        ]);

        try {
            $order = $this->produccion->terminar(
                $order,
                isset($datos['produced_quantity']) ? (float) $datos['produced_quantity'] : null
            );
        } catch (ReglaNegocioException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()]);
        }

        $cantidad = rtrim(rtrim(number_format((float) $order->produced_quantity, 2, '.', ''), '0'), '.');

        return back()->with('success', "Lote {$order->batch_number} terminado: {$cantidad} {$order->product->unit} al almacén.");
    }

    public function cancel(Request $request, ProductionOrder $order)
    {
        if ($redirect = $this->bloquearSiNoPuedeOperar()) {
            return $redirect;
        }

        $datos = $request->validate([
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->produccion->cancelar($order, $datos['motivo'] ?? null);
        } catch (ReglaNegocioException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()]);
        }

        return back()->with('success', "Lote {$order->batch_number} cancelado. Los insumos volvieron al almacén.");
    }

    private function bloquearSiNoPuedeLeer()
    {
        if (! in_array(Auth::user()->role, self::ROLES_LECTURA, true)) {
            return redirect()->route('dashboard')
                ->withErrors(['general' => 'No tienes acceso a los lotes de la planta.']);
        }

        return null;
    }

    private function bloquearSiNoPuedeOperar()
    {
        if (! in_array(Auth::user()->role, self::ROLES_OPERACION, true)) {
            return redirect()->route('produccion.lotes.index')
                ->withErrors(['general' => 'Solo el jefe de producción mueve los lotes; desde aquí se ve el reporte.']);
        }

        return null;
    }
}
