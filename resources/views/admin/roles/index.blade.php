@extends('layouts.app')

@section('title', 'Roles')

@section('content')
<div class="space-y-6">

    <x-tabla
        titulo="Roles"
        descripcion="Qué hace cada rol y cuánta gente lo tiene. Se asignan al crear o editar a alguien en Usuarios. No se crean roles nuevos desde aquí: cada uno está cableado a los permisos de las pantallas, y uno inventado dejaría a esa persona sin poder entrar a nada."
        :coleccion="$roles"
        :columnas="5"
        vacio="Ningún rol coincide con el filtro.">

        <x-slot:acciones>
            <a href="{{ route('admin.usuarios.index') }}"
                class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] uppercase tracking-wider whitespace-nowrap">
                Ir a Usuarios
            </a>
        </x-slot:acciones>

        <x-slot:filtros>
            <form method="GET" action="{{ route('admin.roles.index') }}" class="flex flex-wrap items-center gap-2">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                    <input type="search" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar rol..."
                        class="w-52 pl-8 pr-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-spark-lime">
                </div>
                <select name="estado" class="py-2 px-3 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800">
                    <option value="">Todos</option>
                    <option value="con_gente" @selected(request('estado') === 'con_gente')>Con gente asignada</option>
                    <option value="sin_gente" @selected(request('estado') === 'sin_gente')>Sin nadie</option>
                </select>
                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-spark-lime font-bold text-[10px] uppercase">Filtrar</button>
                @if(request('buscar') || request('estado'))
                <a href="{{ route('admin.roles.index') }}" class="text-[11px] font-bold text-slate-500 underline">Quitar filtros</a>
                @endif
            </form>
        </x-slot:filtros>

        <x-slot:encabezados>
            <th class="text-left py-3 px-4 font-bold">Rol</th>
            <th class="text-left py-3 px-4 font-bold">Qué hace</th>
            <th class="text-right py-3 px-4 font-bold">Personas</th>
            <th class="text-left py-3 px-4 font-bold">Cobra como</th>
            <th class="text-right py-3 px-4 font-bold">Acciones</th>
        </x-slot:encabezados>

        @foreach($roles as $rol)
        <tr class="border-b border-slate-50 hover:bg-slate-50/60 transition">
            <td class="py-3 px-4 font-mono text-[10px] text-slate-400">{{ $rol['clave'] }}</td>
            <td class="py-3 px-4 font-bold text-slate-800">{{ $rol['etiqueta'] }}</td>
            <td class="py-3 px-4 text-slate-500 max-w-md">{{ $rol['descripcion'] }}</td>
            <td class="py-3 px-4 text-right font-black text-slate-800">
                {{ $rol['total'] }}
                @if($rol['total'] !== $rol['activos'])
                <span class="block text-[10px] font-medium text-amber-600">{{ $rol['activos'] }} activos</span>
                @endif
            </td>
            <td class="py-3 px-4">
                @if($rol['tipo_cliente'])
                <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-blue-100 text-blue-900">
                    {{ $rol['tipo_cliente']->name }}
                </span>
                @else
                <span class="text-slate-400">—</span>
                @endif
            </td>
            <td class="py-3 px-4 text-right">
                <a href="{{ route('admin.usuarios.index', ['rol' => $rol['clave']]) }}"
                    class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10px] uppercase">
                    Ver quiénes son
                </a>
            </td>
        </tr>
        @endforeach
    </x-tabla>

</div>
@endsection
