<div>
    @if (! $asamblea)
        <div class="tarjeta">
            <h2>No hay asamblea en curso</h2>
            <form wire:submit="abrir" class="campo-fila">
                <div class="campo">
                    <label for="at">Título</label>
                    <input id="at" wire:model="nuevaTitulo">
                    @error('nuevaTitulo') <span class="error-campo">{{ $message }}</span> @enderror
                </div>
                <div class="campo">
                    <label for="af">Fecha</label>
                    <input id="af" type="date" wire:model="nuevaFecha">
                    @error('nuevaFecha') <span class="error-campo">{{ $message }}</span> @enderror
                </div>
                <div class="campo">
                    <label for="al">Lugar</label>
                    <input id="al" wire:model="nuevoLugar">
                </div>
                <div class="campo" style="display:flex;align-items:flex-end">
                    <button type="submit" class="principal">Abrir registro</button>
                </div>
            </form>
        </div>
    @else
        <div class="tarjeta">
            <h2>{{ $asamblea->titulo }}</h2>
            <div class="comparativa">
                <div class="celda">
                    <div class="valor">{{ $asamblea->asistentes }}</div>
                    <div class="rotulo">Presentes</div>
                </div>
                <div class="celda">
                    <div class="valor">{{ $asamblea->padron_snapshot }}</div>
                    <div class="rotulo">Padrón activo</div>
                </div>
                <div class="celda">
                    <div class="valor">{{ $asamblea->quorum_requerido }}</div>
                    <div class="rotulo">Quórum requerido (mitad + 1)</div>
                </div>
                <div class="celda {{ $asamblea->quorum_alcanzado ? 'bien' : 'mal' }}">
                    <div class="valor">{{ $asamblea->quorum_alcanzado ? 'SÍ' : 'NO' }}</div>
                    <div class="rotulo">Quórum alcanzado</div>
                </div>
            </div>
        </div>

        <div class="tarjeta">
            <h3>Buscar en el padrón (DNI o nombre, ignora acentos)</h3>
            <input type="text" wire:model.live="busqueda" placeholder="p. ej. 7000 · josé · quispe">

            @if ($resultados->isNotEmpty())
                <table>
                    <thead><tr><th>DNI</th><th>Nombre</th><th>Zona</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($resultados as $p)
                        <tr>
                            <td>{{ $p->dni }}</td>
                            <td>{{ $p->nombres }} {{ $p->apellidos }}</td>
                            <td class="tenue">{{ $p->zona?->nombre }}</td>
                            <td>
                                <button type="button" class="suave"
                                        wire:click="marcar('{{ $p->dni }}', '{{ addslashes($p->nombres.' '.$p->apellidos) }}')">
                                    Marcar presente
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @elseif (strlen(trim($busqueda)) >= 2)
                <p class="tenue">Sin coincidencias.</p>
            @endif
        </div>

        <div class="tarjeta">
            <h3>Asistentes registrados ({{ $asistencias->count() }})</h3>
            <table>
                <thead><tr><th>DNI</th><th>Nombre</th><th>Hora</th></tr></thead>
                <tbody>
                @forelse ($asistencias as $a)
                    <tr>
                        <td>{{ $a->dni }}</td>
                        <td>{{ $a->nombre_completo ?? '—' }}</td>
                        <td class="tenue">{{ $a->registrado_en?->format('H:i') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="tenue">Aún no hay asistentes.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
