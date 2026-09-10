<?php

namespace App\Livewire\Panel\Admin;

use App\Exceptions\ReglaNegocioException;
use App\Models\Liquidacion;
use App\Models\SemanaPago;
use App\Services\Liquidacion\LiquidacionService;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Pestaña Liquidación del viernes: genera la liquidación del ciclo
 * jueves→miércoles y permite pagar el sobre y marcar su entrega física.
 * Todo el cálculo (tarifa congelada, sanciones) vive en LiquidacionService.
 */
class LiquidacionViernes extends Component
{
    #[Validate('required|date')]
    public $fechaReferencia;

    public function mount(): void
    {
        $this->fechaReferencia = now()->toDateString();
    }

    private function rango(): array
    {
        return SemanaPago::paraFecha(CarbonImmutable::parse($this->fechaReferencia));
    }

    public function generar(LiquidacionService $servicio): void
    {
        $this->authorize('generar', Liquidacion::class);
        $this->validate();

        $rango = $this->rango();
        $semana = SemanaPago::firstOrCreate(
            ['fecha_inicio' => $rango['fecha_inicio'], 'fecha_fin' => $rango['fecha_fin']],
            ['fecha_liquidacion' => $rango['fecha_liquidacion'], 'estado' => 'abierta'],
        );

        try {
            $liquidaciones = $servicio->generarSemana($semana);
        } catch (ReglaNegocioException $e) {
            $this->addError('fechaReferencia', $e->getMessage());

            return;
        }

        session()->flash('ok', $liquidaciones->count().' liquidaciones generadas; total neto S/ '
            .number_format((float) $liquidaciones->sum('monto_neto'), 2));
    }

    public function pagar(LiquidacionService $servicio, string $liquidacionId): void
    {
        $this->authorize('marcarEntregada', Liquidacion::class);
        try {
            $servicio->marcarPagada(Liquidacion::findOrFail($liquidacionId));
        } catch (ReglaNegocioException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function entregarSobre(LiquidacionService $servicio, string $liquidacionId): void
    {
        $this->authorize('marcarEntregada', Liquidacion::class);
        try {
            $servicio->marcarSobreEntregado(Liquidacion::findOrFail($liquidacionId));
        } catch (ReglaNegocioException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $rango = $this->rango();
        $semana = SemanaPago::query()
            ->where('fecha_inicio', $rango['fecha_inicio'])
            ->where('fecha_fin', $rango['fecha_fin'])
            ->with(['liquidaciones' => fn ($q) => $q->with(['productor', 'detalles'])->orderByDesc('monto_neto')])
            ->first();

        return view('livewire.panel.admin.liquidacion-viernes', [
            'rango' => $rango,
            'semana' => $semana,
            'totalCiclo' => $semana ? (float) $semana->liquidaciones->sum('monto_neto') : 0.0,
            'semanas' => SemanaPago::query()->withCount('liquidaciones')->latest('fecha_inicio')->take(8)->get(),
        ]);
    }
}
