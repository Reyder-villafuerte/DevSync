<div>
    <div class="tarjeta">
        <h2>Precio de compra de leche</h2>
        <form wire:submit="guardarCompra" class="campo-fila">
            <div class="campo">
                <label for="pl">Precio por litro (S/)</label>
                <input id="pl" type="number" step="0.0001" min="0" wire:model.blur="precioLitro">
                @error('precioLitro') <span class="error-campo">{{ $message }}</span> @enderror
            </div>
            <div class="campo">
                <label for="plm">Tarifa mínima degradada (RN-05)</label>
                <input id="plm" type="number" step="0.0001" min="0" wire:model.blur="precioLitroMinimo">
                @error('precioLitroMinimo') <span class="error-campo">{{ $message }}</span> @enderror
            </div>
            <div class="campo" style="display:flex;align-items:flex-end">
                <button type="submit" class="principal">Guardar (nueva vigencia)</button>
            </div>
        </form>

        <h3>Histórico de vigencias</h3>
        <table>
            <thead><tr><th>Desde</th><th>Hasta</th><th class="num">S/ litro</th><th class="num">S/ mínimo</th></tr></thead>
            <tbody>
            @foreach ($historicoCompra as $h)
                <tr>
                    <td>{{ $h->vigente_desde->format('d/m/Y') }}</td>
                    <td>{{ $h->vigente_hasta?->format('d/m/Y') ?? 'vigente' }}</td>
                    <td class="num">{{ $h->precio_litro }}</td>
                    <td class="num">{{ $h->precio_litro_minimo }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div class="tarjeta">
        <h2>Precios de venta por producto y tipo de cliente</h2>
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    @foreach ($tipos as $t)<th class="num">{{ ucfirst($t->value) }}</th>@endforeach
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach ($productos as $p)
                <tr>
                    <td>{{ $p->nombre }}</td>
                    @foreach ($tipos as $t)
                        <td class="num">
                            <input type="number" step="0.01" min="0" style="width:90px"
                                   wire:model.blur="preciosVenta.{{ $p->id }}.{{ $t->value }}">
                            @error("preciosVenta.{$p->id}.{$t->value}") <span class="error-campo">{{ $message }}</span> @enderror
                        </td>
                    @endforeach
                    <td><button type="button" class="suave" wire:click="guardarVenta('{{ $p->id }}')">Guardar</button></td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <p class="tenue">Al guardar se cierra la vigencia anterior (<code>vigente_hasta</code>) y se abre una nueva; nada se sobrescribe.</p>
    </div>
</div>
