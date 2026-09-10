<div class="tarjeta" wire:poll.10s>
    <h2>Stock disponible</h2>
    <table>
        <thead><tr><th>Producto</th><th class="num">Disponible</th><th>Último movimiento</th></tr></thead>
        <tbody>
        @forelse ($stock as $s)
            <tr>
                <td>{{ $s->producto_nombre }}</td>
                <td class="num @if((float) $s->cantidad_actual <= 0) alerta @endif">{{ $s->cantidad_actual }}</td>
                <td class="tenue">{{ optional($s->ultimo_movimiento_en)->format('d/m/Y H:i') ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="3" class="tenue">Sin stock registrado.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
