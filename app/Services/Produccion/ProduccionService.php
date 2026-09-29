<?php

namespace App\Services\Produccion;

use App\Exceptions\ReglaNegocioException;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Lotes de producción.
 *
 * Producir no es instantáneo: la orden se planifica, se inicia descontando los
 * ingredientes de la receta y recién cuando cumple su tiempo de proceso entrega
 * el producto terminado al almacén.
 *
 * Un ingrediente puede ser un insumo comprado o un producto que la planta ya
 * fabricó. El consumo de insumos va al kardex (`supply_movements`); el de
 * productos componentes queda en los renglones del lote, que es donde vive la
 * historia de lo que se fabricó.
 */
class ProduccionService
{
    public function __construct(private AlmacenService $almacen) {}

    /** Registra la intención de producir; todavía no toca el almacén. */
    public function planificar(Product $producto, User $supervisor, float $cantidad, ?string $notas = null, ?string $numeroLote = null): ProductionOrder
    {
        if ($cantidad <= 0) {
            throw new ReglaNegocioException('La cantidad a producir debe ser mayor que cero.', 'planned_quantity');
        }

        if (! $producto->is_active) {
            throw new ReglaNegocioException("El producto {$producto->name} está desactivado.", 'product_id');
        }

        if ($producto->recipeItems()->count() === 0) {
            throw new ReglaNegocioException("El producto {$producto->name} no tiene receta: no se sabe qué insumos consume.", 'product_id');
        }

        return ProductionOrder::create([
            'batch_number' => $numeroLote ?: $this->numeroLote($producto),
            'product_id' => $producto->id,
            'supervisor_id' => $supervisor->id,
            'planned_quantity' => $cantidad,
            'status' => 'planificada',
            'notes' => $notas,
        ]);
    }

    /**
     * Planifica varios lotes de una sola vez, contra el mismo almacén.
     *
     * Es el caso de la planta: se sientan a programar el día y cargan queso y
     * yogur en la misma tanda. Los dos salen de la misma leche, así que no
     * alcanza con que cada renglón quepa por separado —hay que mirarlos
     * juntos—. O entran todos o no entra ninguno: dejar media tanda registrada
     * obligaría a adivinar cuál se cayó.
     *
     * @param  array<int, array{product: Product, quantity: float}>  $renglones
     * @return Collection<int, ProductionOrder>
     */
    public function planificarVarios(array $renglones, User $supervisor, ?string $notas = null, bool $iniciar = false)
    {
        if ($renglones === []) {
            throw new ReglaNegocioException('Agrega al menos un producto al lote.', 'items');
        }

        $this->verificarQueAlcanceParaTodos($renglones);

        return DB::transaction(function () use ($renglones, $supervisor, $notas, $iniciar) {
            $ordenes = collect();

            foreach ($renglones as $renglon) {
                $orden = $this->planificar($renglon['product'], $supervisor, $renglon['quantity'], $notas);

                if ($iniciar) {
                    $orden = $this->iniciar($orden);
                }

                $ordenes->push($orden);
            }

            return $ordenes;
        });
    }

    /**
     * ¿Alcanza el almacén para toda la tanda?
     *
     * Se arma el pozo de cada ingrediente una sola vez y se va descontando
     * renglón por renglón. Así, si hay 60 L de leche y el queso se lleva 50,
     * al yogur solo le quedan 10: mirar cada renglón contra los 60 dejaría
     * pasar una tanda que en la tina no cabe.
     *
     * @param  array<int, array{product: Product, quantity: float}>  $renglones
     */
    private function verificarQueAlcanceParaTodos(array $renglones): void
    {
        $pozo = [];

        foreach ($renglones as $renglon) {
            $receta = $renglon['product']->recipeItems()->with(['supply', 'componentProduct'])->get();

            foreach ($receta as $item) {
                $ingrediente = $item->ingrediente();

                if (! $ingrediente) {
                    continue;
                }

                $clave = $ingrediente->item_code;

                // El saldo de verdad se lee una sola vez por ingrediente.
                $pozo[$clave] ??= [
                    'nombre' => $item->nombreIngrediente(),
                    'unidad' => $item->unidadIngrediente(),
                    'hay' => $item->stockDisponible(),
                    'piden' => 0.0,
                ];

                $pozo[$clave]['piden'] += (float) $item->quantity_per_unit * $renglon['quantity'];
            }
        }

        foreach ($pozo as $ingrediente) {
            if ($ingrediente['piden'] > $ingrediente['hay'] + 0.0001) {
                $piden = $this->numero($ingrediente['piden']);
                $hay = $this->numero($ingrediente['hay']);

                throw new ReglaNegocioException(
                    "No alcanza {$ingrediente['nombre']}: entre todos los productos de esta tanda se "
                    ."necesitan {$piden} {$ingrediente['unidad']} y hay {$hay}.",
                    'stock'
                );
            }
        }
    }

    /**
     * Arranca el lote: valida que haya insumos, los descuenta del almacén y
     * calcula a qué hora estará listo según el tiempo de proceso del producto.
     */
    public function iniciar(ProductionOrder $orden): ProductionOrder
    {
        if ($orden->status !== 'planificada') {
            throw new ReglaNegocioException("El lote {$orden->batch_number} ya fue iniciado o cerrado.", 'status');
        }

        $producto = $orden->product()->with(['recipeItems.supply', 'recipeItems.componentProduct'])->firstOrFail();
        $cantidad = (float) $orden->planned_quantity;
        $receta = $producto->recipeItems;

        if ($receta->isEmpty()) {
            throw new ReglaNegocioException("El producto {$producto->name} no tiene receta: no se sabe qué insumos consume.", 'product_id');
        }

        foreach ($receta as $renglon) {
            $requerido = (float) $renglon->quantity_per_unit * $cantidad;
            $disponible = $renglon->stockDisponible();

            if ($disponible + 0.0001 < $requerido) {
                $faltante = $this->numero($requerido);
                $hay = $this->numero($disponible);
                $nombre = $renglon->nombreIngrediente();
                $unidad = $renglon->unidadIngrediente();

                throw new ReglaNegocioException(
                    "Falta {$nombre}: se necesitan {$faltante} {$unidad} y hay {$hay}.",
                    'stock'
                );
            }
        }

        return DB::transaction(function () use ($orden, $producto, $receta, $cantidad) {
            $orden->items()->delete();

            foreach ($receta as $renglon) {
                $usado = (float) $renglon->quantity_per_unit * $cantidad;

                if ($renglon->esProducto()) {
                    $componente = $renglon->componentProduct;

                    InventoryStock::adjustStock($componente->item_code, -$usado)
                        ->vincularProducto($componente);
                } else {
                    $this->almacen->mover(
                        $renglon->supply,
                        -$usado,
                        'consumo_produccion',
                        $orden,
                        $orden->supervisor,
                        "Consumo del lote {$orden->batch_number}."
                    );
                }

                $orden->items()->create([
                    'supply_id' => $renglon->supply_id,
                    'component_product_id' => $renglon->component_product_id,
                    'quantity_used' => $usado,
                    'unit' => $renglon->unidadIngrediente(),
                ]);
            }

            $orden->status = 'en_proceso';
            $orden->started_at = now();
            $orden->expected_ready_at = now()->addMinutes((int) round((float) $producto->process_hours * 60));
            $orden->save();

            return $orden->fresh('items');
        });
    }

    /**
     * Cierra el lote y suma el producto terminado al almacén. No se puede
     * cerrar antes de que el proceso cumpla su tiempo.
     */
    public function terminar(ProductionOrder $orden, ?float $cantidadReal = null): ProductionOrder
    {
        if ($orden->status !== 'en_proceso') {
            throw new ReglaNegocioException("El lote {$orden->batch_number} no está en proceso.", 'status');
        }

        $minutosRestantes = $orden->minutesRemaining();

        if ($minutosRestantes > 0) {
            throw new ReglaNegocioException(
                "El proceso todavía no termina: faltan {$minutosRestantes} minuto(s) para cerrar el lote {$orden->batch_number}.",
                'expected_ready_at'
            );
        }

        $producida = $cantidadReal ?? (float) $orden->planned_quantity;

        if ($producida <= 0) {
            throw new ReglaNegocioException('La cantidad producida debe ser mayor que cero.', 'produced_quantity');
        }

        return DB::transaction(function () use ($orden, $producida) {
            $producto = $orden->product;

            InventoryStock::adjustStock($producto->item_code, $producida, $producto->name, $producto->unit)
                ->vincularProducto($producto);

            $orden->status = 'terminada';
            $orden->produced_quantity = $producida;
            $orden->finished_at = now();
            $orden->save();

            return $orden->fresh('items');
        });
    }

    /** Anula el lote; si ya estaba en proceso, devuelve los insumos al almacén. */
    public function cancelar(ProductionOrder $orden, ?string $motivo = null): ProductionOrder
    {
        if (in_array($orden->status, ['terminada', 'cancelada'], true)) {
            throw new ReglaNegocioException("El lote {$orden->batch_number} ya está cerrado.", 'status');
        }

        return DB::transaction(function () use ($orden, $motivo) {
            if ($orden->status === 'en_proceso') {
                foreach ($orden->items()->with(['supply', 'componentProduct'])->get() as $item) {
                    if ($item->esProducto()) {
                        $componente = $item->componentProduct;

                        InventoryStock::adjustStock($componente->item_code, (float) $item->quantity_used)
                            ->vincularProducto($componente);

                        continue;
                    }

                    $this->almacen->mover(
                        $item->supply,
                        (float) $item->quantity_used,
                        'devolucion',
                        $orden,
                        $orden->supervisor,
                        "Devolución por cancelación del lote {$orden->batch_number}."
                    );
                }

                $orden->items()->delete();
            }

            $orden->status = 'cancelada';
            $orden->finished_at = now();
            $orden->notes = trim(($orden->notes ? $orden->notes.' | ' : '').'Cancelado: '.($motivo ?: 'sin motivo declarado'));
            $orden->save();

            return $orden;
        });
    }

    /**
     * El número de lote: producto, momento y correlativo.
     *
     * El momento llega al segundo, y programar la tanda del día registra
     * varios lotes dentro del mismo segundo. Cuando el del mismo producto ya
     * está tomado se le agrega un correlativo, porque `batch_number` es
     * único y si no la segunda línea de la tanda revienta.
     */
    private function numeroLote(Product $producto): string
    {
        $prefijo = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $producto->item_code) ?: 'LOTE', 0, 4));
        $base = $prefijo.'-'.now()->format('ymd-His').'-'.str_pad((string) $producto->id, 2, '0', STR_PAD_LEFT);

        if (! ProductionOrder::where('batch_number', $base)->exists()) {
            return $base;
        }

        for ($correlativo = 2; $correlativo <= 99; $correlativo++) {
            $candidato = $base.'-'.$correlativo;

            if (! ProductionOrder::where('batch_number', $candidato)->exists()) {
                return $candidato;
            }
        }

        // Cien lotes del mismo producto en un segundo no pasa, pero antes que
        // reventar el guardado se cae a los microsegundos.
        return $base.'-'.now()->format('u');
    }

    private function numero(float $valor): string
    {
        return rtrim(rtrim(number_format($valor, 2, '.', ''), '0'), '.');
    }
}
