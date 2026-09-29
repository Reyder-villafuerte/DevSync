<?php

namespace App\Services\Produccion;

use App\Exceptions\ReglaNegocioException;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Compras de insumos: lo que entra al almacén y lo que cuesta.
 *
 * Guardar una compra mueve el almacén en el acto, así que corregirla o
 * borrarla no es tocar una fila: es devolver al almacén lo que había entrado y
 * volver a aplicarlo. Si en el medio esa mercadería ya se consumió en
 * producción, la corrección se rechaza en vez de dejar el stock en negativo.
 *
 * El costo unitario del insumo deja de ser un número tipeado: se recalcula como
 * promedio ponderado de todas sus compras, así que edición y borrado lo dejan
 * siempre consistente sin arrastrar historia.
 */
class ComprasService
{
    public function __construct(private AlmacenService $almacen) {}

    // ---------------------------------------------------------------- compras

    /**
     * @param  array{supplier_id: int, purchase_date: string, document_number?: string|null, notes?: string|null, items: array<int, array{supply_id: int, quantity: float, unit_cost: float}>}  $datos
     */
    public function registrar(array $datos, ?User $usuario = null): Purchase
    {
        $renglones = $this->normalizarRenglones($datos['items'] ?? []);
        $proveedor = $this->proveedorDe($datos, $usuario);

        return DB::transaction(function () use ($datos, $renglones, $proveedor, $usuario) {
            $compra = Purchase::create([
                'supplier_id' => $proveedor->id,
                'purchase_date' => $datos['purchase_date'],
                'document_number' => $this->texto($datos['document_number'] ?? null),
                'notes' => $this->texto($datos['notes'] ?? null),
                'total_amount' => 0,
                'registered_by' => $usuario?->id,
            ]);

            $this->aplicar($compra, $renglones, $usuario);
            $this->recalcularCostos($this->insumosDe($renglones));

            return $compra->fresh('items');
        });
    }

    /**
     * Corrige una compra ya aplicada: devuelve lo viejo y aplica lo nuevo.
     *
     * @param  array{supplier_id: int, purchase_date: string, document_number?: string|null, notes?: string|null, items: array<int, array{supply_id: int, quantity: float, unit_cost: float}>}  $datos
     */
    public function actualizar(Purchase $compra, array $datos, ?User $usuario = null): Purchase
    {
        $renglones = $this->normalizarRenglones($datos['items'] ?? []);
        $proveedor = $this->proveedorDe($datos, $usuario);

        $compra->load('items.supply');
        $this->verificarQueSePuedaDevolver($compra);

        // Los insumos que salen de la compra también necesitan recosteo.
        $afectados = $this->insumosDe($renglones)
            ->merge($compra->items->pluck('supply_id'))
            ->unique();

        return DB::transaction(function () use ($compra, $datos, $renglones, $proveedor, $usuario, $afectados) {
            $this->devolver($compra, $usuario, "Corrección de la {$compra->label()}.");

            $compra->update([
                'supplier_id' => $proveedor->id,
                'purchase_date' => $datos['purchase_date'],
                'document_number' => $this->texto($datos['document_number'] ?? null),
                'notes' => $this->texto($datos['notes'] ?? null),
            ]);

            $this->aplicar($compra, $renglones, $usuario);
            $this->recalcularCostos($afectados);

            return $compra->fresh('items');
        });
    }

    /** Anula la compra devolviendo al almacén todo lo que había ingresado. */
    public function eliminar(Purchase $compra, ?User $usuario = null): void
    {
        $compra->load('items.supply');
        $this->verificarQueSePuedaDevolver($compra);

        $afectados = $compra->items->pluck('supply_id')->unique();

        DB::transaction(function () use ($compra, $usuario, $afectados) {
            $this->devolver($compra, $usuario, "Anulación de la {$compra->label()}.");

            // Los movimientos quedan: el kardex no borra historia, solo deja de
            // apuntar a una compra que ya no existe.
            $compra->delete();

            $this->recalcularCostos($afectados);
        });
    }

    // ----------------------------------------------------------- proveedores

    /**
     * Ficha a quién figura en la boleta.
     *
     * No es un proveedor de la asociación —esos son los productores de leche,
     * y sus beneficios salen de su rol en el padrón—. La ficha nace de la
     * compra y existe solo para que el historial pueda decir a quién se le
     * compró sin repetir el mismo nombre en cada boleta.
     *
     * @param  array{name: string, document?: string|null}  $datos
     */
    public function crearProveedor(array $datos, ?User $usuario = null): Supplier
    {
        $nombre = $this->nombreDeProveedor($datos);

        if (Supplier::where('name', $nombre)->exists()) {
            throw new ReglaNegocioException("Ya hay una compra a nombre de {$nombre}.", 'new_supplier_name');
        }

        return Supplier::create([
            'name' => $nombre,
            'document' => $this->texto($datos['document'] ?? null),
            'is_active' => true,
            'created_by' => $usuario?->id,
        ]);
    }

    // --------------------------------------------------------------- interno

    /**
     * Crea los renglones y hace entrar la mercadería al almacén.
     *
     * @param  array<int, array{supply: Supply, quantity: float, unit_cost: float}>  $renglones
     */
    private function aplicar(Purchase $compra, array $renglones, ?User $usuario): void
    {
        $total = 0.0;

        foreach ($renglones as $renglon) {
            $subtotal = round($renglon['quantity'] * $renglon['unit_cost'], 2);
            $total += $subtotal;

            $compra->items()->create([
                'supply_id' => $renglon['supply']->id,
                'quantity' => $renglon['quantity'],
                'unit_cost' => $renglon['unit_cost'],
                'subtotal' => $subtotal,
            ]);

            $this->almacen->mover(
                $renglon['supply'],
                $renglon['quantity'],
                'compra',
                null,
                $usuario,
                "Ingreso por {$compra->label()} de {$compra->supplier->name}.",
                $compra,
                $renglon['unit_cost']
            );
        }

        $compra->update(['total_amount' => round($total, 2)]);
    }

    /** Saca del almacén lo que esta compra había metido y borra sus renglones. */
    private function devolver(Purchase $compra, ?User $usuario, string $motivo): void
    {
        foreach ($compra->items as $item) {
            $this->almacen->mover(
                $item->supply,
                -(float) $item->quantity,
                'anulacion_compra',
                null,
                $usuario,
                $motivo,
                $compra,
                (float) $item->unit_cost
            );
        }

        $compra->items()->delete();
    }

    /**
     * Una compra solo se puede corregir o anular si su mercadería sigue en
     * almacén. Si ya se consumió, devolverla dejaría el stock en negativo.
     */
    private function verificarQueSePuedaDevolver(Purchase $compra): void
    {
        $porInsumo = $compra->items->groupBy('supply_id');

        foreach ($porInsumo as $items) {
            $insumo = $items->first()->supply;
            $aDevolver = (float) $items->sum(fn (PurchaseItem $item) => (float) $item->quantity);
            $disponible = $insumo->stock();

            if ($disponible + 0.0001 < $aDevolver) {
                $falta = $this->numero($aDevolver);
                $hay = $this->numero($disponible);

                throw new ReglaNegocioException(
                    "No se puede modificar esta compra: entraron {$falta} {$insumo->unit} de {$insumo->name} ".
                    "y en almacén solo quedan {$hay}. Esa mercadería ya se consumió.",
                    'items'
                );
            }
        }
    }

    /**
     * Costo promedio ponderado de cada insumo sobre todas sus compras.
     *
     * Se recalcula entero en vez de acumularse, para que editar o borrar una
     * compra no deje arrastrando un promedio viejo.
     *
     * @param  Collection<int, int>  $insumoIds
     */
    private function recalcularCostos($insumoIds): void
    {
        foreach ($insumoIds as $insumoId) {
            $insumo = Supply::find($insumoId);

            if (! $insumo) {
                continue;
            }

            $resumen = PurchaseItem::where('supply_id', $insumoId)
                ->selectRaw('SUM(quantity) as cantidad, SUM(quantity * unit_cost) as importe')
                ->first();

            $cantidad = (float) ($resumen->cantidad ?? 0);

            // Sin compras vivas se respeta el costo que la planta tenga cargado
            // a mano: es mejor un dato viejo que un cero que infla los márgenes.
            if ($cantidad <= 0) {
                continue;
            }

            $insumo->update(['unit_cost' => round((float) $resumen->importe / $cantidad, 2)]);
        }
    }

    /**
     * @param  array<int, array{supply_id?: mixed, quantity?: mixed, unit_cost?: mixed}>  $items
     * @return array<int, array{supply: Supply, quantity: float, unit_cost: float}>
     */
    private function normalizarRenglones(array $items): array
    {
        $renglones = [];

        foreach ($items as $item) {
            $insumoId = (int) ($item['supply_id'] ?? 0);

            if ($insumoId <= 0) {
                continue;
            }

            $cantidad = (float) ($item['quantity'] ?? 0);
            $costo = (float) ($item['unit_cost'] ?? 0);

            if ($cantidad <= 0) {
                throw new ReglaNegocioException('Cada renglón de la compra necesita una cantidad mayor que cero.', 'items');
            }

            if ($costo < 0) {
                throw new ReglaNegocioException('El costo unitario no puede ser negativo.', 'items');
            }

            $insumo = Supply::find($insumoId);

            if (! $insumo) {
                throw new ReglaNegocioException('Uno de los insumos de la compra ya no existe.', 'items');
            }

            // La leche no se compra por aquí: entra por el caudalímetro de
            // planta y se paga por liquidación. Registrarla también como compra
            // la sumaría dos veces al stock y se pagaría dos veces.
            if (! $insumo->seCompra()) {
                throw new ReglaNegocioException(
                    "«{$insumo->name}» no se compra por esta pantalla: entra por acopio y se paga en la liquidación semanal del productor.",
                    'items'
                );
            }

            $renglones[] = [
                'supply' => $insumo,
                'quantity' => $cantidad,
                'unit_cost' => $costo,
            ];
        }

        if (empty($renglones)) {
            throw new ReglaNegocioException('La compra necesita al menos un insumo.', 'items');
        }

        return $renglones;
    }

    /** @param  array<int, array{supply: Supply, quantity: float, unit_cost: float}>  $renglones */
    private function insumosDe(array $renglones): Collection
    {
        return collect($renglones)->map(fn (array $renglon) => $renglon['supply']->id)->unique();
    }

    /**
     * A quién se le compró.
     *
     * La compra se sube como quien copia una boleta: se escribe a quién figura
     * en ella. Antes de anotar a alguien nuevo se busca al que ya existe
     * —primero por documento, después por nombre— para no terminar con el
     * mismo nombre dos veces en el historial.
     *
     * @param  array<string, mixed>  $datos
     */
    private function proveedorDe(array $datos, ?User $usuario = null): Supplier
    {
        $nombre = trim((string) ($datos['new_supplier_name'] ?? ''));
        $documento = trim((string) ($datos['new_supplier_document'] ?? ''));

        if ($nombre === '' && $documento === '') {
            throw new ReglaNegocioException(
                'Indica a quién se le compró: escribe el nombre o razón social de la boleta.',
                'new_supplier_name'
            );
        }

        $existente = $this->proveedorParecido($nombre, $documento);

        if ($existente) {
            // La boleta puede traer el RUC que la ficha vieja no tenía.
            if (! $existente->document && $documento !== '') {
                $existente->document = $documento;
                $existente->save();
            }

            return $existente;
        }

        if ($nombre === '') {
            throw new ReglaNegocioException('La compra necesita el nombre de la boleta.', 'new_supplier_name');
        }

        return $this->crearProveedor(['name' => $nombre, 'document' => $documento], $usuario);
    }

    /** A quién ya se le compró antes: el documento manda sobre el nombre. */
    private function proveedorParecido(string $nombre, string $documento): ?Supplier
    {
        if ($documento !== '') {
            $porDocumento = Supplier::where('document', $documento)->first();

            if ($porDocumento) {
                return $porDocumento;
            }
        }

        if ($nombre === '') {
            return null;
        }

        return Supplier::whereRaw('LOWER(name) = ?', [mb_strtolower($nombre)])->first();
    }

    /** @param  array<string, mixed>  $datos */
    private function nombreDeProveedor(array $datos): string
    {
        $nombre = trim((string) ($datos['name'] ?? ''));

        if ($nombre === '') {
            throw new ReglaNegocioException('La compra necesita el nombre de la boleta.', 'new_supplier_name');
        }

        return $nombre;
    }

    private function texto(?string $valor): ?string
    {
        $valor = trim((string) $valor);

        return $valor === '' ? null : $valor;
    }

    private function numero(float $valor): string
    {
        return rtrim(rtrim(number_format($valor, 2, '.', ''), '0'), '.');
    }
}
