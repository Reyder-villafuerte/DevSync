@extends('layouts.app')

@section('title', 'Categorías y Unidades')

@section('content')
<div class="space-y-6">

    <x-tabla
        titulo="Categorías"
        descripcion="El vocabulario del almacén. Primero creas la categoría («Envases», «Frutas») y la unidad en que se mide; recién después das de alta los insumos."
        :coleccion="$categorias"
        :columnas="4"
        vacio="Ninguna categoría coincide con el filtro.">

        <x-slot:acciones>
            <button type="button" data-abrir="modalCategoria"
                class="px-4 py-2.5 rounded-xl bg-spark-dark hover:bg-black text-spark-lime font-black text-[11px] uppercase tracking-wider whitespace-nowrap">
                <i class="fa-solid fa-plus mr-1"></i> Agregar
            </button>
        </x-slot:acciones>

        <x-slot:filtros>
            <form method="GET" action="{{ route('produccion.categorias.index') }}" class="flex flex-wrap items-center gap-2">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                    <input type="search" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar categoría..."
                        class="w-52 pl-8 pr-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-spark-lime">
                </div>
                <select name="estado" class="py-2 px-3 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800">
                    <option value="">Todas</option>
                    <option value="activas" @selected(request('estado') === 'activas')>Solo activas</option>
                    <option value="inactivas" @selected(request('estado') === 'inactivas')>Solo inactivas</option>
                </select>
                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-spark-lime font-bold text-[10px] uppercase">Filtrar</button>
                @if(request('buscar') || request('estado'))
                <a href="{{ route('produccion.categorias.index') }}" class="text-[11px] font-bold text-slate-500 underline">Quitar filtros</a>
                @endif
            </form>
        </x-slot:filtros>

        <x-slot:encabezados>
            <th class="text-left py-3 px-4 font-bold">Categoría</th>
            <th class="text-left py-3 px-4 font-bold">Descripción</th>
            <th class="text-right py-3 px-4 font-bold">En uso</th>
            <th class="text-right py-3 px-4 font-bold">Acciones</th>
        </x-slot:encabezados>

        @foreach($categorias as $categoria)
        <tr class="border-b border-slate-50 hover:bg-slate-50/60 transition">
            <td class="py-3 px-4 font-mono text-slate-400">{{ $categoria->id }}</td>
            <td class="py-3 px-4 font-bold {{ $categoria->is_active ? 'text-slate-800' : 'text-slate-400 line-through' }}">
                {{ $categoria->name }}
            </td>
            <td class="py-3 px-4 text-slate-500">{{ $categoria->description ?: '—' }}</td>
            <td class="py-3 px-4 text-right text-slate-600">
                {{ $categoria->supplies_count }} insumo(s) · {{ $categoria->products_count }} producto(s)
            </td>
            <td class="py-3 px-4">
                <div class="flex justify-end gap-1.5">
                    <button type="button"
                        data-editar-categoria
                        data-id="{{ $categoria->id }}"
                        data-nombre="{{ $categoria->name }}"
                        data-descripcion="{{ $categoria->description }}"
                        data-activa="{{ $categoria->is_active ? '1' : '0' }}"
                        class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10px] uppercase">
                        Editar
                    </button>
                    <form action="{{ route('produccion.categorias.destroy', $categoria) }}" method="POST"
                        onsubmit="return confirm('¿Eliminar la categoría {{ $categoria->name }}?');">
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

    <x-tabla
        titulo="Unidades de medida"
        descripcion="Se ofrecen al guardar un insumo o un producto. La abreviatura es la que se ve en el almacén y en las recetas."
        :coleccion="$unidades"
        :columnas="4"
        vacio="Ninguna unidad coincide con el filtro.">

        <x-slot:acciones>
            <button type="button" data-abrir="modalUnidad"
                class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-black text-spark-lime font-black text-[11px] uppercase tracking-wider whitespace-nowrap">
                <i class="fa-solid fa-plus mr-1"></i> Agregar
            </button>
        </x-slot:acciones>

        <x-slot:filtros>
            <form method="GET" action="{{ route('produccion.categorias.index') }}" class="flex flex-wrap items-center gap-2">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                    <input type="search" name="buscar_unidad" value="{{ request('buscar_unidad') }}" placeholder="Buscar unidad..."
                        class="w-52 pl-8 pr-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-spark-lime">
                </div>
                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-spark-lime font-bold text-[10px] uppercase">Filtrar</button>
                @if(request('buscar_unidad'))
                <a href="{{ route('produccion.categorias.index') }}" class="text-[11px] font-bold text-slate-500 underline">Quitar filtro</a>
                @endif
            </form>
        </x-slot:filtros>

        <x-slot:encabezados>
            <th class="text-left py-3 px-4 font-bold">Unidad</th>
            <th class="text-left py-3 px-4 font-bold">Abreviatura</th>
            <th class="text-right py-3 px-4 font-bold">En uso</th>
            <th class="text-right py-3 px-4 font-bold">Acciones</th>
        </x-slot:encabezados>

        @foreach($unidades as $unidad)
        <tr class="border-b border-slate-50 hover:bg-slate-50/60 transition">
            <td class="py-3 px-4 font-mono text-slate-400">{{ $unidad->id }}</td>
            <td class="py-3 px-4 font-bold text-slate-800">{{ $unidad->name }}</td>
            <td class="py-3 px-4 font-mono text-slate-500">{{ $unidad->abbreviation }}</td>
            <td class="py-3 px-4 text-right text-slate-600">
                {{ $unidad->supplies_count }} insumo(s) · {{ $unidad->products_count }} producto(s)
            </td>
            <td class="py-3 px-4">
                <div class="flex justify-end gap-1.5">
                    <button type="button"
                        data-editar-unidad
                        data-id="{{ $unidad->id }}"
                        data-nombre="{{ $unidad->name }}"
                        data-abreviatura="{{ $unidad->abbreviation }}"
                        class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10px] uppercase">
                        Editar
                    </button>
                    <form action="{{ route('produccion.categorias.unidades.destroy', $unidad) }}" method="POST"
                        onsubmit="return confirm('¿Eliminar la unidad {{ $unidad->name }}?');">
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

{{-- Modal: crear o editar categoría --}}
<div id="modalCategoria" hidden class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 shadow-xl">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900" data-titulo>Crear categoría</h3>
                <p class="text-[11px] text-slate-500">Ejemplos: Lácteo base, Envases, Frutas, Cultivos, Empaque.</p>
            </div>
            <button type="button" data-cerrar class="text-slate-400 hover:text-slate-700 text-lg leading-none">&times;</button>
        </div>

        <form method="POST" action="{{ route('produccion.categorias.store') }}" class="space-y-3 text-xs" data-formulario
            data-url-crear="{{ route('produccion.categorias.store') }}"
            data-url-editar="{{ route('produccion.categorias.update', ['categoria' => '__ID__']) }}">
            @csrf
            <input type="hidden" name="_method" value="POST" data-metodo>
            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Nombre</label>
                <input type="text" name="name" required maxlength="80" placeholder="Ej. Envases" data-campo="name"
                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800 focus:bg-white focus:ring-2 focus:ring-spark-lime">
            </div>
            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Descripción (opcional)</label>
                <input type="text" name="description" maxlength="255" data-campo="description"
                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 focus:bg-white focus:ring-2 focus:ring-spark-lime">
            </div>
            <label class="flex items-center gap-2 text-[11px] font-bold text-slate-600" data-solo-edicion>
                <input type="checkbox" name="is_active" value="1" data-campo="is_active" checked>
                Categoría activa (aparece al dar de alta insumos y productos)
            </label>
            <div class="flex gap-2 pt-2">
                <button type="button" data-cerrar class="flex-1 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] uppercase">Cancelar</button>
                <button type="submit" class="flex-1 bg-spark-dark hover:bg-black text-spark-lime font-black py-2.5 rounded-xl text-[11px] uppercase tracking-wider">Guardar</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal: crear o editar unidad --}}
<div id="modalUnidad" hidden class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50">
    <div class="bg-white w-full max-w-md rounded-3xl p-6 shadow-xl">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900" data-titulo>Crear unidad de medida</h3>
                <p class="text-[11px] text-slate-500">La abreviatura es la que se ve en el almacén y en las recetas.</p>
            </div>
            <button type="button" data-cerrar class="text-slate-400 hover:text-slate-700 text-lg leading-none">&times;</button>
        </div>

        <form method="POST" action="{{ route('produccion.categorias.unidades.store') }}" class="space-y-3 text-xs" data-formulario
            data-url-crear="{{ route('produccion.categorias.unidades.store') }}"
            data-url-editar="{{ route('produccion.categorias.unidades.update', ['unidad' => '__ID__']) }}">
            @csrf
            <input type="hidden" name="_method" value="POST" data-metodo>
            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Nombre</label>
                <input type="text" name="name" required maxlength="50" placeholder="Ej. Baldes de 20 litros" data-campo="name"
                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
            </div>
            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Abreviatura</label>
                <input type="text" name="abbreviation" required maxlength="10" placeholder="balde" data-campo="abbreviation"
                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
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
    function formularioDe(modal) {
        return modal.querySelector('[data-formulario]');
    }

    function abrir(modal, titulo, valores, id) {
        const form = formularioDe(modal);

        modal.querySelector('[data-titulo]').textContent = titulo;
        form.action = id ? form.dataset.urlEditar.replace('__ID__', id) : form.dataset.urlCrear;
        form.querySelector('[data-metodo]').value = id ? 'PUT' : 'POST';

        form.querySelectorAll('[data-campo]').forEach(function (campo) {
            if (campo.type === 'checkbox') {
                campo.checked = id ? valores[campo.dataset.campo] === '1' : true;
            } else {
                campo.value = valores[campo.dataset.campo] ?? '';
            }
        });

        // Una categoría nace activa: el interruptor solo tiene sentido al editar.
        form.querySelectorAll('[data-solo-edicion]').forEach(function (bloque) {
            bloque.hidden = !id;
        });

        modal.hidden = false;
        const primero = form.querySelector('[data-campo]');
        if (primero) { primero.focus(); }
    }

    function cerrar(modal) {
        if (modal) { modal.hidden = true; }
    }

    document.querySelectorAll('[data-abrir]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            const modal = document.getElementById(boton.dataset.abrir);
            const titulo = modal.id === 'modalUnidad' ? 'Crear unidad de medida' : 'Crear categoría';
            abrir(modal, titulo, {}, null);
        });
    });

    document.querySelectorAll('[data-cerrar]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            cerrar(boton.closest('[id^="modal"]'));
        });
    });

    document.querySelectorAll('[id^="modal"]').forEach(function (modal) {
        modal.addEventListener('click', function (evento) {
            if (evento.target === modal) { cerrar(modal); }
        });
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            document.querySelectorAll('[id^="modal"]').forEach(cerrar);
        }
    });

    document.querySelectorAll('[data-editar-categoria]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            abrir(document.getElementById('modalCategoria'), 'Editar categoría', {
                name: boton.dataset.nombre,
                description: boton.dataset.descripcion,
                is_active: boton.dataset.activa,
            }, boton.dataset.id);
        });
    });

    document.querySelectorAll('[data-editar-unidad]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            abrir(document.getElementById('modalUnidad'), 'Editar unidad de medida', {
                name: boton.dataset.nombre,
                abbreviation: boton.dataset.abreviatura,
            }, boton.dataset.id);
        });
    });
})();
</script>
@endsection
