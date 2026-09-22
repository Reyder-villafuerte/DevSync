<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\ClientType;
use App\Models\InventoryCategory;
use App\Models\InventoryStock;
use App\Models\MeasurementUnit;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\Supply;
use App\Models\SupplyMovement;
use App\Services\Produccion\CatalogoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Almacén: dos existencias distintas bajo el mismo techo. Los insumos, que se
 * compran y se consumen y por eso solo tienen costo; y los productos
 * terminados, que se producen y se venden y por eso llevan sus tres tarifas.
 *
 * Las categorías y las unidades se administran aparte, en
 * {@see InventoryCategoryController}.
 */
class CatalogoAlmacenController extends Controller
{
    public const ROLES_PERMITIDOS = ['jefe_produccion', 'admin', 'jefe_general'];

    public function __construct(private CatalogoService $catalogo) {}

    public function index(Request $request)
    {
        if ($redirect = $this->bloquearSiNoAutorizado()) {
            return $redirect;
        }

        $categorias = InventoryCategory::where('is_active', true)->orderBy('name')->get();

        // Un solo viaje al almacén: el resto de la pantalla lee de este mapa en
        // lugar de consultar el stock fila por fila.
        $stocks = InventoryStock::pluck('current_stock', 'item_code')
            ->map(fn ($saldo) => (float) $saldo);

        // Lo que se compra y se consume. Nunca tiene precio de venta.
        $insumos = Supply::with(['category', 'measurementUnit'])
            ->where('is_active', true)
            ->when($request->filled('buscar'), fn ($q) => $q->where('name', 'like', '%'.$request->buscar.'%'))
            ->when($request->filled('entrada'), fn ($q) => $q->where('entry_mode', $request->entrada))
            ->orderBy('name')
            ->paginate(10, ['*'], 'pagina_insumos')
            ->withQueryString();

        // Lo que se produce y se vende, con sus tarifas por tipo de cliente.
        $productos = Product::with(['category', 'measurementUnit', 'recipeItems.supply'])
            ->where('is_active', true)
            ->orderBy('name')
            ->paginate(10, ['*'], 'pagina_productos')
            ->withQueryString();

        $resumen = [
            'categorias' => InventoryCategory::count(),
            'insumos' => Supply::where('is_active', true)->count(),
            'productos' => Product::where('is_active', true)->count(),
            'lotes_en_proceso' => ProductionOrder::where('status', 'en_proceso')->count(),
        ];

        // El aviso de mínimos mira TODO el almacén, no solo la página visible.
        $bajoMinimo = Supply::with('category')->where('is_active', true)->get()->filter(
            fn (Supply $insumo) => ($stocks[$insumo->item_code] ?? 0.0) < (float) $insumo->minimum_stock
        );

        $unidades = MeasurementUnit::where('is_active', true)->orderBy('name')->get();

        // Para el margen se toma la tarifa de público, que es la de referencia.
        $tipoPublico = ClientType::tipoPublico();

        $movimientos = SupplyMovement::with(['supply', 'order', 'registrar'])
            ->latest('id')
            ->paginate(10, ['*'], 'pagina_kardex')
            ->withQueryString();

        return view('produccion.almacen', compact(
            'categorias',
            'insumos',
            'productos',
            'stocks',
            'tipoPublico',
            'resumen',
            'bajoMinimo',
            'unidades',
            'movimientos'
        ));
    }

    public function storeInsumo(Request $request)
    {
        if ($redirect = $this->bloquearSiNoAutorizado()) {
            return $redirect;
        }

        $datos = $request->validate([
            'inventory_category_id' => ['required', 'exists:inventory_categories,id'],
            'name' => ['required', 'string', 'max:120'],
            'measurement_unit_id' => ['required', 'exists:measurement_units,id'],
            'item_code' => ['nullable', 'string', 'max:50'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'entry_mode' => ['nullable', 'in:compra,acopio'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'initial_stock' => ['nullable', 'numeric', 'min:0'],
        ]);

        $categoria = InventoryCategory::findOrFail($datos['inventory_category_id']);

        try {
            $insumo = $this->catalogo->crearInsumo($categoria, $datos);
        } catch (ReglaNegocioException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()])->withInput();
        }

        return back()->with('success', "Insumo «{$insumo->name}» agregado a {$categoria->name}.");
    }

    public function ajustarStock(Request $request, Supply $supply)
    {
        if ($redirect = $this->bloquearSiNoAutorizado()) {
            return $redirect;
        }

        $datos = $request->validate([
            'delta' => ['required', 'numeric'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $stock = $this->catalogo->ajustarStockInsumo(
                $supply,
                (float) $datos['delta'],
                Auth::user(),
                $datos['notes'] ?? null
            );
        } catch (ReglaNegocioException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()])->withInput();
        }

        return back()->with('success', "Almacén actualizado: {$supply->name} queda en {$stock} {$supply->unit}.");
    }

    private function bloquearSiNoAutorizado()
    {
        if (! in_array(Auth::user()->role, self::ROLES_PERMITIDOS, true)) {
            return redirect()->route('dashboard')
                ->withErrors(['general' => 'Solo la jefatura de producción administra el catálogo de la planta.']);
        }

        return null;
    }
}
