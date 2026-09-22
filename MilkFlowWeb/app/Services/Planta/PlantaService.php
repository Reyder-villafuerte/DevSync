<?php

namespace App\Services\Planta;

use App\Exceptions\ReglaNegocioException;
use App\Models\CollectionRoute;
use App\Models\InventoryStock;
use App\Models\PlantReception;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Recepción de leche con caudalímetro.
 *
 * Regla clave: al stock de leche entra ÚNICAMENTE lo medido por el caudalímetro
 * en planta, nunca lo declarado por el acopiador en la ruta. Lo que se hace
 * después con esa leche —queso, yogur, lo que sea— vive en los lotes de
 * producción, que consumen según la receta de cada producto.
 */
class PlantaService
{
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
        if (! in_array($estado, ['verificado', 'incompleto', 'con_observacion'], true)) {
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
                    config('huata.codigos.leche'),
                    $deltaStock,
                    'Leche Fresca Verificada en Planta (Caudalímetro)',
                    'litros'
                );
            }

            return $recepcion;
        });
    }
}
