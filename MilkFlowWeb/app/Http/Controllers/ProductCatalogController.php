<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\ClientType;
use App\Models\InventoryCategory;
use App\Models\MeasurementUnit;
use App\Models\Product;
use App\Models\Supply;
use App\Services\Produccion\CatalogoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Productos del catálogo: qué sabe hacer la planta, con qué receta, en cuánto
 * tiempo y a qué precio para proveedor, mayorista y cliente local.
 */
class ProductCatalogController extends Controller
{
    public function __construct(private CatalogoService $catalogo) {}

    public function index(Request $request)
    {
        if ($redirect = $this->bloquearSiNoAutorizado()) {
            return $redirect;
        }

        $productos = Product::with([
            'category',
            'recipeItems.supply.category',
            'recipeItems.componentProduct.category',
        ])
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $termino = $request->buscar;

                $q->where('name', 'like', "%{$termino}%")->orWhere('item_code', 'like', "%{$termino}%");
            })
            ->when($request->filled('categoria'), fn ($q) => $q->where('inventory_category_id', $request->categoria))
            ->when($request->filled('estado'), fn ($q) => $q->where('is_active', $request->estado === 'activos'))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $categorias = InventoryCategory::where('is_active', true)
            ->orderBy('name')
            ->get();

        $insumos = Supply::with('category')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $unidades = MeasurementUnit::where('is_active', true)->orderBy('name')->get();

        // Cualquier producto activo puede ser ingrediente de otro (semielaborado).
        $componentes = Product::where('is_active', true)->orderBy('name')->get();

        // Los tipos a los que se les puede poner tarifa propia.
        $tiposCliente = ClientType::where('is_active', true)
            ->with('productPrices')
            ->orderBy('min_quantity')
            ->orderBy('name')
            ->get();

        return view('produccion.productos', compact(
            'productos',
            'categorias',
            'insumos',
            'componentes',
            'tiposCliente',
            'unidades'
        ));
    }

    public function store(Request $request)
    {
        if ($redirect = $this->bloquearSiNoAutorizado()) {
            return $redirect;
        }

        $datos = $request->validate([
            'inventory_category_id' => ['required', 'exists:inventory_categories,id'],
            'name' => ['required', 'string', 'max:120'],
            'measurement_unit_id' => ['required', 'exists:measurement_units,id'],
            'item_code' => ['nullable', 'string', 'max:50'],
            'process_hours' => ['required', 'numeric', 'min:0'],
            'tarifas' => ['nullable', 'array'],
            'tarifas.*.client_type_id' => ['nullable', 'exists:client_types,id'],
            'tarifas.*.price_per_unit' => ['nullable', 'numeric', 'min:0'],
            'process_notes' => ['nullable', 'string', 'max:1000'],
            'receta' => ['required', 'array', 'min:1'],
            'receta.*.ingrediente' => ['nullable', 'string', 'regex:/^[ip]:\\d+$/'],
            'receta.*.quantity_per_unit' => ['required', 'numeric', 'gt:0'],
            'receta.*.notes' => ['nullable', 'string', 'max:255'],
        ], [
            'receta.required' => 'Agrega al menos un ingrediente a la receta (por ejemplo, los litros de leche).',
            'receta.*.quantity_per_unit.gt' => 'Cada ingrediente de la receta necesita una cantidad mayor que cero.',
        ]);

        $categoria = InventoryCategory::findOrFail($datos['inventory_category_id']);

        $tarifas = $this->tarifasDesde($datos['tarifas'] ?? []);

        // El producto nace con las tres columnas heredadas puestas en lo que se
        // cargó para los tres tipos de siempre; el resto se fija después.
        $datos += $this->columnasHeredadasDesde($tarifas);

        try {
            $producto = $this->catalogo->crearProducto($categoria, $datos, $this->recetaDesde($datos['receta']), Auth::user());
            $this->catalogo->fijarTarifas($producto, $tarifas, reemplazar: true);
        } catch (ReglaNegocioException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()])->withInput();
        }

        return redirect()->route('produccion.productos.index')
            ->with('success', "Producto «{$producto->name}» creado con {$producto->recipeItems->count()} insumo(s) en su receta.");
    }

    public function updateReceta(Request $request, Product $product)
    {
        if ($redirect = $this->bloquearSiNoAutorizado()) {
            return $redirect;
        }

        $datos = $request->validate([
            'receta' => ['required', 'array', 'min:1'],
            'receta.*.ingrediente' => ['nullable', 'string', 'regex:/^[ip]:\\d+$/'],
            'receta.*.quantity_per_unit' => ['required', 'numeric', 'gt:0'],
            'receta.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->catalogo->reemplazarReceta($product, $this->recetaDesde($datos['receta']));
        } catch (ReglaNegocioException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()])->withInput();
        }

        return back()->with('success', "Receta de «{$product->name}» actualizada.");
    }

    public function updatePrecios(Request $request, Product $product)
    {
        if ($redirect = $this->bloquearSiNoAutorizado()) {
            return $redirect;
        }

        $datos = $request->validate([
            // Renglones de tarifa: tipo de cliente y su precio. El tipo que no
            // se liste cobra su tarifa estándar.
            'tarifas' => ['nullable', 'array'],
            'tarifas.*.client_type_id' => ['nullable', 'exists:client_types,id'],
            'tarifas.*.price_per_unit' => ['nullable', 'numeric', 'min:0'],
            'process_hours' => ['required', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $product->update([
            'process_hours' => $datos['process_hours'],
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->catalogo->fijarTarifas($product, $this->tarifasDesde($datos['tarifas'] ?? []), reemplazar: true);

        return back()->with('success', "Tarifas y tiempo de proceso de «{$product->name}» actualizados.");
    }

    private function bloquearSiNoAutorizado()
    {
        if (! in_array(Auth::user()->role, CatalogoAlmacenController::ROLES_PERMITIDOS, true)) {
            return redirect()->route('dashboard')
                ->withErrors(['general' => 'Solo la jefatura de producción administra el catálogo de la planta.']);
        }

        return null;
    }

    /**
     * El formulario manda un solo campo por renglón, «i:12» o «p:3», porque el
     * desplegable mezcla insumos y productos. Aquí se abre en las dos columnas
     * que entiende el catálogo; un renglón sin ingrediente se descarta, que es
     * como se quita algo de la receta.
     *
     * @param  array<int, array<string, mixed>>  $renglones
     * @return array<int, array<string, mixed>>
     */
    private function recetaDesde(array $renglones): array
    {
        $receta = [];

        foreach ($renglones as $renglon) {
            $ingrediente = trim((string) ($renglon['ingrediente'] ?? ''));

            if ($ingrediente === '') {
                continue;
            }

            [$tipo, $id] = explode(':', $ingrediente, 2);

            $receta[] = [
                'supply_id' => $tipo === 'i' ? (int) $id : null,
                'component_product_id' => $tipo === 'p' ? (int) $id : null,
                'quantity_per_unit' => (float) ($renglon['quantity_per_unit'] ?? 0),
                'notes' => $renglon['notes'] ?? null,
            ];
        }

        return $receta;
    }

    /**
     * Las tres columnas heredadas a partir de las tarifas cargadas.
     *
     * `crearProducto` todavía las pide porque la app móvil las lee; se toman
     * de los tres tipos sembrados y, si no vinieron, de su estándar.
     *
     * @param  array<int|string, mixed>  $tarifas
     * @return array<string, float>
     */
    private function columnasHeredadasDesde(array $tarifas): array
    {
        $columnas = ['proveedor' => 'price_provider', 'mayorista' => 'price_wholesale', 'local' => 'price_local'];
        $valores = [];

        foreach (ClientType::whereIn('slug', array_keys($columnas))->get() as $tipo) {
            // Lo que se cargó para ese tipo; si no vino, cero: el reflejo real
            // lo hace `fijarTarifas` en cuanto se guardan las tarifas.
            $valores[$columnas[$tipo->slug]] = (float) ($tarifas[$tipo->id] ?? 0);
        }

        return $valores + ['price_provider' => 0.0, 'price_wholesale' => 0.0, 'price_local' => 0.0];
    }

    /**
     * Los renglones de tarifa del formulario, como mapa tipo => precio.
     *
     * El desplegable deja elegir el tipo, así que llegan como lista. Un
     * renglón sin tipo o sin precio se descarta: ese tipo se queda con su
     * tarifa estándar.
     *
     * @param  array<int, array<string, mixed>>  $filas
     * @return array<int, float>
     */
    private function tarifasDesde(array $filas): array
    {
        $tarifas = [];

        foreach ($filas as $fila) {
            $tipoId = (int) ($fila['client_type_id'] ?? 0);
            $precio = $fila['price_per_unit'] ?? null;

            if ($tipoId <= 0 || $precio === null || $precio === '') {
                continue;
            }

            $tarifas[$tipoId] = (float) $precio;
        }

        return $tarifas;
    }
}
