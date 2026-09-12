<div class="tarjeta">
    <h2>Conciliación de volumen y recepción de ruta (total)</h2>

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
            <label for="ra">Ruta cerrada</label>
            <select id="ra" wire:model.live="rutaAcopioId" required>
                <option value="">— seleccione una ruta pendiente de conciliar —</option>
                @foreach ($pendientes as $ra)
                    <option value="{{ $ra->id }}">
                        {{ $ra->fecha->format('d/m/Y') }} · {{ $ra->ruta?->nombre }} ·
                        {{ $ra->acopiador?->usuario?->nombres ?? 'N/D' }}
                        ({{ number_format((float) $ra->litros_declarados, 2) }} L declarados)
                    </option>
                @endforeach
            </select>
            <p class="tenue">
                @if ($this->rutaElegida)
                    Conciliación total de la ruta del
                    {{ $this->rutaElegida->fecha->format('d/m/Y') }},
                    {{ $this->rutaElegida->registros()->where('deleted', false)->count() }} entregas.
                @else
                    Se concilia la ruta completa, no entrega por entrega.
                @endif
            </p>
        </div>

        @if ($rutaAcopioId)
            <div class="datos-conciliacion">
                <div class="dato azul">
                    <div class="rotulo">Total declarado por el acopiador (ruta)</div>
                    <div class="cifra">{{ number_format($this->litrosAcopiador, 2) }} L</div>
                </div>

                <div class="dato verde">
                    <label class="rotulo" for="cau">Total medido por caudalímetro (planta)</label>
                    <input id="cau" class="cifra-editable" type="number" step="0.01" min="0"
                           placeholder="Escriba el volumen medido…"
                           wire:model.live="litrosCaudalimetro" required>
                </div>

                <div class="dato {{ $this->superaTolerancia ? 'rojo' : ($litrosCaudalimetro ? 'verde' : 'neutro') }}">
                    <div class="rotulo">Diferencia en volumen (total ruta)</div>
                    <div class="cifra">{{ number_format($this->diferencia, 2) }} L</div>
                </div>

                <div class="dato {{ $this->superaTolerancia ? 'rojo' : ($litrosCaudalimetro ? 'verde' : 'neutro') }}">
                    <div class="rotulo">Diferencia porcentual</div>
                    <div class="cifra">{{ number_format($this->porcentaje, 2) }}%</div>
                </div>
            </div>

            <div class="cierre-conciliacion">
                <div class="izquierda">
                <div class="estado-conciliacion">
                    <div class="rotulo">Estado de la conciliación</div>
                    @if (! $litrosCaudalimetro)
                        <span class="chip gris">Falta el volumen medido</span>
                    @elseif ($this->superaTolerancia)
                        <span class="chip rojo">Tolerancia superada</span>
                    @else
                        <span class="chip verde">Dentro de tolerancia ({{ $this->tolerancia }}%)</span>
                    @endif
                </div>

                <div class="campo">
                    <label for="obs">Observaciones de la ruta</label>
                    <textarea id="obs" rows="3" maxlength="255" wire:model="observacion"
                              placeholder="Opcional. Para explicar la diferencia (derrame, mal aforo, parada extra…)."></textarea>
                    @error('observacion') <span class="error-campo">{{ $message }}</span> @enderror
                </div>
                </div>

                <button type="submit" class="principal grande" wire:loading.attr="disabled">
                    Confirmar y registrar recepción de la ruta
                </button>
            </div>

            @if ($this->superaTolerancia)
                <p class="alerta">
                    ⚠ La diferencia supera el {{ $this->tolerancia }}%. Verifique antes de registrar;
                    la conciliación queda marcada con alerta.
                </p>
            @endif
        @endif

        @error('litrosCaudalimetro') <span class="error-campo">{{ $message }}</span> @enderror
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
