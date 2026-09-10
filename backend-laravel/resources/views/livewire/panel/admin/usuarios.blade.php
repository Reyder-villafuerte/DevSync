<div>
    <div class="tarjeta">
        <h2>Nuevo usuario</h2>
        <form wire:submit="crear">
            <div class="campo-fila">
                <div class="campo">
                    <label for="u-n">Nombres</label>
                    <input id="u-n" wire:model="nombres">
                    @error('nombres') <span class="error-campo">{{ $message }}</span> @enderror
                </div>
                <div class="campo">
                    <label for="u-a">Apellidos</label>
                    <input id="u-a" wire:model="apellidos">
                    @error('apellidos') <span class="error-campo">{{ $message }}</span> @enderror
                </div>
                <div class="campo">
                    <label for="u-d">DNI</label>
                    <input id="u-d" wire:model="dni" inputmode="numeric" pattern="[0-9]{8}">
                    @error('dni') <span class="error-campo">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="campo-fila">
                <div class="campo">
                    <label for="u-e">Correo (opcional)</label>
                    <input id="u-e" type="email" wire:model="email">
                    @error('email') <span class="error-campo">{{ $message }}</span> @enderror
                </div>
                <div class="campo">
                    <label for="u-t">Teléfono (opcional)</label>
                    <input id="u-t" wire:model="telefono">
                </div>
                <div class="campo">
                    <label for="u-p">Contraseña (opcional; se genera si se deja vacía)</label>
                    <input id="u-p" type="text" wire:model="password">
                    @error('password') <span class="error-campo">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="campo-fila">
                <div class="campo">
                    <label for="u-r">Rol</label>
                    <select id="u-r" wire:model.live="rol">
                        <option value="">— seleccione —</option>
                        @foreach ($roles as $r)
                            <option value="{{ $r->value }}">{{ $r->etiqueta() }}</option>
                        @endforeach
                    </select>
                    @error('rol') <span class="error-campo">{{ $message }}</span> @enderror
                </div>
                @if ($rol === 'acopiador')
                    <div class="campo">
                        <label for="u-ruta">Ámbito · Ruta</label>
                        <select id="u-ruta" wire:model="rutaId">
                            <option value="">— seleccione —</option>
                            @foreach ($rutas as $ruta)<option value="{{ $ruta->id }}">{{ $ruta->nombre }}</option>@endforeach
                        </select>
                        @error('rutaId') <span class="error-campo">{{ $message }}</span> @enderror
                    </div>
                @elseif ($rol === 'productor')
                    <div class="campo">
                        <label for="u-zona">Ámbito · Zona</label>
                        <select id="u-zona" wire:model="zonaId">
                            <option value="">— seleccione —</option>
                            @foreach ($zonas as $zona)<option value="{{ $zona->id }}">{{ $zona->nombre }}</option>@endforeach
                        </select>
                        @error('zonaId') <span class="error-campo">{{ $message }}</span> @enderror
                    </div>
                @elseif ($rol)
                    <div class="campo"><label>Ámbito</label><input type="text" value="Planta (sin ámbito)" disabled></div>
                @endif
            </div>
            <button type="submit" class="principal">Crear usuario</button>
        </form>
    </div>

    <div class="tarjeta">
        <h3>Usuarios ({{ $usuarios->count() }})</h3>
        <table>
            <thead><tr><th>Nombre</th><th>DNI</th><th>Rol</th><th>Ámbito</th><th>Estado</th><th></th></tr></thead>
            <tbody>
            @foreach ($usuarios as $u)
                <tr>
                    <td>{{ $u->nombres }} {{ $u->apellidos }}</td>
                    <td>{{ $u->dni }}</td>
                    <td>{{ $u->rol->etiqueta() }}</td>
                    <td class="tenue">{{ $u->acopiador?->ruta?->nombre ?? $u->productor?->zona?->nombre ?? '—' }}</td>
                    <td>
                        @if ($u->activo)
                            <span class="chip verde">activo</span>
                        @else
                            <span class="chip rojo">suspendido</span>
                        @endif
                    </td>
                    <td>
                        @if ($u->activo)
                            <button type="button" class="peligro" wire:click="suspender('{{ $u->id }}')">Suspender</button>
                        @else
                            <button type="button" class="suave" wire:click="reactivar('{{ $u->id }}')">Reactivar</button>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
