<?php

namespace App\Livewire\Panel\Recepcion;

use App\Exceptions\ReglaNegocioException;
use App\Models\Conciliacion;
use App\Models\RegistroAcopio;
use App\Models\RutaAcopio;
use App\Services\Acopio\ConciliacionService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Recepción del día: comparativa "Litros Acopiador vs. Caudalímetro".
 * El caudalímetro es editable; la diferencia y el % se calculan en vivo
 * (sólo display). La conciliación real la hace ConciliacionService.
 */
class RecepcionDia extends Component
{
    public ?string $rutaAcopioId = null;

    #[Validate('required|numeric|min:0')]
    public $litrosCaudalimetro = null;

    public ?array $ultimoResultado = null;

    /** Tolerancia volumétrica (en %). */
    #[Computed]
    public function tolerancia(): float
    {
        return (float) config('milkflow.conciliacion.tolerancia_pct');
    }

    #[Computed]
    public function litrosAcopiador(): float
    {
        if (! $this->rutaAcopioId) {
            return 0.0;
        }

        return (float) RegistroAcopio::query()
            ->where('ruta_acopio_id', $this->rutaAcopioId)
            ->where('deleted', false)
            ->sum('litros');
    }

    #[Computed]
    public function diferencia(): float
    {
        return round((float) ($this->litrosCaudalimetro ?: 0) - $this->litrosAcopiador(), 2);
    }

    #[Computed]
    public function porcentaje(): float
    {
        $base = $this->litrosAcopiador();

        return $base > 0 ? round(abs($this->diferencia()) / $base * 100, 3) : 0.0;
    }

    #[Computed]
    public function superaTolerancia(): bool
    {
        return $this->litrosAcopiador() > 0 && $this->porcentaje() > $this->tolerancia();
    }

    public function conciliar(ConciliacionService $servicio): void
    {
        $this->authorize('panel-jefatura-planta');
        $this->validate();

        $ruta = RutaAcopio::findOrFail($this->rutaAcopioId);

        try {
            $conciliacion = $servicio->conciliar(
                rutaAcopio: $ruta,
                litrosCaudalimetro: (float) $this->litrosCaudalimetro,
                registrador: auth()->user(),
            );
        } catch (ReglaNegocioException $e) {
            $this->addError('litrosCaudalimetro', $e->getMessage());

            return;
        }

        $this->ultimoResultado = [
            'ruta' => $ruta->ruta?->nombre,
            'acopiador' => $conciliacion->litros_acopiador,
            'caudalimetro' => $conciliacion->litros_caudalimetro,
            'diferencia' => $conciliacion->diferencia_litros,
            'porcentaje' => $conciliacion->diferencia_porcentaje,
            'alerta' => $conciliacion->tiene_alerta,
        ];
        $this->reset(['rutaAcopioId', 'litrosCaudalimetro']);
        session()->flash('ok', 'Conciliación registrada.');
    }

    public function render()
    {
        return view('livewire.panel.recepcion.recepcion-dia', [
            'pendientes' => RutaAcopio::query()
                ->with(['acopiador.usuario', 'ruta'])
                ->whereIn('estado', ['cerrada', 'descargada'])
                ->whereDoesntHave('conciliacion')
                ->orderBy('fecha')
                ->get(),
            'alertas' => Conciliacion::query()->conAlerta()->latest('conciliado_en')->take(10)->get(),
        ]);
    }
}
