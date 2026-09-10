<div>
    <div class="tarjeta">
        <h2>Liquidación del viernes</h2>
        <p class="tenue">Ciclo jueves → miércoles. Se congela la tarifa de la semana y se aplican las sanciones vigentes.</p>

        <form wire:submit="generar" class="campo-fila">
            <div class="campo">
                <label for="fref">Fecha de referencia (cualquier día del ciclo)</label>
                <input id="fref" type="date" wire:model.blur="fechaReferencia">
                @error('fechaReferencia') <span class="error-campo">{{ $message }}</span> @enderror
            </div>
            <div class="campo" style="display:flex;align-items:flex-end">
                <button type="submit" class="principal" wire:loading.attr="disabled">Generar liquidación</button>
            </div>
        </form>
        <p>
            Ciclo: <strong>{{ \Carbon\Carbon::parse($rango['fecha_inicio'])->format('d/m/Y') }}</strong>
            – <strong>{{ \Carbon\Carbon::parse($rango['fecha_fin'])->format('d/m/Y') }}</strong>
            · pago el {{ \Carbon\Carbon::parse($rango['fecha_liquidacion'])->format('d/m/Y') }}
            @if ($semana)
                · Total del ciclo: <strong>S/ {{ number_format($totalCiclo, 2) }}</strong>
                · Estado: <span class="chip gris">{{ $semana->estado->value }}</span>
            @endif
        </p>
    </div>

    @if ($semana && $semana->liquidaciones->isNotEmpty())
        <div class="tarjeta">
            <h3>Liquidaciones por productor</h3>
            <table>
                <thead>
                    <tr>
                        <th>Productor</th><th class="num">Litros</th><th class="num">S/ litro</th>
                        <th>Tarifa</th><th>Sanción</th><th class="num">Neto</th><th>Estado</th><th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($semana->liquidaciones as $l)
                    @php $sancion = $l->detalles->firstWhere('concepto', 'descuento_agua'); @endphp
                    <tr>
                        <td>{{ $l->productor?->nombres }} {{ $l->productor?->apellidos }}</td>
                        <td class="num">{{ $l->litros_totales }}</td>
                        <td class="num">{{ $l->precio_litro_aplicado }}</td>
                        <td>
                            @if ($l->tarifa_degradada)
                                <span class="chip rojo">mínima (RN-05)</span>
                            @else
                                <span class="chip gris">normal</span>
                            @endif
                        </td>
                        <td>
                            @if ($sancion)
                                <span class="alerta">{{ $sancion->descripcion }}</span>
                                <span class="tenue">(S/ {{ number_format(abs((float) $sancion->monto), 2) }})</span>
                            @else
                                —
                            @endif
                        </td>
                        <td class="num"><strong>{{ number_format((float) $l->monto_neto, 2) }}</strong></td>
                        <td><span class="chip {{ $l->estado === 'entregada' ? 'verde' : ($l->estado === 'pagada' ? 'naranja' : 'gris') }}">{{ $l->estado }}</span></td>
                        <td>
                            <div style="display:flex;gap:.35rem;align-items:center">
                                @if (! in_array($l->estado, ['pagada', 'entregada']))
                                    <button type="button" class="suave" wire:click="pagar('{{ $l->id }}')">Pagar</button>
                                @endif
                                @if (in_array($l->estado, ['pagada', 'entregada']))
                                    <label style="font-weight:normal;margin:0">
                                        <input type="checkbox" style="width:auto"
                                               @checked($l->estado === 'entregada')
                                               @disabled($l->estado === 'entregada')
                                               wire:click="entregarSobre('{{ $l->id }}')"> sobre entregado
                                    </label>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                    <tr><th colspan="5">Total del ciclo</th><th class="num">S/ {{ number_format($totalCiclo, 2) }}</th><th colspan="2"></th></tr>
                </tfoot>
            </table>
        </div>
    @endif

    <div class="tarjeta">
        <h3>Últimas semanas</h3>
        <table>
            <thead><tr><th>Inicio</th><th>Fin</th><th>Pago</th><th>Estado</th><th class="num"># Liquidaciones</th><th></th></tr></thead>
            <tbody>
            @foreach ($semanas as $s)
                <tr>
                    <td>{{ $s->fecha_inicio->format('d/m/Y') }}</td>
                    <td>{{ $s->fecha_fin->format('d/m/Y') }}</td>
                    <td>{{ $s->fecha_liquidacion->format('d/m/Y') }}</td>
                    <td>{{ $s->estado->value }}</td>
                    <td class="num">{{ $s->liquidaciones_count }}</td>
                    <td><a href="{{ route('liquidaciones.show', $s) }}">ver detalle</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
