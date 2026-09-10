<div class="tarjeta">
    <h2>Sesiones de producción</h2>

    <form wire:submit="iniciar" class="campo-fila">
        <div class="campo">
            <label for="prod">Producto</label>
            <select id="prod" wire:model.live="productoId">
                <option value="">— seleccione —</option>
                @foreach ($productos as $p)
                    <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                @endforeach
            </select>
            @error('productoId') <span class="error-campo">{{ $message }}</span> @enderror
        </div>
        <div class="campo">
            <label for="lit">Litros a procesar</label>
            <input id="lit" type="number" step="0.01" min="0.01" wire:model.live="litros">
            @error('litros') <span class="error-campo">{{ $message }}</span> @enderror
        </div>
        <div class="campo">
            <label>Estimación de unidades</label>
            <input type="text" value="{{ $this->estimacion !== null ? $this->estimacion.' u.' : '—' }}" disabled>
        </div>
        <div class="campo" style="display:flex;align-items:flex-end">
            <button type="submit" class="principal" wire:loading.attr="disabled">Iniciar sesión</button>
        </div>
    </form>

    <h3>Sesiones abiertas</h3>
    <table>
        <thead><tr><th>Lote</th><th>Producto</th><th class="num">Litros</th><th>Estado</th><th>Completar</th></tr></thead>
        <tbody>
        @forelse ($pendientes as $s)
            <tr>
                <td>{{ $s->lote_codigo }}</td>
                <td>{{ $s->producto?->nombre }}</td>
                <td class="num">{{ $s->litros_procesados }}</td>
                <td><span class="chip naranja">{{ $s->estado->value }}</span></td>
                <td>
                    <div style="display:flex;gap:.4rem;align-items:center">
                        <input type="number" min="0" style="width:90px" placeholder="unid." wire:model="unidades.{{ $s->id }}">
                        <button type="button" class="suave" wire:click="completar('{{ $s->id }}')" wire:loading.attr="disabled">Completar</button>
                    </div>
                    @error('completar_'.$s->id) <span class="error-campo">{{ $message }}</span> @enderror
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="tenue">Sin sesiones abiertas.</td></tr>
        @endforelse
        </tbody>
    </table>

    <h3>Completadas recientemente</h3>
    <table>
        <thead><tr><th>Lote</th><th>Producto</th><th class="num">Litros</th><th class="num">Unidades</th><th class="num">Rend./100 L</th><th>RN-08</th></tr></thead>
        <tbody>
        @forelse ($completadas as $s)
            <tr>
                <td>{{ $s->lote_codigo }}</td>
                <td>{{ $s->producto?->nombre }}</td>
                <td class="num">{{ $s->litros_procesados }}</td>
                <td class="num">{{ $s->unidades_producidas }}</td>
                <td class="num">{{ $s->rendimiento_por_100l ?? '—' }}</td>
                <td>
                    @if ($s->cumple_rn08 === null)
                        <span class="chip gris">n/a</span>
                    @elseif ($s->cumple_rn08)
                        <span class="chip verde">conforme</span>
                    @else
                        <span class="chip rojo">fuera de meta</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="tenue">Aún no hay sesiones completadas.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
