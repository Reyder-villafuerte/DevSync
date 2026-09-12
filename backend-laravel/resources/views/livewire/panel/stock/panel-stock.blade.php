<div wire:poll.30s>
    <div class="tarjeta">
        <h2>Leche cruda en planta</h2>
        <p class="tenue">
            Materia prima: todo lo recibido de las entregas de los socios, menos lo que ya entró a producción.
        </p>

        <div class="datos-conciliacion">
            <div class="dato azul">
                <div class="rotulo">Acopiado (todas las entregas)</div>
                <div class="cifra">{{ number_format($this->litrosAcopiados, 2) }} L</div>
            </div>
            <div class="dato neutro">
                <div class="rotulo">Procesado en planta</div>
                <div class="cifra">{{ number_format($this->litrosProcesados, 2) }} L</div>
            </div>
            <div class="dato {{ $this->litrosDisponibles < 0 ? 'rojo' : 'verde' }}">
                <div class="rotulo">Disponible sin procesar</div>
                <div class="cifra">{{ number_format($this->litrosDisponibles, 2) }} L</div>
            </div>
            <div class="dato neutro">
                <div class="rotulo">Verificado contra caudalímetro</div>
                <div class="cifra">{{ number_format($this->litrosRecibidosConformes, 2) }} L</div>
            </div>
        </div>

        @if ($this->litrosDisponibles < 0)
            <p class="alerta">
                ⚠ Se procesaron más litros de los acopiados. Revise las sesiones de producción
                o las entregas que falten por registrar.
            </p>
        @endif
    </div>

    <div class="tarjeta">
        <h2>Producto terminado</h2>
        <p class="tenue">
            Saldo derivado del libro de movimientos: producción suma, venta y merma restan.
        </p>

        <table>
            <thead>
            <tr>
                <th>Producto</th>
                <th class="num">En stock</th>
                <th>Unidad</th>
                <th class="num">Movimientos</th>
                <th>Último movimiento</th>
            </tr>
            </thead>
            <tbody>
            @forelse ($productos as $p)
                <tr>
                    <td>{{ $p['nombre'] }}</td>
                    <td class="num {{ $p['cantidad'] <= 0 ? 'alerta' : '' }}">
                        <strong>{{ number_format($p['cantidad'], 2) }}</strong>
                    </td>
                    <td>{{ $p['unidad'] }}</td>
                    <td class="num">{{ $p['movimientos'] }}</td>
                    <td class="tenue">
                        {{ $p['ultimo']?->format('d/m/Y H:i') ?? 'sin movimientos' }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="tenue">No hay productos en el catálogo.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="tarjeta">
        <h3>Últimos movimientos</h3>
        <table>
            <thead>
            <tr><th>Fecha</th><th>Producto</th><th>Tipo</th><th class="num">Cantidad</th><th>Motivo</th></tr>
            </thead>
            <tbody>
            @forelse ($movimientos as $m)
                <tr>
                    <td>{{ $m->ocurrido_en?->format('d/m/Y H:i') }}</td>
                    <td>{{ $m->producto?->nombre }}</td>
                    <td>{{ str_replace('_', ' ', $m->tipo_movimiento) }}</td>
                    <td class="num {{ $m->cantidad < 0 ? 'alerta' : 'ok' }}">
                        {{ $m->cantidad > 0 ? '+' : '' }}{{ number_format((float) $m->cantidad, 2) }}
                    </td>
                    <td class="tenue">{{ $m->motivo ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="tenue">Todavía no hay movimientos de stock.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
