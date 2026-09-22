{{--
    El patrón de tabla del sistema.

    Envuelve una tabla con su encabezado, su barra de búsqueda y filtros, y el
    paginado. Se usa igual en todas las pantallas para que el administrador no
    tenga que aprender una forma distinta en cada módulo.

    @param  string       $titulo
    @param  string|null  $descripcion
    @param  mixed        $coleccion   paginador o colección que se está listando
    @param  string|null  $vacio       qué decir cuando no hay filas
    @param  int          $columnas    cuántas columnas tiene la tabla (para la fila vacía)
--}}
@props([
    'titulo',
    'descripcion' => null,
    'coleccion' => null,
    'vacio' => 'No hay nada que mostrar todavía.',
    'columnas' => 6,
])

@php
    $paginado = $coleccion instanceof \Illuminate\Contracts\Pagination\Paginator
        || $coleccion instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
@endphp

<div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">

    <div class="p-6 flex flex-col lg:flex-row lg:items-start justify-between gap-4 border-b border-slate-100">
        <div>
            <h2 class="text-base font-bold text-slate-900">{{ $titulo }}</h2>
            @if($descripcion)
            <p class="text-[11px] text-slate-500 mt-1 max-w-2xl leading-relaxed">{{ $descripcion }}</p>
            @endif
        </div>

        @isset($acciones)
        <div class="flex flex-wrap gap-2">{{ $acciones }}</div>
        @endisset
    </div>

    @isset($filtros)
    <div class="px-6 py-3 bg-slate-50/60 border-b border-slate-100">
        {{ $filtros }}
    </div>
    @endisset

    <div class="overflow-x-auto">
        <table class="w-full text-xs">
            <thead>
                <tr class="text-[10px] uppercase text-slate-400 border-b border-slate-100 bg-white">
                    <th class="text-left py-3 px-4 font-bold w-16">ID</th>
                    {{ $encabezados }}
                </tr>
            </thead>
            <tbody>
                {{ $slot }}

                @if($coleccion !== null && count($coleccion) === 0)
                <tr>
                    <td colspan="{{ $columnas + 1 }}" class="py-8 text-center text-slate-400 italic">{{ $vacio }}</td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

    <x-paginador :coleccion="$coleccion" />

</div>
