<?php

namespace App\Services\Planta;

use App\Exceptions\ReglaNegocioException;
use App\Models\CheeseProduction;
use App\Models\CollectionRoute;
use App\Models\InventoryStock;
use App\Models\PlantReception;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Recepción con caudalímetro y elaboración de queso.
 *
 * Regla clave: al stock de leche entra ÚNICAMENTE lo medido por el caudalímetro
 * en planta, nunca lo declarado por el acopiador en la ruta.
 */
class PlantaService
{
    public const LITROS_POR_MOLDE = 10;

    /**
     * El jefe de producción contrasta lo declarado por el acopiador contra el
     * caudalímetro. Si corrige una medición previa, el stock recibe solo el delta.
     */
    public function verificarRecepcion(
        CollectionRoute $ruta,
        User $verificador,
        float $litrosCaudalimetro,
        string $estado,
        ?string $observacion = null,
        ?string $clientUuid = null
    ): PlantReception {
        if (!in_array($estado, ['verificado', 'incompleto', 'con_observacion'], true)) {
            throw new ReglaNegocioException("Estado de verificación no válido: {$estado}.", 'verification_status');
        }

        return DB::transaction(function () use ($ruta, $verificador, $litrosCaudalimetro, $estado, $observacion, $clientUuid) {
            $litrosDeclarados = (float) $ruta->total_collected_liters;

            $recepcionPrevia = PlantReception::where('collection_route_id', $ruta->id)->first();
            $caudalimetroPrevio = $recepcionPrevia ? (float) $recepcionPrevia->flowmeter_liters : 0.0;

            $recepcion = PlantReception::updateOrCreate(
                ['collection_route_id' => $ruta->id],
                [
                    'client_uuid' => $clientUuid,
                    'verifier_id' => $verificador->id,
                    'collector_declared_liters' => $litrosDeclarados,
                    'flowmeter_liters' => $litrosCaudalimetro,
                    'difference_liters' => $litrosCaudalimetro - $litrosDeclarados,
                    'verification_status' => $estado,
                    'observation' => $observacion,
                    'verified_at' => now(),
                ]
            );

            $ruta->status = 'verificada';
            $ruta->save();

            $deltaStock = $litrosCaudalimetro - $caudalimetroPrevio;
            if (abs($deltaStock) > 0.0001) {
                InventoryStock::adjustStock(
                    'MILK_RAW_LITERS',
                    $deltaStock,
                    'Leche Fresca Verificada en Planta (Caudalímetro)',
                    'litros'
                );
            }

            return $recepcion;
        });
    }

    /** Moldes que alcanzan a producirse con el stock de leche disponible. */
    public function moldesPosibles(): int
    {
        return (int) floor(InventoryStock::getStock('MILK_RAW_LITERS') / self::LITROS_POR_MOLDE);
    }

    /** Registra una producción de queso: 1 molde consume 10 L de leche. */
    public function producirQueso(
        User $supervisor,
        int $moldes,
        ?string $numeroLote = null,
        ?string $fecha = null,
        ?string $clientUuid = null
    ): CheeseProduction {
        if ($moldes < 1) {
            throw new ReglaNegocioException('La cantidad de moldes debe ser al menos 1.', 'cheese_molds_produced');
        }

        $lecheRequerida = $moldes * self::LITROS_POR_MOLDE;
        $stockLeche = InventoryStock::getStock('MILK_RAW_LITERS');

        if ($stockLeche < $lecheRequerida) {
            throw new ReglaNegocioException(
                "Stock de leche insuficiente. Se requieren {$lecheRequerida} L y hay disponibles {$stockLeche} L.",
                'cheese_molds_produced'
            );
        }

        return DB::transaction(function () use ($supervisor, $moldes, $lecheRequerida, $numeroLote, $fecha, $clientUuid) {
            $produccion = CheeseProduction::create([
                'client_uuid' => $clientUuid,
                'production_date' => $fecha ?: date('Y-m-d'),
                'supervisor_id' => $supervisor->id,
                'cheese_molds_produced' => $moldes,
                'milk_liters_used' => $lecheRequerida,
                'batch_number' => $numeroLote ?: 'LOTE-' . date('ymd-His'),
                'status' => 'completado',
            ]);

            InventoryStock::adjustStock('MILK_RAW_LITERS', -$lecheRequerida);
            InventoryStock::adjustStock('CHEESE_MOLD_UNITS', $moldes, 'Moldes de Queso Madurado Huata', 'moldes');

            return $produccion;
        });
    }
}
