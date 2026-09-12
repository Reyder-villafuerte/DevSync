<?php

namespace App\Livewire\Panel\Recepcion;

use App\Enums\EstadoRecepcion;
use App\Exceptions\ReglaNegocioException;
use App\Models\RegistroAcopio;
use App\Models\RutaAcopio;
use App\Services\Acopio\VerificacionRecepcionService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Verificación de entregas del día: cuando el acopiador cierra la ruta, el jefe
 * de producción ve AQUÍ cada entrega declarada por productor y confirma cuántos
 * litros recibió. Si el medidor de tina da menos, la fila queda "Faltó X L".
 *
 * El total de la ruta vs. caudalímetro se sigue conciliando en RecepcionDia.
 */
class VerificacionEntregas extends Component
{
    public ?string $rutaAcopioId = null;

    /** Litros recibidos que teclea el jefe, indexado por id de entrega. */
    public array $recibidos = [];

    public function updatedRutaAcopioId(): void
    {
        $this->recibidos = [];
        unset($this->entregas, $this->resumen);
    }

    /** Rutas ya cerradas por el acopiador (con entregas registradas). */
    #[Computed]
    public function rutas(): Collection
    {
        return RutaAcopio::query()
            ->with(['acopiador.usuario', 'ruta'])
            ->withCount([
                'registros as entregas_count' => fn ($q) => $q->where('deleted', false),
                'registros as pendientes_count' => fn ($q) => $q->where('deleted', false)->pendientesDeRecepcion(),
            ])
            ->whereIn('estado', ['cerrada', 'conciliada'])
            ->whereHas('registros', fn ($q) => $q->where('deleted', false))
            ->orderByDesc('fecha')
            ->limit(40)
            ->get();
    }

    /** Entregas de la ruta seleccionada, con su estado de recepción. */
    #[Computed]
    public function entregas(): Collection
    {
        if (! $this->rutaAcopioId) {
            return collect();
        }

        return RegistroAcopio::query()
            ->with('productor')
            ->where('ruta_acopio_id', $this->rutaAcopioId)
            ->where('deleted', false)
            ->orderBy('hora_registro')
            ->get();
    }

    #[Computed]
    public function resumen(): array
    {
        $entregas = $this->entregas;

        return [
            'declarado' => (float) $entregas->sum('litros'),
            'recibido' => (float) $entregas->sum(fn ($e) => $e->estado_recepcion->verificado() ? (float) $e->litros_recibidos : 0),
            'faltante' => (float) $entregas->sum('litros_faltantes'),
            'pendientes' => $entregas->where('estado_recepcion', EstadoRecepcion::PENDIENTE)->count(),
            'total' => $entregas->count(),
        ];
    }

    public function marcarConforme(string $registroId, VerificacionRecepcionService $servicio): void
    {
        $this->verificar($registroId, null, $servicio);
    }

    public function guardarRecibido(string $registroId, VerificacionRecepcionService $servicio): void
    {
        $valor = $this->recibidos[$registroId] ?? null;

        if ($valor === null || $valor === '' || ! is_numeric($valor) || (float) $valor < 0) {
            $this->addError("recibidos.$registroId", 'Ingrese los litros recibidos (número ≥ 0).');

            return;
        }

        $this->verificar($registroId, (float) $valor, $servicio);
    }

    public function marcarTodoConforme(VerificacionRecepcionService $servicio): void
    {
        $this->authorize('panel-jefatura-planta');

        if (! $this->rutaAcopioId) {
            return;
        }

        $ruta = RutaAcopio::findOrFail($this->rutaAcopioId);
        $n = $servicio->confirmarLoteConforme($ruta, auth()->user());

        unset($this->entregas, $this->resumen, $this->rutas);
        session()->flash('ok', "Se marcaron {$n} entrega(s) como conformes.");
    }

    private function verificar(string $registroId, ?float $litros, VerificacionRecepcionService $servicio): void
    {
        $this->authorize('panel-jefatura-planta');
        $this->resetErrorBag("recibidos.$registroId");

        $registro = RegistroAcopio::where('ruta_acopio_id', $this->rutaAcopioId)->findOrFail($registroId);

        try {
            $servicio->confirmar($registro, $litros, auth()->user());
        } catch (ReglaNegocioException $e) {
            $this->addError("recibidos.$registroId", $e->getMessage());

            return;
        }

        unset($this->recibidos[$registroId]);
        unset($this->entregas, $this->resumen, $this->rutas);
    }

    public function render()
    {
        return view('livewire.panel.recepcion.verificacion-entregas');
    }
}
