<?php

namespace App\Services\Ventas;

use App\Exceptions\ReglaNegocioException;
use App\Models\ClientType;
use App\Models\Customer;
use App\Models\DailyCashClosure;
use App\Models\InventoryStock;
use App\Models\ProducerDeduction;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Services\Acopio\JornadaOperativa;
use Illuminate\Support\Facades\DB;

/**
 * Venta de mostrador y arqueo/cierre de caja.
 *
 * Una venta lleva renglones: se puede despachar queso, yogurt o lo que la
 * planta produzca, cada uno con la tarifa que le corresponde a ese cliente. La
 * tarifa sale del propio producto, no de una constante.
 *
 * Dos formas de pago: efectivo (dinero físico) y descuento_leche (a cuenta de
 * la leche del proveedor, que genera una deducción en su liquidación semanal).
 */
class VentaService
{
    /**
     * Registra una venta, descuenta el stock de cada producto y, si se paga a
     * cuenta de leche, deja la deducción pendiente del proveedor.
     *
     * Acepta el formato nuevo (`items`) y el viejo (`cheese_molds_quantity`),
     * que es el que siguen mandando las apps móviles ya instaladas.
     */
    public function registrarVenta(
        User $vendedor,
        array $datos,
        ?string $clientUuid = null
    ): Sale {
        $pedido = $this->normalizarPedido($datos);
        $this->verificarStock($pedido);

        return DB::transaction(function () use ($vendedor, $datos, $pedido, $clientUuid) {
            $cliente = $this->resolverCliente($datos, $this->unidadesDe($pedido));

            $renglones = $this->tarifar($pedido, $cliente);
            $total = round(array_sum(array_column($renglones, 'subtotal')), 2);
            $unidades = $this->unidadesDe($pedido);
            $formaPago = $datos['payment_method'] ?? 'efectivo';

            $venta = Sale::create([
                'client_uuid' => $clientUuid,
                'receipt_number' => Sale::generateReceiptNumber(),
                'customer_id' => $cliente->id,
                'seller_id' => $vendedor->id,
                // Espejo heredado que el móvil y el arqueo siguen leyendo.
                'cheese_molds_quantity' => (int) round($unidades),
                // Con un solo renglón es su tarifa; con varios, el promedio por
                // unidad. NUNCA null: la app móvil ya instalada lo deserializa
                // como Double no nulo y descartaría la venta en silencio.
                'unit_price' => count($renglones) === 1
                    ? $renglones[0]['unit_price']
                    : round($total / max($unidades, 0.0001), 2),
                'total_amount' => $total,
                'payment_method' => $formaPago,
                'sold_at' => $datos['sold_at'] ?? now(),
            ]);

            foreach ($renglones as $renglon) {
                $venta->items()->create([
                    'product_id' => $renglon['product']->id,
                    'quantity' => $renglon['quantity'],
                    'unit_price' => $renglon['unit_price'],
                    'subtotal' => $renglon['subtotal'],
                ]);

                InventoryStock::adjustStock($renglon['product']->item_code, -$renglon['quantity'])
                    ->vincularProducto($renglon['product']);
            }

            $venta->load('items.product');

            if ($formaPago === 'descuento_leche' && $cliente->linked_user_id) {
                ProducerDeduction::create([
                    'producer_id' => $cliente->linked_user_id,
                    'sale_id' => $venta->id,
                    'date' => now()->format('Y-m-d'),
                    'concept' => "Compra de {$venta->resumenItems()} a cuenta de leche (Recibo #{$venta->receipt_number})",
                    'amount' => $total,
                    'status' => 'pendiente',
                    'created_by' => $vendedor->id,
                    'notes' => 'Mercadería despachada en planta a descontar en la liquidación semanal de leche.',
                ]);
            }

            return $venta;
        });
    }

    /**
     * Arma los renglones del pedido a partir de lo que llegó.
     *
     * @return array<int, array{product: Product, quantity: float}>
     */
    private function normalizarPedido(array $datos): array
    {
        $crudos = $datos['items'] ?? null;

        // Formato viejo: una cantidad suelta que siempre significó queso.
        if (empty($crudos) && isset($datos['cheese_molds_quantity'])) {
            $crudos = [[
                'product_id' => $this->quesoDelCatalogo()->id,
                'quantity' => $datos['cheese_molds_quantity'],
            ]];
        }

        if (empty($crudos) || ! is_array($crudos)) {
            throw new ReglaNegocioException('La venta necesita al menos un producto.', 'items');
        }

        $pedido = [];

        foreach ($crudos as $crudo) {
            $productoId = (int) ($crudo['product_id'] ?? 0);
            $cantidad = (float) ($crudo['quantity'] ?? 0);

            if ($productoId <= 0) {
                continue;
            }

            if ($cantidad <= 0) {
                throw new ReglaNegocioException('Cada renglón de la venta necesita una cantidad mayor que cero.', 'items');
            }

            $producto = Product::find($productoId);

            if (! $producto) {
                throw new ReglaNegocioException('Uno de los productos de la venta ya no existe.', 'items');
            }

            if (! $producto->is_active) {
                throw new ReglaNegocioException("El producto {$producto->name} está desactivado.", 'items');
            }

            $pedido[] = ['product' => $producto, 'quantity' => $cantidad];
        }

        if (empty($pedido)) {
            throw new ReglaNegocioException('La venta necesita al menos un producto.', 'items');
        }

        return $pedido;
    }

    /**
     * No se despacha lo que no hay. Se valida por producto y sumando los
     * renglones repetidos, para que dos líneas del mismo queso no pasen.
     *
     * @param  array<int, array{product: Product, quantity: float}>  $pedido
     */
    private function verificarStock(array $pedido): void
    {
        $porProducto = [];

        foreach ($pedido as $renglon) {
            $codigo = $renglon['product']->item_code;
            $porProducto[$codigo] ??= ['product' => $renglon['product'], 'quantity' => 0.0];
            $porProducto[$codigo]['quantity'] += $renglon['quantity'];
        }

        foreach ($porProducto as $codigo => $pedida) {
            $disponible = InventoryStock::getStock($codigo);

            if ($disponible + 0.0001 < $pedida['quantity']) {
                $producto = $pedida['product'];
                $hay = $this->numero($disponible);

                throw new ReglaNegocioException(
                    "Stock insuficiente de {$producto->name}. Disponibles: {$hay} {$producto->unit}.",
                    'items'
                );
            }
        }
    }

    /**
     * Le pone precio a cada renglón según el cliente.
     *
     * La escalera de mayorista se mira por renglón: diez moldes de queso son
     * compra grande de queso, no diez unidades sueltas de cosas distintas.
     *
     * @param  array<int, array{product: Product, quantity: float}>  $pedido
     * @return array<int, array{product: Product, quantity: float, unit_price: float, subtotal: float}>
     */
    private function tarifar(array $pedido, Customer $cliente): array
    {
        return array_map(function (array $renglon) use ($cliente) {
            $producto = $renglon['product'];
            $precio = $producto->tarifaPara($cliente, (int) ceil($renglon['quantity']));

            if ($precio === null) {
                throw new ReglaNegocioException(
                    "«{$producto->name}» no tiene tarifa cargada para este cliente. ".
                    'Ponle su precio en Productos y Recetas antes de venderlo.',
                    'items'
                );
            }

            return [
                'product' => $renglon['product'],
                'quantity' => $renglon['quantity'],
                'unit_price' => $precio,
                'subtotal' => round($precio * $renglon['quantity'], 2),
            ];
        }, $pedido);
    }

    /** @param  array<int, array{product: Product, quantity: float}>  $pedido */
    private function unidadesDe(array $pedido): float
    {
        return (float) array_sum(array_column($pedido, 'quantity'));
    }

    /** El queso del catálogo, que es lo que significan los pedidos viejos. */
    private function quesoDelCatalogo(): Product
    {
        $codigo = config('huata.codigos.queso');
        $queso = Product::where('item_code', $codigo)->first();

        if (! $queso) {
            throw new ReglaNegocioException(
                "El catálogo no tiene el producto {$codigo}: indica qué se está vendiendo.",
                'items'
            );
        }

        return $queso;
    }

    /** Cliente ya registrado o alta rápida en el mostrador. */
    private function resolverCliente(array $datos, float $unidades): Customer
    {
        if (! empty($datos['customer_id'])) {
            $cliente = Customer::find($datos['customer_id']);
            if ($cliente) {
                return $cliente;
            }
        }

        if (! empty($datos['customer_client_uuid'])) {
            $cliente = Customer::where('client_uuid', $datos['customer_client_uuid'])->first();
            if ($cliente) {
                return $cliente;
            }
        }

        if (empty($datos['new_last_name'])) {
            throw new ReglaNegocioException('Debe elegir un cliente o registrar uno nuevo.', 'customer_id');
        }

        // Antes de dar de alta a alguien se busca si ya está registrado: con su
        // DNI, o con su nombre completo. Así el que vuelve al mostrador conserva
        // su tarifa en vez de quedar duplicado como cliente nuevo.
        $conocido = $this->clienteYaRegistrado($datos);

        if ($conocido) {
            return $conocido;
        }

        $tipo = $datos['new_type'] ?? null;
        $tipoFinal = $tipo ?: ($unidades >= 10 ? 'mayorista' : 'local');

        return Customer::create([
            'client_uuid' => $datos['customer_client_uuid'] ?? null,
            'first_name' => $datos['new_first_name'] ?? '',
            'last_name' => $datos['new_last_name'],
            'dni_ruc' => $datos['new_dni_ruc'] ?? null,
            'phone' => $datos['new_phone'] ?? null,
            'type' => $tipoFinal,
            'client_type_id' => ClientType::where('slug', $tipoFinal)->value('id'),
            'is_wholesale_approved' => $tipo === 'mayorista',
        ]);
    }

    /**
     * El cliente que ya está en el padrón de compradores.
     *
     * El DNI manda; si no vino, se compara el nombre completo sin distinguir
     * mayúsculas ni espacios de más.
     *
     * @param  array<string, mixed>  $datos
     */
    private function clienteYaRegistrado(array $datos): ?Customer
    {
        $documento = trim((string) ($datos['new_dni_ruc'] ?? ''));

        if ($documento !== '') {
            $porDocumento = Customer::where('dni_ruc', $documento)->first();

            if ($porDocumento) {
                return $porDocumento;
            }
        }

        $apellidos = $this->normalizar($datos['new_last_name'] ?? '');
        $nombres = $this->normalizar($datos['new_first_name'] ?? '');

        if ($apellidos === '') {
            return null;
        }

        return Customer::whereRaw('LOWER(TRIM(last_name)) = ?', [$apellidos])
            ->whereRaw('LOWER(TRIM(first_name)) = ?', [$nombres])
            ->first();
    }

    private function normalizar(?string $valor): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $valor)));
    }

    /**
     * Cierre de caja del día: concilia todas las ventas sin cierre bajo un
     * arqueo y deja la bandeja de "ventas de hoy" en blanco.
     */
    public function cerrarCaja(User $cajero, ?string $notas = null, ?string $fecha = null, ?string $clientUuid = null): DailyCashClosure
    {
        $fecha = $fecha ?: app(JornadaOperativa::class)->fecha();

        $pendientes = Sale::deLaJornada($fecha)->whereNull('closure_id')->get();

        if ($pendientes->isEmpty()) {
            throw new ReglaNegocioException(
                'No hay ventas activas pendientes de cierre para el día de hoy.',
                'cierre'
            );
        }

        return DB::transaction(function () use ($cajero, $notas, $fecha, $clientUuid, $pendientes) {
            $cierre = DailyCashClosure::create([
                'client_uuid' => $clientUuid,
                'date' => $fecha,
                'closed_by' => $cajero->id,
                'total_cash' => $pendientes->where('payment_method', 'efectivo')->sum('total_amount'),
                'total_milk_discount' => $pendientes->where('payment_method', 'descuento_leche')->sum('total_amount'),
                'total_amount' => $pendientes->sum('total_amount'),
                'cheese_molds_quantity' => $pendientes->sum('cheese_molds_quantity'),
                'sales_count' => $pendientes->count(),
                'notes' => $notas ?: 'Cierre de caja y arqueo conforme.',
                'closed_at' => now(),
            ]);

            Sale::deLaJornada($fecha)
                ->whereNull('closure_id')
                ->update(['closure_id' => $cierre->id]);

            return $cierre;
        });
    }

    private function numero(float $valor): string
    {
        return rtrim(rtrim(number_format($valor, 2, '.', ''), '0'), '.');
    }
}
