<?php

namespace App\Services\Tarifas;

use App\Enums\TipoCliente;
use App\Exceptions\ReglaNegocioException;
use App\Models\PrecioCompraLeche;
use App\Models\PrecioVenta;
use App\Models\Producto;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Use Case: fijar tarifas sin destruir el histórico (requisito técnico 4).
 *
 * Al guardar un nuevo precio se CIERRA la vigencia anterior
 * (vigente_hasta = nueva_vigencia − 1 día) y se ABRE una nueva fila con
 * vigente_desde = hoy y vigente_hasta = null. Nunca se hace UPDATE del precio
 * histórico, salvo el caso de re-editar el precio del mismo día (se corrige en
 * sitio para no chocar con la restricción de exclusión de rangos de Postgres).
 *
 * Todo en transacción (restricción: escrituras que afectan precios).
 */
class TarifaService
{
    /** Precio de compra de leche por litro (RN-05: normal + tarifa mínima degradada). */
    public function fijarPrecioCompraLeche(float $precioLitro, float $precioMinimo, ?string $desde = null): PrecioCompraLeche
    {
        $desde = $desde ?: now()->toDateString();

        if ($precioLitro <= 0 || $precioMinimo <= 0) {
            throw new ReglaNegocioException('Los precios deben ser mayores a cero.', 'TARIFA_INVALIDA');
        }
        if ($precioMinimo > $precioLitro) {
            throw new ReglaNegocioException('La tarifa mínima no puede superar la tarifa normal.', 'TARIFA_MINIMA_INVALIDA');
        }

        return DB::transaction(function () use ($precioLitro, $precioMinimo, $desde) {
            $anterior = PrecioCompraLeche::query()
                ->whereNull('vigente_hasta')
                ->orderByDesc('vigente_desde')
                ->first();

            if ($anterior && $anterior->vigente_desde->toDateString() === $desde) {
                $anterior->forceFill([
                    'precio_litro' => $precioLitro,
                    'precio_litro_minimo' => $precioMinimo,
                ])->save();

                return $anterior;
            }

            if ($anterior) {
                $anterior->forceFill([
                    'vigente_hasta' => Carbon::parse($desde)->subDay()->toDateString(),
                ])->save();
            }

            return PrecioCompraLeche::create([
                'precio_litro' => $precioLitro,
                'precio_litro_minimo' => $precioMinimo,
                'vigente_desde' => $desde,
                'vigente_hasta' => null,
            ]);
        });
    }

    /** Precio de venta de un producto para un tipo de cliente. */
    public function fijarPrecioVenta(Producto $producto, TipoCliente $tipoCliente, float $precio, ?string $desde = null): PrecioVenta
    {
        $desde = $desde ?: now()->toDateString();

        if ($precio <= 0) {
            throw new ReglaNegocioException('El precio de venta debe ser mayor a cero.', 'TARIFA_INVALIDA');
        }

        return DB::transaction(function () use ($producto, $tipoCliente, $precio, $desde) {
            $anterior = PrecioVenta::query()
                ->where('producto_id', $producto->id)
                ->where('tipo_cliente', $tipoCliente->value)
                ->whereNull('vigente_hasta')
                ->orderByDesc('vigente_desde')
                ->first();

            if ($anterior && $anterior->vigente_desde->toDateString() === $desde) {
                $anterior->forceFill(['precio' => $precio])->save();

                return $anterior;
            }

            if ($anterior) {
                $anterior->forceFill([
                    'vigente_hasta' => Carbon::parse($desde)->subDay()->toDateString(),
                ])->save();
            }

            return PrecioVenta::create([
                'producto_id' => $producto->id,
                'tipo_cliente' => $tipoCliente->value,
                'precio' => $precio,
                'vigente_desde' => $desde,
                'vigente_hasta' => null,
            ]);
        });
    }
}
