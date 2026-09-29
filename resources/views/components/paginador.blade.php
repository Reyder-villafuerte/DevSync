{{--
    El pie de paginado del sistema: «Mostrando X–Y de Z» con Anterior y
    Siguiente. Vive aparte de <x-tabla> para poder usarlo también en las
    pantallas que todavía arman su tabla a mano.
--}}
@props(['coleccion'])

@php
    $paginado = $coleccion instanceof \Illuminate\Contracts\Pagination\Paginator
        || $coleccion instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
@endphp

@if($paginado)
<div class="px-6 py-3 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <span class="text-[11px] text-slate-500">
        @if($coleccion->total() > 0)
        Mostrando <strong class="text-slate-700">{{ $coleccion->firstItem() }}–{{ $coleccion->lastItem() }}</strong>
        de <strong class="text-slate-700">{{ $coleccion->total() }}</strong>
        @else
        Sin resultados
        @endif
    </span>

    <div class="flex items-center gap-2">
        @if($coleccion->onFirstPage())
        <span class="px-3 py-1.5 rounded-lg bg-slate-50 text-slate-300 font-bold text-[10px] uppercase">Anterior</span>
        @else
        <a href="{{ $coleccion->previousPageUrl() }}"
            class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10px] uppercase">Anterior</a>
        @endif

        <span class="text-[11px] text-slate-400">
            Página {{ $coleccion->currentPage() }} de {{ max(1, $coleccion->lastPage()) }}
        </span>

        @if($coleccion->hasMorePages())
        <a href="{{ $coleccion->nextPageUrl() }}"
            class="px-3 py-1.5 rounded-lg bg-spark-dark hover:bg-black text-spark-lime font-bold text-[10px] uppercase">Siguiente</a>
        @else
        <span class="px-3 py-1.5 rounded-lg bg-slate-50 text-slate-300 font-bold text-[10px] uppercase">Siguiente</span>
        @endif
    </div>
</div>
@endif
