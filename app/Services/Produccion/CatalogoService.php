<?php

namespace App\Services\Produccion;

use App\Exceptions\ReglaNegocioException;
use App\Models\ClientType;
use App\Models\InventoryCategory;
use App\Models\InventoryStock;
use App\Models\MeasurementUnit;
use App\Models\Product;
use App\Models\ProductClientTypePrice;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Alta y mantenimiento del catálogo: categorías, insumos y productos con su
 * receta y sus tres tarifas.
 *
 * Nada de esto vive en el código: la planta lo crea desde la web, así que el
 * día que decida hacer yogurt de fresa embotellado no hace falta programar.
 */
class CatalogoService
{
    public function __construct(private AlmacenService $almacen) {}

    /** Crea una categoría de almacén ("Envases", "Frutas"). */
    public function crearCategoria(string $nombre, ?string $descripcion = null, ?User $autor = null): InventoryCategory
    {
        $nombre = trim($nombre);

        if ($nombre === '') {
            throw new ReglaNegocioException('La categoría necesita un nombre.', 'name');
        }

        $slug = Str::slug($nombre);

        if (InventoryCategory::where('slug', $slug)->exists()) {
            throw new ReglaNegocioException("Ya existe una categoría llamada {$nombre}.", 'name');
        }

        return InventoryCategory::create([
            'name' => $nombre,
            'slug' => $slug,
            'description' => $descripcion,
            'is_active' => true,
            'created_by' => $autor?->id,
        ]);
    }

    /**
     * Da de alta un insumo dentro de una categoría y deja creada su fila de
     * stock, para que el almacén lo muestre desde el primer día en cero.
     *
     * @param  array{item_code?: string|null, name: string, unit: string, unit_cost?: float, minimum_stock?: float, initial_stock?: float}  $datos
     */
    public function crearInsumo(InventoryCategory $categoria, array $datos): Supply
    {
        $nombre = trim($datos['name'] ?? '');
        $medida = $this->unidadDeMedida($datos);
        $unidad = $medida ? $medida->abbreviation : trim($datos['unit'] ?? '');

        if ($nombre === '' || $unidad === '') {
            throw new ReglaNegocioException('El insumo necesita nombre y unidad de medida.', 'name');
        }

        $codigo = strtoupper(trim($datos['item_code'] ?? '')) ?: $this->codigoDesde($nombre);

        $this->verificarCodigoLibre($codigo);

        return DB::transaction(function () use ($categoria, $datos, $nombre, $unidad, $medida, $codigo) {
            $insumo = Supply::create([
                'inventory_category_id' => $categoria->id,
                'item_code' => $codigo,
                'name' => $nombre,
                'unit' => $unidad,
                'measurement_unit_id' => $medida?->id,
                'unit_cost' => $datos['unit_cost'] ?? 0,
                'entry_mode' => in_array($datos['entry_mode'] ?? '', Supply::ENTRADAS, true)
                    ? $datos['entry_mode']
                    : Supply::ENTRADA_COMPRA,
                'minimum_stock' => $datos['minimum_stock'] ?? 0,
                'is_active' => true,
            ]);

            $stockInicial = (float) ($datos['initial_stock'] ?? 0);

            InventoryStock::adjustStock($codigo, 0, $nombre, $unidad)->vincularInsumo($insumo);

            if ($stockInicial > 0) {
                $this->almacen->mover($insumo, $stockInicial, 'ingreso', null, null, 'Stock inicial del alta de insumo.');
            }

            return $insumo;
        });
    }

    /**
     * Crea el producto y su receta en un solo paso: primero la categoría, luego
     * los insumos que consume cada unidad y al final sus tres precios.
     *
     * @param  array{item_code?: string|null, name: string, unit: string, process_hours?: float, price_provider?: float, price_wholesale?: float, price_local?: float, process_notes?: string|null}  $datos
     * @param  array<int, array{supply_id: int, quantity_per_unit: float, notes?: string|null}>  $receta
     */
    public function crearProducto(InventoryCategory $categoria, array $datos, array $receta, ?User $autor = null): Product
    {
        $nombre = trim($datos['name'] ?? '');
        $medida = $this->unidadDeMedida($datos);
        $unidad = $medida ? $medida->abbreviation : trim($datos['unit'] ?? '');

        if ($nombre === '' || $unidad === '') {
            throw new ReglaNegocioException('El producto necesita nombre y unidad de medida.', 'name');
        }

        if (empty($receta)) {
            throw new ReglaNegocioException('Un producto sin receta no se puede producir: agrega al menos un ingrediente.', 'receta');
        }

        $codigo = strtoupper(trim($datos['item_code'] ?? '')) ?: $this->codigoDesde($nombre);

        $this->verificarCodigoLibre($codigo);

        return DB::transaction(function () use ($categoria, $datos, $receta, $nombre, $unidad, $medida, $codigo, $autor) {
            $producto = Product::create([
                'inventory_category_id' => $categoria->id,
                'item_code' => $codigo,
                'name' => $nombre,
                'unit' => $unidad,
                'measurement_unit_id' => $medida?->id,
                'process_hours' => $datos['process_hours'] ?? 0,
                'price_provider' => $datos['price_provider'] ?? 0,
                'price_wholesale' => $datos['price_wholesale'] ?? 0,
                'price_local' => $datos['price_local'] ?? 0,
                'process_notes' => $datos['process_notes'] ?? null,
                'is_active' => true,
                'created_by' => $autor?->id,
            ]);

            $this->reemplazarReceta($producto, $receta);
            $producto->syncTarifasBase();

            InventoryStock::adjustStock($codigo, 0, $nombre, $unidad)->vincularProducto($producto);

            return $producto->fresh('recipeItems');
        });
    }

    /**
     * Reescribe la receta completa del producto.
     *
     * Cada renglón apunta a un insumo (`supply_id`) o a otro producto de la
     * planta (`component_product_id`), que es como se expresa un semielaborado.
     *
     * @param  array<int, array{supply_id?: int|null, component_product_id?: int|null, quantity_per_unit: float, notes?: string|null}>  $receta
     */
    public function reemplazarReceta(Product $producto, array $receta): Product
    {
        $normalizada = [];

        foreach ($receta as $renglon) {
            $insumoId = (int) ($renglon['supply_id'] ?? 0);
            $componenteId = (int) ($renglon['component_product_id'] ?? 0);
            $cantidad = (float) ($renglon['quantity_per_unit'] ?? 0);

            if ($insumoId <= 0 && $componenteId <= 0) {
                continue;
            }

            if ($insumoId > 0 && $componenteId > 0) {
                throw new ReglaNegocioException('Cada renglón de la receta es un insumo o un producto, no los dos.', 'receta');
            }

            if ($cantidad <= 0) {
                throw new ReglaNegocioException('Cada ingrediente de la receta necesita una cantidad mayor que cero.', 'receta');
            }

            if ($insumoId > 0) {
                if (! Supply::whereKey($insumoId)->exists()) {
                    throw new ReglaNegocioException('Uno de los insumos de la receta ya no existe.', 'receta');
                }

                $clave = 'i'.$insumoId;
            } else {
                $componente = Product::find($componenteId);

                if (! $componente) {
                    throw new ReglaNegocioException('Uno de los productos de la receta ya no existe.', 'receta');
                }

                $this->verificarQueNoSeMuerdaLaCola($producto, $componente);

                $clave = 'p'.$componenteId;
            }

            $normalizada[$clave] = [
                'supply_id' => $insumoId > 0 ? $insumoId : null,
                'component_product_id' => $componenteId > 0 ? $componenteId : null,
                'quantity_per_unit' => $cantidad,
                'notes' => $renglon['notes'] ?? null,
            ];
        }

        if (empty($normalizada)) {
            throw new ReglaNegocioException('Un producto sin receta no se puede producir: agrega al menos un ingrediente.', 'receta');
        }

        return DB::transaction(function () use ($producto, $normalizada) {
            $producto->recipeItems()->delete();

            foreach ($normalizada as $renglon) {
                $producto->recipeItems()->create($renglon);
            }

            return $producto->fresh('recipeItems');
        });
    }

    /**
     * Un producto no puede llevarse a sí mismo, ni directo ni dando la vuelta.
     *
     * Sin esta verificación, «A lleva B» y «B lleva A» dejarían el cálculo de
     * costos y el arranque del lote girando para siempre.
     */
    private function verificarQueNoSeMuerdaLaCola(Product $producto, Product $componente): void
    {
        if ($producto->id === $componente->id) {
            throw new ReglaNegocioException("«{$producto->name}» no puede llevarse a sí mismo.", 'receta');
        }

        if ($this->dependeDe($componente, $producto->id)) {
            throw new ReglaNegocioException(
                "«{$componente->name}» ya lleva «{$producto->name}» en su receta: se harían circulares.",
                'receta'
            );
        }
    }

    /** @param  array<int, bool>  $vistos */
    private function dependeDe(Product $candidato, int $buscadoId, array $vistos = []): bool
    {
        if (isset($vistos[$candidato->id])) {
            return false;
        }

        $vistos[$candidato->id] = true;

        foreach ($candidato->recipeItems()->whereNotNull('component_product_id')->with('componentProduct')->get() as $renglon) {
            $hijo = $renglon->componentProduct;

            if (! $hijo) {
                continue;
            }

            if ($hijo->id === $buscadoId || $this->dependeDe($hijo, $buscadoId, $vistos)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fija las tarifas propias del producto, una por tipo de cliente.
     *
     * Un valor vacío quita la tarifa propia: ese tipo vuelve a cobrar su
     * estándar. Al final se reflejan las tres de siempre en las columnas
     * heredadas, que son las que viaja el móvil.
     *
     * Con `$reemplazar` en true la lista es la verdad completa: los tipos que
     * no vengan pierden su tarifa propia y vuelven al estándar. Es lo que
     * necesita un formulario que muestra todos los renglones.
     *
     * @param  array<int|string, mixed>  $tarifas  client_type_id => precio
     */
    public function fijarTarifas(Product $producto, array $tarifas, bool $reemplazar = false): Product
    {
        return DB::transaction(function () use ($producto, $tarifas, $reemplazar) {
            if ($reemplazar) {
                $conservados = array_map('intval', array_keys(array_filter(
                    $tarifas,
                    fn ($precio) => $precio !== null && $precio !== ''
                )));

                $producto->clientTypePrices()->whereNotIn('client_type_id', $conservados ?: [0])->delete();
            }

            foreach ($tarifas as $tipoId => $precio) {
                $tipoId = (int) $tipoId;

                if ($tipoId <= 0 || ! ClientType::whereKey($tipoId)->exists()) {
                    continue;
                }

                $vacio = $precio === null || $precio === '';

                if ($vacio) {
                    $producto->clientTypePrices()->where('client_type_id', $tipoId)->delete();

                    continue;
                }

                if ((float) $precio < 0) {
                    throw new ReglaNegocioException('Una tarifa no puede ser negativa.', 'tarifas');
                }

                ProductClientTypePrice::updateOrCreate(
                    ['product_id' => $producto->id, 'client_type_id' => $tipoId],
                    ['price_per_unit' => (float) $precio],
                );
            }

            $this->reflejarTarifasHeredadas($producto->fresh('clientTypePrices'));

            return $producto->fresh('clientTypePrices');
        });
    }

    /**
     * Copia a `price_provider` / `price_wholesale` / `price_local` lo que hoy
     * cobran los tres tipos sembrados. La app móvil lee esas columnas.
     */
    private function reflejarTarifasHeredadas(Product $producto): void
    {
        $columnas = ['proveedor' => 'price_provider', 'mayorista' => 'price_wholesale', 'local' => 'price_local'];
        $cambios = [];

        foreach (ClientType::whereIn('slug', array_keys($columnas))->get() as $tipo) {
            $cambios[$columnas[$tipo->slug]] = $tipo->priceFor($producto) ?? 0;
        }

        if ($cambios) {
            $producto->update($cambios);
        }
    }

    /** Entrada o salida manual de almacén (compra de botellas, merma de fruta). */
    public function ajustarStockInsumo(Supply $insumo, float $delta, ?User $usuario = null, ?string $notas = null): float
    {
        if (abs($delta) < 0.0001) {
            throw new ReglaNegocioException('El ajuste de almacén no puede ser cero.', 'delta');
        }

        $stockActual = $insumo->stock();

        if ($delta < 0 && $stockActual + $delta < -0.0001) {
            $retiro = $this->numero(abs($delta));
            $disponible = $this->numero($stockActual);

            throw new ReglaNegocioException(
                "No puedes retirar {$retiro} {$insumo->unit}: en almacén solo hay {$disponible}.",
                'delta'
            );
        }

        return $this->almacen->mover(
            $insumo,
            $delta,
            'ajuste',
            null,
            $usuario,
            $notas ?: ($delta > 0 ? 'Entrada manual de almacén.' : 'Salida manual de almacén.')
        );
    }

    /** Crea una unidad de medida del catálogo ("Kilogramos", "kg"). */
    public function crearUnidadMedida(string $nombre, string $abreviatura): MeasurementUnit
    {
        $nombre = trim($nombre);
        $abreviatura = trim($abreviatura);

        if ($nombre === '' || $abreviatura === '') {
            throw new ReglaNegocioException('La unidad necesita nombre y abreviatura.', 'name');
        }

        if (MeasurementUnit::where('abbreviation', $abreviatura)->exists()) {
            throw new ReglaNegocioException("Ya existe una unidad con la abreviatura {$abreviatura}.", 'abbreviation');
        }

        return MeasurementUnit::create([
            'name' => $nombre,
            'abbreviation' => $abreviatura,
            'is_active' => true,
        ]);
    }

    /** @param  array<string, mixed>  $datos */
    private function unidadDeMedida(array $datos): ?MeasurementUnit
    {
        $id = $datos['measurement_unit_id'] ?? null;

        if (! $id) {
            return null;
        }

        $medida = MeasurementUnit::find($id);

        if (! $medida) {
            throw new ReglaNegocioException('La unidad de medida elegida ya no existe.', 'measurement_unit_id');
        }

        return $medida;
    }

    /** Renombra una categoría o la activa/desactiva; el slug la sigue identificando. */
    public function actualizarCategoria(InventoryCategory $categoria, string $nombre, ?string $descripcion, bool $activa = true): InventoryCategory
    {
        $nombre = trim($nombre);

        if ($nombre === '') {
            throw new ReglaNegocioException('La categoría necesita un nombre.', 'name');
        }

        $slug = Str::slug($nombre);

        if (InventoryCategory::where('slug', $slug)->whereKeyNot($categoria->id)->exists()) {
            throw new ReglaNegocioException("Ya existe una categoría llamada {$nombre}.", 'name');
        }

        $categoria->update([
            'name' => $nombre,
            'slug' => $slug,
            'description' => $descripcion,
            'is_active' => $activa,
        ]);

        return $categoria;
    }

    /** Solo se borra una categoría vacía: si tiene insumos o productos, se conserva. */
    public function eliminarCategoria(InventoryCategory $categoria): void
    {
        $insumos = $categoria->supplies()->count();
        $productos = $categoria->products()->count();

        if ($insumos + $productos > 0) {
            throw new ReglaNegocioException(
                "«{$categoria->name}» tiene {$insumos} insumo(s) y {$productos} producto(s). Muévelos o elimínalos antes.",
                'categoria'
            );
        }

        $categoria->delete();
    }

    /** Cambia nombre y abreviatura de una unidad de medida. */
    public function actualizarUnidadMedida(MeasurementUnit $unidad, string $nombre, string $abreviatura): MeasurementUnit
    {
        $nombre = trim($nombre);
        $abreviatura = trim($abreviatura);

        if ($nombre === '' || $abreviatura === '') {
            throw new ReglaNegocioException('La unidad necesita nombre y abreviatura.', 'name');
        }

        if (MeasurementUnit::where('abbreviation', $abreviatura)->whereKeyNot($unidad->id)->exists()) {
            throw new ReglaNegocioException("Ya existe una unidad con la abreviatura {$abreviatura}.", 'abbreviation');
        }

        $unidad->update(['name' => $nombre, 'abbreviation' => $abreviatura]);

        // Los insumos y productos guardan la abreviatura para mostrarla rápido.
        Supply::where('measurement_unit_id', $unidad->id)->update(['unit' => $abreviatura]);
        Product::where('measurement_unit_id', $unidad->id)->update(['unit' => $abreviatura]);

        return $unidad;
    }

    /** Solo se borra una unidad que nadie esté usando. */
    public function eliminarUnidadMedida(MeasurementUnit $unidad): void
    {
        $enUso = $unidad->supplies()->count() + $unidad->products()->count();

        if ($enUso > 0) {
            throw new ReglaNegocioException(
                "La unidad {$unidad->label()} está en uso por {$enUso} ítem(s) del catálogo.",
                'unidad'
            );
        }

        $unidad->delete();
    }

    private function verificarCodigoLibre(string $codigo): void
    {
        if (Supply::where('item_code', $codigo)->exists() || Product::where('item_code', $codigo)->exists()) {
            throw new ReglaNegocioException("El código {$codigo} ya está en uso en el almacén.", 'item_code');
        }
    }

    private function codigoDesde(string $nombre): string
    {
        return strtoupper(Str::slug($nombre, '_'));
    }

    private function numero(float $valor): string
    {
        return rtrim(rtrim(number_format($valor, 2, '.', ''), '0'), '.');
    }
}
