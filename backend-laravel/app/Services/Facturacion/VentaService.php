<?php

namespace App\Services\Facturacion;

use App\Enums\TipoMovimientoStock;
use App\Exceptions\ReglaNegocioException;
use App\Models\Cliente;
use App\Models\Correlativo;
use App\Models\DetalleVenta;
use App\Models\Dispositivo;
use App\Models\PrecioVenta;
use App\Models\Producto;
use App\Models\RangoCorrelativo;
use App\Models\Usuario;
use App\Models\Venta;
use App\Services\Stock\StockService;
use Illuminate\Support\Facades\DB;

/**
 * Use Case: registrar una venta de mostrador desde el panel web de Despacho.
 *
 * El componente Livewire solo captura cliente + producto + cantidad; aquí vive
 * TODA la lógica (restricción del enunciado):
 *  - precio unitario tomado de la tarifa de venta VIGENTE para el tipo de cliente,
 *  - validación de stock ANTES de tocar la BD (si no alcanza, se aborta con el
 *    disponible real; nunca se falla en silencio),
 *  - correlativo asignado por CorrelativoService sobre el rango del dispositivo
 *    "Mostrador Web" (mismo motor que el canal offline, requisito técnico 6),
 *  - Venta + DetalleVenta + egreso de stock, todo en una transacción.
 */
class VentaService
{
    /** Identificador del dispositivo lógico del punto de venta web (lo siembra MostradorWebSeeder). */
    public const DISPOSITIVO_MOSTRADOR = 'mostrador-web';

    private const SERIE = 'B001';

    private const TIPO_COMPROBANTE = 'boleta';

    public function __construct(
        private readonly StockService $stock,
        private readonly CorrelativoService $correlativos,
    ) {}

    public function registrar(Cliente $cliente, Producto $producto, float $cantidad, Usuario $usuario): Venta
    {
        if ($cantidad <= 0) {
            throw new ReglaNegocioException('La cantidad debe ser mayor a cero.', 'VENTA_CANTIDAD_INVALIDA');
        }

        $hoy = now()->toDateString();
        $precio = $this->tarifaVigente($producto, $cliente, $hoy);

        // Comprobación de stock fuera de la transacción (patrón de StockService).
        $this->stock->asegurarDisponibilidad($producto, $cantidad);

        return DB::transaction(function () use ($cliente, $producto, $cantidad, $usuario, $precio, $hoy) {
            $rango = $this->rangoConCupo();
            $numero = $this->correlativos->emitirNumero($rango);

            $subtotal = round($cantidad * (float) $precio->precio, 2);

            $venta = Venta::create([
                'cliente_id' => $cliente->id,
                'registrado_por' => $usuario->id,
                'rango_correlativo_id' => $rango->id,
                'tipo_comprobante' => self::TIPO_COMPROBANTE,
                'serie_comprobante' => self::SERIE,
                'numero_comprobante' => $numero,
                'fecha' => $hoy,
                'total' => $subtotal,
                'estado' => 'emitida',
            ]);

            DetalleVenta::create([
                'venta_id' => $venta->id,
                'producto_id' => $producto->id,
                'precio_venta_id' => $precio->id,
                'cantidad' => $cantidad,
                'precio_unitario' => $precio->precio,
                'subtotal' => $subtotal,
            ]);

            $this->stock->registrarMovimiento(
                producto: $producto,
                tipo: TipoMovimientoStock::VENTA_EGRESO,
                cantidadAbsoluta: $cantidad,
                origen: $venta,
                usuarioId: $usuario->id,
                motivo: "Venta {$venta->comprobante()}",
            );

            return $venta->fresh(['detalles', 'cliente']);
        });
    }

    /**
     * Tarifa de venta vigente hoy para el producto y el tipo de cliente.
     * Es un cálculo de sólo lectura que el componente también usa para mostrar
     * el precio en vivo; la fuente de verdad al confirmar es este método.
     */
    public function tarifaVigente(Producto $producto, Cliente $cliente, ?string $fecha = null): PrecioVenta
    {
        $fecha ??= now()->toDateString();

        $precio = PrecioVenta::query()
            ->where('producto_id', $producto->id)
            ->where('tipo_cliente', $cliente->tipo_cliente->value)
            ->vigenteEn($fecha)
            ->orderByDesc('vigente_desde')
            ->first();

        if (! $precio) {
            throw new ReglaNegocioException(
                "No hay tarifa de venta vigente para {$producto->nombre} y clientes tipo {$cliente->tipo_cliente->value}.",
                'SIN_TARIFA_VIGENTE',
                ['productoId' => $producto->id, 'tipoCliente' => $cliente->tipo_cliente->value],
            );
        }

        return $precio;
    }

    /** Rango de correlativo con cupo para el mostrador; reserva uno nuevo si hace falta. */
    private function rangoConCupo(): RangoCorrelativo
    {
        $correlativo = Correlativo::firstOrCreate(
            ['tipo_comprobante' => self::TIPO_COMPROBANTE, 'serie' => self::SERIE],
            ['numero_maximo_asignado' => 0, 'tamano_bloque_default' => 50],
        );
        $dispositivo = $this->dispositivoMostrador();

        $rango = RangoCorrelativo::query()
            ->where('dispositivo_id', $dispositivo->id)
            ->where('correlativo_id', $correlativo->id)
            ->where('agotado', false)
            ->whereColumn('numero_siguiente', '<=', 'numero_hasta')
            ->orderBy('reservado_en')
            ->first();

        return $rango ?? $this->correlativos->reservarRango($correlativo, $dispositivo);
    }

    private function dispositivoMostrador(): Dispositivo
    {
        $dispositivo = Dispositivo::query()->where('identificador', self::DISPOSITIVO_MOSTRADOR)->first();

        if (! $dispositivo) {
            throw new ReglaNegocioException(
                'No está configurado el dispositivo de mostrador web; ejecute «php artisan db:seed --class=MostradorWebSeeder».',
                'MOSTRADOR_NO_CONFIGURADO',
            );
        }

        return $dispositivo;
    }
}
