<div class="tarjeta">
    <h2>Punto de venta</h2>

    @if ($comprobante)
        <div class="flash ok">
            Comprobante <strong>{{ $comprobante }}</strong> emitido.
            <a class="boton suave no-imprimir" href="{{ route('ventas.comprobante', $ventaId) }}" target="_blank" style="margin-left:.6rem">Imprimir</a>
        </div>
    @endif

    <form wire:submit="confirmar">
        <div class="campo-fila">
            <div class="campo">
                <label for="cli">Cliente / tipo</label>
                <select id="cli" wire:model.live="clienteId">
                    <option value="">— seleccione —</option>
                    @foreach ($clientes as $c)
                        <option value="{{ $c->id }}">{{ $c->nombre }} ({{ $c->tipo_cliente->value }})</option>
                    @endforeach
                </select>
                @error('clienteId') <span class="error-campo">{{ $message }}</span> @enderror
            </div>
            <div class="campo">
                <label for="pro">Producto</label>
                <select id="pro" wire:model.live="productoId">
                    <option value="">— seleccione —</option>
                    @foreach ($productos as $p)
                        <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                    @endforeach
                </select>
                @error('productoId') <span class="error-campo">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="campo-fila">
            <div class="campo">
                <label for="cant">Cantidad</label>
                <input id="cant" type="number" step="0.01" min="0.01" wire:model.live="cantidad">
                @error('cantidad') <span class="error-campo">{{ $message }}</span> @enderror
                @if ($this->disponible !== null)
                    <span class="tenue">Disponible: {{ $this->disponible }}</span>
                @endif
            </div>
            <div class="campo">
                <label>Precio unitario (tarifa vigente)</label>
                <input type="text" disabled value="{{ $this->precio ? 'S/ '.number_format((float) $this->precio->precio, 2) : '—' }}">
            </div>
            <div class="campo">
                <label>Total</label>
                <input type="text" disabled value="S/ {{ number_format($this->total, 2) }}">
            </div>
        </div>

        <button type="submit" class="principal" wire:loading.attr="disabled">Confirmar venta</button>
    </form>
</div>
