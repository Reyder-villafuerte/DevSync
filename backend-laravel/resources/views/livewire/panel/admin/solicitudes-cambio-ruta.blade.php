<div>
    <div class="tarjeta">
        <h2>Solicitudes pendientes ({{ $pendientes->count() }})</h2>
        <table>
            <thead><tr><th>Productor</th><th>Zona actual</th><th>Zona solicitada</th><th>Motivo</th><th>Resolución</th></tr></thead>
            <tbody>
            @forelse ($pendientes as $s)
                <tr>
                    <td>{{ $s->productor?->nombres }} {{ $s->productor?->apellidos }}</td>
                    <td>{{ $s->zonaActual?->nombre }} <span class="tenue">({{ $s->zonaActual?->ruta?->nombre }})</span></td>
                    <td>{{ $s->zonaSolicitada?->nombre }} <span class="tenue">({{ $s->zonaSolicitada?->ruta?->nombre }})</span></td>
                    <td>{{ $s->motivo }}</td>
                    <td>
                        <input type="text" placeholder="comentario (opcional)" wire:model="comentario.{{ $s->id }}">
                        <div style="display:flex;gap:.35rem;margin-top:.35rem">
                            <button type="button" class="principal" wire:click="aprobar('{{ $s->id }}')">Aprobar</button>
                            <button type="button" class="peligro" wire:click="rechazar('{{ $s->id }}')">Rechazar</button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="tenue">No hay solicitudes pendientes.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="tarjeta">
        <h3>Resueltas recientemente</h3>
        <table>
            <thead><tr><th>Productor</th><th>Zona solicitada</th><th>Resultado</th><th>Fecha</th></tr></thead>
            <tbody>
            @forelse ($resueltas as $s)
                <tr>
                    <td>{{ $s->productor?->nombres }} {{ $s->productor?->apellidos }}</td>
                    <td>{{ $s->zonaSolicitada?->nombre }}</td>
                    <td><span class="chip {{ $s->estado === 'aprobada' ? 'verde' : 'rojo' }}">{{ $s->estado }}</span></td>
                    <td class="tenue">{{ $s->resuelto_en?->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="tenue">Sin resoluciones recientes.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
