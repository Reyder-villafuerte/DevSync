@extends('layouts.app')

@section('title', 'Catálogo del Sistema')

@section('content')
<div class="space-y-6">

    <x-tabla
        titulo="Catálogo del Sistema"
        descripcion="Todo lo que la planta tiene dado de alta, con el enlace a la pantalla donde se administra cada cosa."
        :coleccion="$fichas"
        :columnas="3"
        vacio="Todavía no hay nada cargado.">

        <x-slot:encabezados>
            <th class="text-left py-3 px-4 font-bold">Qué</th>
            <th class="text-right py-3 px-4 font-bold">Cuántos</th>
            <th class="text-left py-3 px-4 font-bold">Detalle</th>
            <th class="text-right py-3 px-4 font-bold">Acciones</th>
        </x-slot:encabezados>

        @foreach($fichas as $i => $ficha)
        <tr class="border-b border-slate-50 hover:bg-slate-50/60 transition">
            <td class="py-3 px-4 text-slate-400">{{ $i + 1 }}</td>
            <td class="py-3 px-4 font-bold text-slate-800">
                <i class="fa-solid {{ $ficha['icono'] }} text-slate-300 mr-2"></i>{{ $ficha['titulo'] }}
            </td>
            <td class="py-3 px-4 text-right font-black text-slate-900 text-sm">{{ $ficha['total'] }}</td>
            <td class="py-3 px-4 text-slate-500">{{ $ficha['detalle'] }}</td>
            <td class="py-3 px-4 text-right">
                <a href="{{ $ficha['ruta'] }}"
                    class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10px] uppercase">
                    Administrar
                </a>
            </td>
        </tr>
        @endforeach
    </x-tabla>

</div>
@endsection
