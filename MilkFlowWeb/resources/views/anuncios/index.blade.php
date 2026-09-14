@extends('layouts.app')

@section('title', 'Anuncios y Comunicados')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-[#0f1713] text-[#bef264] flex items-center justify-center text-xl shadow-sm">
                <i class="fa-solid fa-bullhorn"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">Anuncios y Avisos del Sistema</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#bef264]/30 text-[#0f1713] border border-[#bef264]/50 uppercase tracking-wide">Comunicación Interna</span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Difusión de comunicados oficiales mostrados automáticamente en la pantalla de bienvenida y login durante el rango de fechas programado.
                </p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Formulario nuevo anuncio -->
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
            <div class="flex items-center gap-2 mb-5">
                <div class="w-7 h-7 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-plus"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-900">Publicar Nuevo Aviso</h3>
            </div>

            <form action="{{ route('anuncios.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Título del Anuncio</label>
                    <input type="text" name="title" required placeholder="Ej. Cambio de horario o ruta de acopio" class="w-full text-xs p-3 rounded-2xl border border-slate-200 bg-slate-50/50 font-bold focus:bg-white focus:border-[#0f1713] focus:ring-0 transition">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Mensaje Detallado</label>
                    <textarea name="message" rows="3" required placeholder="Escribe las instrucciones para los usuarios destinatarios..." class="w-full text-xs p-3 rounded-2xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#0f1713] focus:ring-0 transition"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">Fecha Inicio</label>
                        <input type="date" name="start_date" value="{{ date('Y-m-d') }}" required class="w-full text-xs p-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#0f1713] focus:ring-0">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 uppercase tracking-wider mb-1">Fecha Fin</label>
                        <input type="date" name="end_date" value="{{ date('Y-m-d', strtotime('+7 days')) }}" required class="w-full text-xs p-2.5 rounded-xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#0f1713] focus:ring-0">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">Dirigido a Rol</label>
                    <select name="target_role" id="targetRoleSelect" class="w-full text-xs p-3 rounded-2xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#0f1713] focus:ring-0 transition">
                        <option value="">Todos los usuarios del sistema</option>
                        <option value="acopiador">Solo Acopiadores</option>
                        <option value="productor">Solo Productores / Proveedores</option>
                        <option value="jefe_produccion">Solo Planta y Producción</option>
                        <option value="personal_venta">Solo Ventas y Despacho</option>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1.5">O a un Acopiador Específico</label>
                    <select name="target_user_id" class="w-full text-xs p-3 rounded-2xl border border-slate-200 bg-slate-50/50 focus:bg-white focus:border-[#0f1713] focus:ring-0 transition">
                        <option value="">(Ninguno en específico / aplicar por rol)</option>
                        @foreach($collectors as $col)
                            <option value="{{ $col->id }}">Acopiador: {{ $col->name }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="w-full bg-[#0f1713] hover:bg-slate-900 text-[#bef264] font-bold py-3 rounded-2xl text-xs transition shadow-md flex items-center justify-center gap-2">
                    <i class="fa-solid fa-paper-plane"></i> Publicar Comunicado
                </button>
            </form>
        </div>

        <!-- Lista de anuncios -->
        <div class="lg:col-span-2 bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-list-check"></i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-900">Historial de Anuncios Publicados</h3>
                    </div>
                    <span class="text-xs text-slate-400 font-medium">Total: {{ $announcements->total() }}</span>
                </div>

                <div class="space-y-3">
                    @forelse($announcements as $an)
                    <div class="p-5 rounded-2xl border border-slate-100 hover:border-slate-300 transition bg-slate-50/40">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold font-mono bg-slate-200 text-slate-700">
                                    {{ $an->start_date }} al {{ $an->end_date }}
                                </span>
                                <h4 class="font-bold text-slate-900 text-sm mt-1.5">{{ $an->title }}</h4>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-[#bef264]/40 text-[#0f1713] border border-[#bef264]/60 whitespace-nowrap">
                                {{ $an->target_role ?: ($an->targetUser ? 'Usuario: ' . $an->targetUser->name : 'Para Todos') }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 mt-2 leading-relaxed">{{ $an->message }}</p>
                    </div>
                    @empty
                    <div class="p-8 text-center text-slate-400 font-medium">No hay anuncios configurados.</div>
                    @endforelse
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100">
                {{ $announcements->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
