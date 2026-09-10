<?php

namespace App\Http\Resources;

use App\Models\ControlCalidad;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Respuesta de POST /api/inspecciones: el control con su dictamen YA evaluado,
 * más la sanción o capacitación generada.
 *
 * @property ControlCalidad $resource
 */
class DictamenCalidadResource extends JsonResource
{
    public static $wrap = null;

    public function toArray($request): array
    {
        $c = $this->resource;

        return [
            'control' => [
                'id' => $c->id,
                'productorId' => $c->productor_id,
                'tomadoEn' => $c->tomado_en?->toISOString(),
                'aguaAnadidaPorcentaje' => $c->agua_anadida_porcentaje,
                'ph' => $c->ph,
                'dictamen' => $c->dictamen->value,
                'dictamenDetalle' => $c->dictamen_detalle,
                'rechazaLote' => $c->rechaza_lote,
                'esReincidencia' => $c->es_reincidencia,
                'version' => $c->version,
            ],
            'sancion' => $c->sancion ? [
                'id' => $c->sancion->id,
                'tipo' => $c->sancion->tipo->value,
                'porcentajeDescuento' => $c->sancion->porcentaje_descuento,
                'tarifaDegradadaLitro' => $c->sancion->tarifa_degradada_litro,
                'retiraDelPadron' => $c->sancion->retira_del_padron,
                'expulsa' => $c->sancion->expulsa,
            ] : null,
            'capacitacion' => $c->capacitacion ? [
                'id' => $c->capacitacion->id,
                'motivo' => $c->capacitacion->motivo,
                'estado' => $c->capacitacion->estado,
            ] : null,
        ];
    }
}
