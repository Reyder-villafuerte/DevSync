<?php

namespace App\Services\Acopio;

use App\Exceptions\ReglaNegocioException;
use App\Models\CollectionPriceRule;
use App\Models\Supply;
use App\Models\SystemPrice;
use Illuminate\Support\Collection;

/**
 * Lo que la planta le paga al productor por lo que entrega.
 *
 * El precio ya no sale de tres columnas con un `if` encima: sale de las reglas
 * que el administrador carga. Entre las que apliquen gana la de mayor
 * prioridad; si no aplica ninguna, la base.
 */
class TarifaAcopioService
{
    /** El insumo que hoy se acopia por excelencia. */
    public function leche(): ?Supply
    {
        return Supply::where('item_code', config('huata.codigos.leche'))->first();
    }

    /**
     * Resuelve qué se paga por unidad con las medidas de calidad del ciclo.
     *
     * @param  array<string, float|null>  $metricas  p. ej. ['water_addition_percentage' => 7.2]
     * @return array{rule: CollectionPriceRule|null, price: float, penalty_type: string, whole_cycle: bool, reason: string}
     */
    public function resolver(Supply $insumo, array $metricas = []): array
    {
        $reglas = $this->reglasDe($insumo);

        $ganadora = $reglas
            ->filter(fn (CollectionPriceRule $regla) => $regla->aplicaCon($metricas))
            ->sortByDesc('priority')
            ->first();

        if (! $ganadora) {
            return [
                'rule' => null,
                'price' => 0.0,
                'penalty_type' => 'ninguna',
                'whole_cycle' => false,
                'reason' => "No hay ninguna tarifa cargada para {$insumo->name}.",
            ];
        }

        return [
            'rule' => $ganadora,
            'price' => (float) $ganadora->price_per_unit,
            'penalty_type' => $ganadora->penalty_type ?: 'ninguna',
            'whole_cycle' => (bool) $ganadora->applies_to_whole_cycle,
            'reason' => $ganadora->motivo($metricas),
        ];
    }

    /**
     * La tarifa que se paga cuando nada la castiga.
     *
     * Si todavía no hay ninguna regla cargada para ese insumo se cae a la
     * columna heredada, para que una instalación a medio configurar no le
     * liquide cero al productor.
     */
    public function precioBase(Supply $insumo): float
    {
        $base = $this->reglasDe($insumo)->firstWhere(fn (CollectionPriceRule $r) => $r->esBase());

        return $base
            ? (float) $base->price_per_unit
            : (float) SystemPrice::current()->price_milk_base;
    }

    /** @return Collection<int, CollectionPriceRule> */
    public function reglasDe(Supply $insumo): Collection
    {
        return CollectionPriceRule::where('supply_id', $insumo->id)
            ->where('is_active', true)
            ->orderByDesc('priority')
            ->get();
    }

    // ----------------------------------------------------------------- CRUD

    /** @param  array<string, mixed>  $datos */
    public function crearRegla(Supply $insumo, array $datos): CollectionPriceRule
    {
        $normalizados = $this->normalizar($datos);

        if ($normalizados['metric'] === null && $this->tieneBase($insumo, null)) {
            throw new ReglaNegocioException(
                "«{$insumo->name}» ya tiene su tarifa base. Edítala en vez de crear otra.",
                'metric'
            );
        }

        return CollectionPriceRule::create($normalizados + [
            'supply_id' => $insumo->id,
            'is_active' => true,
        ]);
    }

    /** @param  array<string, mixed>  $datos */
    public function actualizarRegla(CollectionPriceRule $regla, array $datos): CollectionPriceRule
    {
        $normalizados = $this->normalizar($datos);

        if ($normalizados['metric'] === null && $this->tieneBase($regla->supply, $regla->id)) {
            throw new ReglaNegocioException(
                'Ya hay otra tarifa base para este producto.',
                'metric'
            );
        }

        $regla->update($normalizados + ['is_active' => (bool) ($datos['is_active'] ?? true)]);

        return $regla;
    }

    /** La base no se borra: sin ella no habría con qué pagar. */
    public function eliminarRegla(CollectionPriceRule $regla): void
    {
        if ($regla->esBase()) {
            throw new ReglaNegocioException(
                'La tarifa base no se elimina: es con la que se paga cuando nada la castiga.',
                'regla'
            );
        }

        $regla->delete();
    }

    /**
     * Copia las tarifas de la leche a `system_prices`.
     *
     * Esa tabla dejó de mandar, pero la app móvil la lee en su sincronización
     * y el historial de temporadas se arma con ella.
     */
    public function reflejarEnTarifasDelSistema(): void
    {
        $leche = $this->leche();

        if (! $leche) {
            return;
        }

        $reglas = $this->reglasDe($leche);
        $vigentes = SystemPrice::current();

        $vigentes->update(array_filter([
            'price_milk_base' => $this->precioBase($leche) ?: null,
            'price_milk_water_penalty_low' => $this->precioDe($reglas, 'leve_descuento'),
            'price_milk_water_penalty_high' => $this->precioDe($reglas, 'grave_expulsion'),
        ], fn ($valor) => $valor !== null));
    }

    /** @param  Collection<int, CollectionPriceRule>  $reglas */
    private function precioDe($reglas, string $penalidad): ?float
    {
        $regla = $reglas->firstWhere('penalty_type', $penalidad);

        return $regla ? (float) $regla->price_per_unit : null;
    }

    private function tieneBase(Supply $insumo, ?int $exceptoId): bool
    {
        return CollectionPriceRule::where('supply_id', $insumo->id)
            ->whereNull('metric')
            ->when($exceptoId, fn ($q) => $q->whereKeyNot($exceptoId))
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    private function normalizar(array $datos): array
    {
        $nombre = trim((string) ($datos['name'] ?? ''));

        if ($nombre === '') {
            throw new ReglaNegocioException('La tarifa necesita un nombre.', 'name');
        }

        $metrica = trim((string) ($datos['metric'] ?? '')) ?: null;
        $operador = trim((string) ($datos['operator'] ?? '')) ?: null;

        if ($metrica !== null) {
            if (! in_array($operador, CollectionPriceRule::OPERADORES, true)) {
                throw new ReglaNegocioException('La condición necesita un comparador válido.', 'operator');
            }

            if (! isset($datos['threshold']) || $datos['threshold'] === '') {
                throw new ReglaNegocioException('La condición necesita un valor contra el cual comparar.', 'threshold');
            }
        }

        if ((float) ($datos['price_per_unit'] ?? -1) < 0) {
            throw new ReglaNegocioException('La tarifa no puede ser negativa.', 'price_per_unit');
        }

        return [
            'name' => $nombre,
            'metric' => $metrica,
            'operator' => $metrica ? $operador : null,
            'threshold' => $metrica ? (float) $datos['threshold'] : null,
            'price_per_unit' => (float) ($datos['price_per_unit'] ?? 0),
            'applies_to_whole_cycle' => (bool) ($datos['applies_to_whole_cycle'] ?? false),
            'penalty_type' => trim((string) ($datos['penalty_type'] ?? '')) ?: null,
            'priority' => (int) ($datos['priority'] ?? 0),
        ];
    }
}
