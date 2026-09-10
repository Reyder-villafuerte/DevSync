<div>
    <div class="tarjeta">
        <h2>Nuevo aviso</h2>
        <form wire:submit="crear">
            <div class="campo">
                <label for="av-t">Título</label>
                <input id="av-t" wire:model="titulo">
                @error('titulo') <span class="error-campo">{{ $message }}</span> @enderror
            </div>
            <div class="campo">
                <label for="av-m">Mensaje</label>
                <textarea id="av-m" rows="3" wire:model="mensaje"></textarea>
                @error('mensaje') <span class="error-campo">{{ $message }}</span> @enderror
            </div>
            <div class="campo-fila">
                <div class="campo">
                    <label for="av-i">Imagen (opcional, máx. 2 MB)</label>
                    <input id="av-i" type="file" wire:model="imagen" accept="image/*">
                    @error('imagen') <span class="error-campo">{{ $message }}</span> @enderror
                    <div wire:loading wire:target="imagen" class="tenue">Subiendo…</div>
                </div>
                <div class="campo">
                    <label for="av-fp">Publicar desde</label>
                    <input id="av-fp" type="date" wire:model="fechaPublicacion">
                    @error('fechaPublicacion') <span class="error-campo">{{ $message }}</span> @enderror
                </div>
                <div class="campo">
                    <label for="av-fe">Expira (opcional)</label>
                    <input id="av-fe" type="date" wire:model="fechaExpiracion">
                    @error('fechaExpiracion') <span class="error-campo">{{ $message }}</span> @enderror
                </div>
            </div>
            <label style="font-weight:normal"><input type="checkbox" style="width:auto" wire:model="obligatorio"> Aviso obligatorio</label>
            <p><button type="submit" class="principal" wire:loading.attr="disabled">Publicar aviso</button></p>
        </form>
    </div>

    <div class="tarjeta">
        <h3>Visibles ahora ({{ $visibles->count() }})</h3>
        @forelse ($visibles as $a)
            <div style="border-bottom:1px solid var(--borde);padding:.6rem 0">
                <strong>{{ $a->titulo }}</strong>
                @if ($a->obligatorio) <span class="chip naranja">obligatorio</span> @endif
                <p style="margin:.3rem 0">{{ $a->contenido }}</p>
                @if ($a->imagen_url) <p><a href="{{ $a->imagen_url }}" target="_blank">ver imagen</a></p> @endif
                <span class="tenue">Desde {{ $a->fecha_publicacion->format('d/m/Y') }}
                    @if ($a->fecha_expiracion) · hasta {{ $a->fecha_expiracion->format('d/m/Y') }} @endif
                </span>
                <button type="button" class="peligro" style="float:right" wire:click="retirar('{{ $a->id }}')">Retirar</button>
            </div>
        @empty
            <p class="tenue">Nada visible en el mural.</p>
        @endforelse
    </div>

    <div class="tarjeta">
        <h3>Programados ({{ $programados->count() }})</h3>
        <table>
            <thead><tr><th>Título</th><th>Se publica</th><th></th></tr></thead>
            <tbody>
            @forelse ($programados as $a)
                <tr>
                    <td>{{ $a->titulo }}</td>
                    <td>{{ $a->fecha_publicacion->format('d/m/Y') }}</td>
                    <td><button type="button" class="peligro" wire:click="retirar('{{ $a->id }}')">Retirar</button></td>
                </tr>
            @empty
                <tr><td colspan="3" class="tenue">Sin avisos programados.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
