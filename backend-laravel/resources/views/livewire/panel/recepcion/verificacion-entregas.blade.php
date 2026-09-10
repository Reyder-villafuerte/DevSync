@php use App\Enums\EstadoRecepcion; @endphp
<div class="tarjeta">
    <h2>Verificación de entregas — ¿llegó lo que el acopiador declaró?</h2>

    @if (session('ok'))
        <p class="flash ok">{{ session('ok') }}</p>
    @endif

    <div class="campo">
        <label for="ve-ruta">Ruta cerrada por el acopiador</label>
        <select id="ve-ruta" wire:model.live="rutaAcopioId">
            <option value="">— seleccione —</option>
            @foreach ($this->rutas as $ra)
                <option value="{{ $ra->id }}">
                    {{ $ra->fecha->format('d/m/Y') }} · {{ $ra->ruta?->nombre }} ·
                    {{ $ra->acopiador?->usuario?->nombres ?? 'N/D' }} ·
                    {{ $ra->entregas_count }} entregas
                    @if ($ra->pendientes_count) ({{ $ra->pendientes_count }} sin verificar) @endif
                </option>
            @endforeach
        </select>
    </div>

    @if ($rutaAcopioId)
        @php $r = $this->resumen; @endphp
        <div class="comparativa">
            <div class="celda">
                <div class="valor">{{ number_format($r['declarado'], 2) }}</div>
                <div class="rotulo">Declarado por el acopiador (L)</div>
            </div>
            <div class="celda {{ $r['pendientes'] ? '' : 'bien' }}">
                <div class="valor">{{ number_format($r['recibido'], 2) }}</div>
                <div class="rotulo">Recibido y verificado (L)</div>
            </div>
            <div class="celda {{ $r['faltante'] > 0 ? 'mal' : ($r['pendientes'] ? '' : 'bien') }}">
                <div class="valor">{{ number_format($r['faltante'], 2) }}</div>
                <div class="rotulo">Faltante total (L)</div>
            </div>
            <div class="celda {{ $r['pendientes'] ? '' : 'bien' }}">
                <div class="valor">{{ $r['total'] - $r['pendientes'] }}/{{ $r['total'] }}</div>
                <div class="rotulo">Entregas verificadas</div>
            </div>
        </div>

        @if ($r['pendientes'] > 0)
            <button type="button" class="suave" wire:click="marcarTodoConforme" wire:loading.attr="disabled"
                wire:confirm="Marcar como CONFORME las {{ $r['pendientes'] }} entrega(s) que faltan verificar?">
                Marcar todo conforme ({{ $r['pendientes'] }})
            </button>
        @endif

        <table>
            <thead>
                <tr>
                    <th>Productor</th>
                    <th class="num">Declarado (L)</th>
                    <th class="num">Recibido (L)</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($this->entregas as $e)
                <tr wire:key="ent-{{ $e->id }}">
                    <td>
                        {{ $e->productor?->nombres }} {{ $e->productor?->apellidos }}
                        @if ($e->sospecha_adulteracion)
                            <span class="chip naranja">sospecha adulteración</span>
                        @endif
                    </td>
                    <td class="num">{{ number_format($e->litros, 2) }}</td>
                    <td class="num">
                        @if ($e->estado_recepcion->verificado())
                            {{ number_format($e->litros_recibidos, 2) }}
                        @else
                            <input type="number" step="0.01" min="0" style="width:6.5rem"
                                placeholder="{{ number_format($e->litros, 2) }}"
                                wire:model="recibidos.{{ $e->id }}">
                        @endif
                        @error('recibidos.'.$e->id) <div class="error-campo">{{ $message }}</div> @enderror
                    </td>
                    <td>
                        @switch($e->estado_recepcion)
                            @case(EstadoRecepcion::CONFORME)
                                <span class="chip verde">Conforme</span>
                                @break
                            @case(EstadoRecepcion::FALTANTE)
                                <span class="chip rojo">Faltó {{ number_format($e->litros_faltantes, 2) }} L</span>
                                @break
                            @case(EstadoRecepcion::EXCEDENTE)
                                <span class="chip naranja">Excedente +{{ number_format($e->litros_recibidos - $e->litros, 2) }} L</span>
                                @break
                            @default
                                <span class="chip gris">Pendiente</span>
                        @endswitch
                    </td>
                    <td>
                        <button type="button" class="suave" wire:click="marcarConforme('{{ $e->id }}')" wire:loading.attr="disabled">
                            Conforme
                        </button>
                        <button type="button" wire:click="guardarRecibido('{{ $e->id }}')" wire:loading.attr="disabled">
                            Registrar recibido
                        </button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="tenue">La ruta no tiene entregas registradas.</td></tr>
            @endforelse
            </tbody>
        </table>
    @endif
</div>
