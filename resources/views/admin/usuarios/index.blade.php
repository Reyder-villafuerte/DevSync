@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
<div class="space-y-6">

    <x-tabla
        titulo="Usuarios"
        descripcion="Quién entra al sistema y con qué rol. Los usuarios no se borran —tienen entregas, ventas y liquidaciones colgando—: se desactivan y su historia queda."
        :coleccion="$usuarios"
        :columnas="7"
        vacio="Ningún usuario coincide con el filtro.">

        <x-slot:acciones>
            <div class="flex items-center gap-2 text-right">
                <div class="bg-slate-50 border border-slate-200 px-3 py-2 rounded-xl">
                    <span class="text-[10px] text-slate-400 block uppercase font-bold">Activos</span>
                    <span class="text-sm font-black text-spark-limeText">{{ $resumen['activos'] }}</span>
                </div>
                <div class="bg-slate-50 border border-slate-200 px-3 py-2 rounded-xl">
                    <span class="text-[10px] text-slate-400 block uppercase font-bold">Inactivos</span>
                    <span class="text-sm font-black text-slate-800">{{ $resumen['inactivos'] }}</span>
                </div>
                <button type="button" data-nuevo-usuario
                    class="px-4 py-2.5 rounded-xl bg-spark-dark hover:bg-black text-spark-lime font-black text-[11px] uppercase tracking-wider whitespace-nowrap">
                    <i class="fa-solid fa-plus mr-1"></i> Agregar
                </button>
            </div>
        </x-slot:acciones>

        <x-slot:filtros>
            <form method="GET" action="{{ route('admin.usuarios.index') }}" class="flex flex-wrap items-center gap-2">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                    <input type="search" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre, DNI o correo..."
                        class="w-56 pl-8 pr-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-spark-lime">
                </div>
                <select name="rol" class="py-2 px-3 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800">
                    <option value="">Todos los roles</option>
                    @foreach($roles as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected(request('rol') === $valor)>{{ $etiqueta }}</option>
                    @endforeach
                </select>
                <select name="estado" class="py-2 px-3 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800">
                    <option value="">Activos e inactivos</option>
                    <option value="activos" @selected(request('estado') === 'activos')>Solo activos</option>
                    <option value="inactivos" @selected(request('estado') === 'inactivos')>Solo inactivos</option>
                </select>
                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-spark-lime font-bold text-[10px] uppercase">Filtrar</button>
                @if(request('buscar') || request('rol') || request('estado'))
                <a href="{{ route('admin.usuarios.index') }}" class="text-[11px] font-bold text-slate-500 underline">Quitar filtros</a>
                @endif
            </form>
        </x-slot:filtros>

        <x-slot:encabezados>
            <th class="text-left py-3 px-4 font-bold">Nombre</th>
            <th class="text-left py-3 px-4 font-bold">DNI</th>
            <th class="text-left py-3 px-4 font-bold">Rol</th>
            <th class="text-left py-3 px-4 font-bold">Zona</th>
            <th class="text-left py-3 px-4 font-bold">Teléfono</th>
            <th class="text-left py-3 px-4 font-bold">Correo</th>
            <th class="text-right py-3 px-4 font-bold">Acciones</th>
        </x-slot:encabezados>

        @foreach($usuarios as $usuario)
        <tr class="border-b border-slate-50 hover:bg-slate-50/60 transition">
            <td class="py-3 px-4 font-mono text-slate-400">{{ $usuario->id }}</td>
            <td class="py-3 px-4 font-bold {{ $usuario->is_active ? 'text-slate-800' : 'text-slate-400' }}">
                {{ $usuario->name }}
                @unless($usuario->is_active)
                <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 ml-1">Inactivo</span>
                @endunless
            </td>
            <td class="py-3 px-4 font-mono text-slate-500">{{ $usuario->dni ?: '—' }}</td>
            <td class="py-3 px-4">
                <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-slate-100 text-slate-700">
                    {{ $roles[$usuario->role] ?? $usuario->role }}
                </span>
            </td>
            <td class="py-3 px-4 text-slate-500">{{ $usuario->zone?->name ?? '—' }}</td>
            <td class="py-3 px-4 text-slate-500">{{ $usuario->phone ?: '—' }}</td>
            <td class="py-3 px-4 text-slate-400">{{ $usuario->email }}</td>
            <td class="py-3 px-4">
                <div class="flex justify-end gap-1.5">
                    <button type="button" data-editar-usuario
                        data-id="{{ $usuario->id }}"
                        data-nombre="{{ $usuario->name }}"
                        data-dni="{{ $usuario->dni }}"
                        data-correo="{{ $usuario->email }}"
                        data-rol="{{ $usuario->role }}"
                        data-telefono="{{ $usuario->phone }}"
                        data-zona="{{ $usuario->zone_id }}"
                        class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10px] uppercase">
                        Editar
                    </button>
                    @if($usuario->id !== auth()->id())
                    <form action="{{ route('admin.usuarios.toggle', $usuario) }}" method="POST"
                        onsubmit="return confirm('¿{{ $usuario->is_active ? 'Desactivar' : 'Activar' }} a {{ $usuario->name }}?');">
                        @csrf
                        <button type="submit"
                            class="px-2.5 py-1.5 rounded-lg font-bold text-[10px] uppercase {{ $usuario->is_active ? 'bg-rose-50 hover:bg-rose-100 text-rose-600' : 'bg-lime-100 hover:bg-lime-200 text-spark-limeText' }}">
                            {{ $usuario->is_active ? 'Desactivar' : 'Activar' }}
                        </button>
                    </form>
                    @endif
                </div>
            </td>
        </tr>
        @endforeach
    </x-tabla>

</div>

{{-- Modal: crear o editar usuario --}}
<div id="modalUsuario" hidden class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900" data-titulo>Nuevo usuario</h3>
                <p class="text-[11px] text-slate-500">Entra al sistema con su DNI o su correo.</p>
            </div>
            <button type="button" data-cerrar class="text-slate-400 hover:text-slate-700 text-lg leading-none">&times;</button>
        </div>

        <form method="POST" action="{{ route('admin.usuarios.store') }}" class="space-y-3 text-xs" data-formulario
            data-url-crear="{{ route('admin.usuarios.store') }}"
            data-url-editar="{{ route('admin.usuarios.update', ['usuario' => '__ID__']) }}">
            @csrf
            <input type="hidden" name="_method" value="POST" data-metodo>

            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Nombre completo</label>
                <input type="text" name="name" required maxlength="120" data-campo="name" placeholder="Ej. Juana Mamani Quispe"
                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">DNI</label>
                    <input type="text" name="dni" required maxlength="20" data-campo="dni" placeholder="40010001"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono font-semibold text-slate-800">
                </div>
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Teléfono</label>
                    <input type="text" name="phone" maxlength="30" data-campo="phone"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                </div>
            </div>

            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Correo</label>
                <input type="email" name="email" required maxlength="150" data-campo="email"
                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Rol</label>
                    <select name="role" required data-campo="role"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                        @foreach($roles as $valor => $etiqueta)
                        <option value="{{ $valor }}">{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                    <span class="text-[10px] text-slate-400 mt-1 block">
                        El rol decide qué pantallas ve. Se consultan en
                        <a href="{{ route('admin.roles.index') }}" class="font-bold underline">Roles</a>.
                    </span>
                </div>
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Zona (opcional)</label>
                    <select name="zone_id" data-campo="zone_id"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                        <option value="">Sin zona</option>
                        @foreach($zonas as $zona)
                        <option value="{{ $zona->id }}">{{ $zona->name }}</option>
                        @endforeach
                    </select>
                    <span class="text-[10px] text-slate-400 mt-1 block">Para productores y acopiadores.</span>
                </div>
            </div>

            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">
                    Contraseña <span data-etiqueta-clave class="text-slate-400 normal-case font-normal"></span>
                </label>
                <input type="text" name="password" minlength="6" maxlength="100" data-campo="password" autocomplete="off"
                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono font-semibold text-slate-800">
            </div>

            <div class="flex gap-2 pt-2">
                <button type="button" data-cerrar class="flex-1 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] uppercase">Cancelar</button>
                <button type="submit" class="flex-1 bg-spark-dark hover:bg-black text-spark-lime font-black py-2.5 rounded-xl text-[11px] uppercase tracking-wider">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('modalUsuario');
    const form = modal.querySelector('[data-formulario]');
    const etiquetaClave = form.querySelector('[data-etiqueta-clave]');
    const campoClave = form.querySelector('[data-campo="password"]');

    function abrir(valores, id) {
        modal.querySelector('[data-titulo]').textContent = id ? 'Editar usuario' : 'Nuevo usuario';
        form.action = id ? form.dataset.urlEditar.replace('__ID__', id) : form.dataset.urlCrear;
        form.querySelector('[data-metodo]').value = id ? 'PUT' : 'POST';

        form.querySelectorAll('[data-campo]').forEach(function (campo) {
            campo.value = valores[campo.dataset.campo] ?? '';
        });

        // Al editar, la contraseña solo cambia si escriben una nueva.
        campoClave.required = !id;
        etiquetaClave.textContent = id ? '— déjala en blanco para no cambiarla' : '';

        modal.hidden = false;
        form.querySelector('[data-campo="name"]').focus();
    }

    document.querySelectorAll('[data-nuevo-usuario]').forEach(function (boton) {
        boton.addEventListener('click', function () { abrir({}, null); });
    });

    document.querySelectorAll('[data-editar-usuario]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            abrir({
                name: boton.dataset.nombre,
                dni: boton.dataset.dni,
                email: boton.dataset.correo,
                role: boton.dataset.rol,
                phone: boton.dataset.telefono,
                zone_id: boton.dataset.zona,
            }, boton.dataset.id);
        });
    });

    modal.querySelectorAll('[data-cerrar]').forEach(function (boton) {
        boton.addEventListener('click', function () { modal.hidden = true; });
    });

    modal.addEventListener('click', function (evento) {
        if (evento.target === modal) { modal.hidden = true; }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') { modal.hidden = true; }
    });

    @if($errors->any())
    modal.hidden = false;
    @endif
})();
</script>
@endsection
