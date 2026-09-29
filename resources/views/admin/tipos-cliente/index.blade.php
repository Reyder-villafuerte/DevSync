@extends('layouts.app')

@section('title', 'Tipos de Cliente')

@section('content')
<div class="space-y-6">

    <x-tabla
        titulo="Tipos de Cliente"
        descripcion="Cómo se agrupa a los compradores: desde qué cantidad entra cada tipo y a qué roles del padrón se reconoce solo. El precio no se pone aquí — cada producto lleva su tarifa para cada tipo y se carga en Productos y Recetas al crearlo junto con su receta."
        :coleccion="$tipos"
        :columnas="5"
        vacio="Ningún tipo de cliente coincide con el filtro.">

        <x-slot:acciones>
            <button type="button" data-nuevo-tipo
                class="px-4 py-2.5 rounded-xl bg-spark-dark hover:bg-black text-spark-lime font-black text-[11px] uppercase tracking-wider whitespace-nowrap">
                <i class="fa-solid fa-plus mr-1"></i> Agregar
            </button>
        </x-slot:acciones>

        <x-slot:filtros>
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <form method="GET" action="{{ route('admin.tipos-cliente.index') }}" class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                        <input type="search" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar tipo..."
                            class="w-52 pl-8 pr-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-spark-lime">
                    </div>
                    <select name="estado" class="py-2 px-3 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800">
                        <option value="">Activos e inactivos</option>
                        <option value="activos" @selected(request('estado') === 'activos')>Solo activos</option>
                        <option value="inactivos" @selected(request('estado') === 'inactivos')>Solo inactivos</option>
                    </select>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-spark-lime font-bold text-[10px] uppercase">Filtrar</button>
                    @if(request('buscar') || request('estado'))
                    <a href="{{ route('admin.tipos-cliente.index') }}" class="text-[11px] font-bold text-slate-500 underline">Quitar filtros</a>
                    @endif
                </form>

                <span class="text-[10px] text-slate-500 leading-relaxed max-w-lg">
                    <strong class="text-slate-700">Cómo se elige la tarifa:</strong>
                    1) si el cliente está vinculado a alguien del padrón cuyo rol reconoce un tipo, gana ese tipo;
                    2) si no, entre el asignado y los tramos por cantidad que alcance, gana el de mayor mínimo;
                    3) si no hay nada, la tarifa de público.
                </span>
            </div>
        </x-slot:filtros>

        <x-slot:encabezados>
            <th class="text-left py-3 px-4 font-bold">Tipo</th>
            <th class="text-right py-3 px-4 font-bold">Desde</th>
            <th class="text-left py-3 px-4 font-bold">Roles que reconoce</th>
            <th class="text-right py-3 px-4 font-bold">Clientes</th>
            <th class="text-right py-3 px-4 font-bold">Acciones</th>
        </x-slot:encabezados>

        @foreach($tipos as $tipo)
        <tr class="border-b border-slate-50 hover:bg-slate-50/60 transition">
            <td class="py-3 px-4 font-mono text-slate-400">{{ $tipo->id }}</td>
            <td class="py-3 px-4 font-bold {{ $tipo->is_active ? 'text-slate-800' : 'text-slate-400 line-through' }}">
                {{ $tipo->name }}
                @if($tipo->description)
                <span class="block font-normal text-[10px] text-slate-400 no-underline">{{ $tipo->description }}</span>
                @endif
            </td>
            <td class="py-3 px-4 text-right text-slate-600">
                {{ rtrim(rtrim(number_format($tipo->min_quantity, 2, '.', ''), '0'), '.') }}
            </td>
            <td class="py-3 px-4">
                @forelse($tipo->roles as $rol)
                <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-blue-100 text-blue-900 inline-block mb-0.5">
                    {{ $rol->etiqueta() }}
                </span>
                @empty
                <span class="text-slate-400">—</span>
                @endforelse
            </td>
            <td class="py-3 px-4 text-right text-slate-700 font-bold">{{ $tipo->customers_count }}</td>
            <td class="py-3 px-4">
                <div class="flex justify-end gap-1.5">
                    <button type="button" data-editar-tipo
                        data-id="{{ $tipo->id }}"
                        data-nombre="{{ $tipo->name }}"
                        data-minimo="{{ rtrim(rtrim(number_format($tipo->min_quantity, 2, '.', ''), '0'), '.') }}"
                        data-roles="{{ json_encode($tipo->rolesReconocidos()) }}"
                        data-descripcion="{{ $tipo->description }}"
                        data-activo="{{ $tipo->is_active ? '1' : '0' }}"
                        class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10px] uppercase">
                        Editar
                    </button>
                    <form action="{{ route('admin.tipos-cliente.destroy', $tipo) }}" method="POST"
                        onsubmit="return confirm('¿Eliminar el tipo {{ $tipo->name }}?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-[10px] uppercase">
                            Eliminar
                        </button>
                    </form>
                </div>
            </td>
        </tr>
        @endforeach
    </x-tabla>

</div>

{{-- Modal: crear o editar tipo de cliente --}}
<div id="modalTipo" hidden class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900" data-titulo>Nuevo tipo de cliente</h3>
                <p class="text-[11px] text-slate-500">Ejemplos: Mayorista, Convenio colegio, Empleado.</p>
            </div>
            <button type="button" data-cerrar class="text-slate-400 hover:text-slate-700 text-lg leading-none">&times;</button>
        </div>

        <form method="POST" action="{{ route('admin.tipos-cliente.store') }}" class="space-y-3 text-xs" data-formulario
            data-url-crear="{{ route('admin.tipos-cliente.store') }}"
            data-url-editar="{{ route('admin.tipos-cliente.update', ['tipo' => '__ID__']) }}">
            @csrf
            <input type="hidden" name="_method" value="POST" data-metodo>

            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Nombre</label>
                <input type="text" name="name" required maxlength="50" data-campo="name" placeholder="Ej. Convenio colegio"
                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
            </div>

            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Desde qué cantidad</label>
                <input type="number" step="0.01" min="0" name="min_quantity" value="0" data-campo="min_quantity"
                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                <span class="text-[10px] text-slate-400 mt-1 block">
                    0 = siempre. 10 = solo a partir de 10 unidades del mismo producto.
                </span>
            </div>

            <p class="text-[10px] text-slate-500 bg-slate-50 border border-slate-200 rounded-xl p-3">
                El precio no se pone aquí. Cada producto lleva su tarifa para este tipo y se carga en
                Productos y Recetas: así la mantequilla no hereda el precio del queso.
            </p>

            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Roles que reconoce</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 bg-slate-50 border border-slate-200 rounded-xl p-3">
                    @foreach($roles as $valor => $etiqueta)
                    <label class="flex items-center gap-2 text-[11px] font-semibold text-slate-700">
                        <input type="checkbox" name="auto_roles[]" value="{{ $valor }}" data-rol="{{ $valor }}">
                        {{ $etiqueta }}
                    </label>
                    @endforeach
                </div>
                <span class="text-[10px] text-slate-400 mt-1 block">
                    Marca todos los que correspondan: un tipo «Empleado» los lleva todos de una vez. A quien
                    esté vinculado a alguien del padrón con alguno de esos roles se le reconoce esta tarifa
                    sin importar cuánto compre. Un rol solo puede estar en un tipo.
                </span>
            </div>

            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Descripción (opcional)</label>
                <input type="text" name="description" maxlength="255" data-campo="description"
                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800">
            </div>

            <label class="flex items-center gap-2 text-[11px] font-bold text-slate-600" data-solo-edicion>
                <input type="checkbox" name="is_active" value="1" data-campo="is_active" checked>
                Tipo activo (se puede elegir al vender)
            </label>

            <div class="flex gap-2 pt-2">
                <button type="button" data-cerrar class="flex-1 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] uppercase">Cancelar</button>
                <button type="submit" class="flex-1 bg-spark-dark hover:bg-black text-spark-lime font-black py-2.5 rounded-xl text-[11px] uppercase tracking-wider">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('modalTipo');
    const form = modal.querySelector('[data-formulario]');

    function abrir(valores, id) {
        modal.querySelector('[data-titulo]').textContent = id ? 'Editar tipo de cliente' : 'Nuevo tipo de cliente';
        form.action = id ? form.dataset.urlEditar.replace('__ID__', id) : form.dataset.urlCrear;
        form.querySelector('[data-metodo]').value = id ? 'PUT' : 'POST';

        form.querySelectorAll('[data-campo]').forEach(function (campo) {
            if (campo.type === 'checkbox') {
                campo.checked = id ? valores[campo.dataset.campo] === '1' : true;
            } else {
                campo.value = valores[campo.dataset.campo] ?? '';
            }
        });

        // Los roles son varias casillas, no un campo suelto.
        const marcados = valores.auto_roles ?? [];
        form.querySelectorAll('[data-rol]').forEach(function (casilla) {
            casilla.checked = marcados.includes(casilla.dataset.rol);
        });

        // Un tipo nace activo: el interruptor solo tiene sentido al editar.
        form.querySelectorAll('[data-solo-edicion]').forEach(function (bloque) {
            bloque.hidden = !id;
        });

        modal.hidden = false;
        form.querySelector('[data-campo="name"]').focus();
    }

    document.querySelectorAll('[data-nuevo-tipo]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            abrir({ min_quantity: '0' }, null);
        });
    });

    document.querySelectorAll('[data-editar-tipo]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            abrir({
                name: boton.dataset.nombre,
                min_quantity: boton.dataset.minimo,
                auto_roles: JSON.parse(boton.dataset.roles || '[]'),
                description: boton.dataset.descripcion,
                is_active: boton.dataset.activo,
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
