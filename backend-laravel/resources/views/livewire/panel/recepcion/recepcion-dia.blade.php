<div class="tarjeta">
    <h2>Recepción del día — Litros Acopiador vs. Caudalímetro</h2>

    @if ($ultimoResultado)
        <p class="flash {{ $ultimoResultado['alerta'] ? 'error' : 'ok' }}">
            {{ $ultimoResultado['ruta'] }} · Acopiador {{ $ultimoResultado['acopiador'] }} L ·
            Caudalímetro {{ $ultimoResultado['caudalimetro'] }} L ·
            Diferencia {{ $ultimoResultado['diferencia'] }} L ({{ $ultimoResultado['porcentaje'] }}%)
            {{ $ultimoResultado['alerta'] ? '— SUPERA LA TOLERANCIA' : '— dentro de tolerancia' }}
        </p>
    @endif

    <form wire:submit="conciliar">
        <div class="campo">
            <label for="ra">Ruta de acopio pendiente de conciliar</label>
            <select id="ra" wire:model.live="rutaAcopioId" required>
                <option value="">— seleccione —</option>
                @foreach ($pendientes as $ra)
                    <option value="{{ $ra->id }}">
                        {{ $ra->fecha->format('d/m/Y') }} · {{ $ra->ruta?->nombre }} ·
                        {{ $ra->acopiador?->usuario?->nombres ?? 'N/D' }} ({{ $ra->registros->count() }} entregas)
                    </option>
                @endforeach
            </select>
        </div>

        @if ($rutaAcopioId)
            <div class="comparativa">
                <div class="celda">
                    <div class="valor">{{ number_format($this->litrosAcopiador, 2) }}</div>
                    <div class="rotulo">Litros Acopiador (registrados)</div>
                </div>
                <div class="celda">
                    <label for="cau" class="rotulo">Litros Caudalímetro</label>
                    <input id="cau" type="number" step="0.01" min="0" wire:model.live="litrosCaudalimetro" required>
                </div>
                <div class="celda {{ $this->superaTolerancia ? 'mal' : ($litrosCaudalimetro ? 'bien' : '') }}">
                    <div class="valor">{{ number_format($this->diferencia, 2) }}</div>
                    <div class="rotulo">Diferencia (L)</div>
                </div>
                <div class="celda {{ $this->superaTolerancia ? 'mal' : ($litrosCaudalimetro ? 'bien' : '') }}">
                    <div class="valor">{{ number_format($this->porcentaje, 2) }}%</div>
                    <div class="rotulo">Diferencia (%) · tolerancia {{ $this->tolerancia }}%</div>
                </div>
            </div>

            @if ($this->superaTolerancia)
                <p class="alerta">⚠ La diferencia supera la tolerancia del {{ $this->tolerancia }}%. Verifique antes de registrar.</p>
            @endif
        @endif

        @error('litrosCaudalimetro') <span class="error-campo">{{ $message }}</span> @enderror

        <button type="submit" class="principal" wire:loading.attr="disabled">Registrar conciliación</button>
    </form>

    <h3>Alertas de conciliación recientes</h3>
    <table>
        <thead><tr><th>Fecha</th><th class="num">Acopiador (L)</th><th class="num">Caudalímetro (L)</th><th class="num">Dif.</th><th class="num">Dif. %</th></tr></thead>
        <tbody>
        @forelse ($alertas as $c)
            <tr>
                <td>{{ $c->conciliado_en?->format('d/m/Y H:i') }}</td>
                <td class="num">{{ $c->litros_acopiador }}</td>
                <td class="num">{{ $c->litros_caudalimetro }}</td>
                <td class="num">{{ $c->diferencia_litros }}</td>
                <td class="num alerta">{{ $c->diferencia_porcentaje }}%</td>
            </tr>
        @empty
            <tr><td colspan="5" class="tenue">Sin alertas.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
