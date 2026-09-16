@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-8">
    
    <div class="huata-welcome"><div><p class="huata-eyebrow">NUESTRA COMUNIDAD · HUATA</p><h1>Panel del día</h1><p>¡Qué gusto verte, {{ $user->name }}!</p></div><span>{{ now()->format('d/m/Y') }}</span></div>
    <div class="huata-metrics">
        <article class="huata-metric"><div><p>Leche en planta</p><strong>{{ number_format($stockLeche, 1) }} <small>L</small></strong><span>Stock verificado</span></div><span class="huata-drawing milk" aria-hidden="true"></span></article>
        <article class="huata-metric"><div><p>Quesos en almacén</p><strong>{{ (int)$stockQueso }}</strong><span>Moldes disponibles</span></div><span class="huata-drawing cheese" aria-hidden="true"></span></article>
        @isset($totalProductores)<article class="huata-metric"><div><p>Productores</p><strong>{{ $totalProductores }}</strong><span>Nuestra comunidad</span></div><span class="huata-drawing people" aria-hidden="true"></span></article>@endisset
        @isset($litrosAcopiadosHoy)<article class="huata-metric"><div><p>Acopio de hoy</p><strong>{{ number_format($litrosAcopiadosHoy, 1) }} <small>L</small></strong><span>Leche registrada hoy</span></div><span class="huata-drawing ledger" aria-hidden="true"></span></article>@endisset
    </div>
    <!-- SECCIÓN OPERATIVA SEGÚN ROL (VISTAS ESPECIALIZADAS) -->

    @if($user->role === 'productor')
    <!-- PANEL PRODUCTOR ESTILO SPARK -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm">
            <h3 class="text-base font-bold text-slate-900 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-bottle-water text-spark-limeText"></i> Registro Diario de Leche Entregada
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="p-3 rounded-l-xl">Fecha</th>
                            <th class="p-3">Litros</th>
                            <th class="p-3">Hora Salida</th>
                            <th class="p-3">Zona</th>
                            <th class="p-3 rounded-r-xl">Notas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($misEntregas as $entrega)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="p-3 font-semibold text-slate-800">{{ $entrega->route->date }}</td>
                            <td class="p-3 font-black text-emerald-700 text-sm">{{ $entrega->liters }} L</td>
                            <td class="p-3 text-slate-500">{{ $entrega->collected_at ?: '05:00 AM' }}</td>
                            <td class="p-3 font-medium text-slate-600">{{ $entrega->route->zone->name }}</td>
                            <td class="p-3 text-slate-400 italic">{{ $entrega->notes ?: 'Sin novedad' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="p-6 text-center text-slate-400">Sin entregas registradas aún.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-6">
            <!-- Solicitud de cambio de zona con estilo Spark -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm">
                <h4 class="font-bold text-slate-900 text-sm mb-1 flex items-center gap-2">
                    <i class="fa-solid fa-arrows-rotate text-amber-500"></i> Solicitar Cambio de Zona
                </h4>
                <p class="text-[11px] text-slate-500 mb-4">Si rotas a otro sector de Huata, envía solicitud al Admin.</p>
                
                <form action="{{ route('zonas.request-change') }}" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">Zona Destino</label>
                        <select name="requested_zone_id" required class="w-full text-xs p-2.5 bg-slate-50 border rounded-xl focus:ring-2 focus:ring-spark-lime">
                            @foreach($zonasDisponibles as $z)
                                @if(!$user->zone_id || $user->zone_id != $z->id)
                                <option value="{{ $z->id }}">{{ $z->name }}</option>
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">Motivo</label>
                        <textarea name="reason" rows="2" placeholder="Ej. Rotación de pastos a sector Joche" class="w-full text-xs p-2 bg-slate-50 border rounded-xl"></textarea>
                    </div>
                    <button type="submit" class="w-full bg-spark-dark hover:bg-black text-spark-lime font-bold py-2 rounded-xl text-xs transition shadow-sm">
                        Enviar Solicitud
                    </button>
                </form>
            </div>

            <!-- Lactoscan Card -->
            <div class="bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm">
                <h4 class="font-bold text-slate-900 text-sm mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-microscope text-purple-600"></i> Pruebas de Calidad Recientes
                </h4>
                @forelse($misAnalisis as $ana)
                <div class="p-3 mb-2 rounded-2xl border {{ $ana->verdict === 'conforme' ? 'bg-emerald-50/60 border-emerald-200' : 'bg-rose-50/60 border-rose-200' }}">
                    <div class="flex justify-between items-center text-xs font-bold">
                        <span class="text-slate-700">{{ $ana->analysis_date }}</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] uppercase font-black {{ $ana->verdict === 'conforme' ? 'bg-emerald-200 text-emerald-900' : 'bg-rose-200 text-rose-900' }}">
                            {{ str_replace('_', ' ', $ana->verdict) }}
                        </span>
                    </div>
                    <div class="grid grid-cols-3 gap-1 mt-2 text-[10px] text-slate-600 font-semibold">
                        <div>Grasa: <strong class="text-slate-900">{{ $ana->fat_percentage }}%</strong></div>
                        <div>Dens: <strong class="text-slate-900">{{ $ana->density }}</strong></div>
                        <div>Acidez: <strong class="text-slate-900">{{ $ana->ph_or_acidity }}</strong></div>
                    </div>
                </div>
                @empty
                <p class="text-xs text-slate-400">Sin pruebas recientes.</p>
                @endforelse
            </div>
        </div>
    </div>

    @elseif($user->role === 'acopiador')
    <!-- PANEL ACOPIADOR ESTILO SPARK -->
    <div class="bg-white p-8 rounded-3xl border border-slate-200/80 shadow-sm">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <span class="text-[10px] uppercase font-bold text-spark-limeText bg-lime-100 px-2.5 py-1 rounded-full">Turno Matutino 4:30 AM</span>
                <h3 class="text-xl font-extrabold text-slate-900 mt-1">Ruta de Acopio Huata</h3>
                <p class="text-xs text-slate-500">Recorre tu lista asignada de proveedores y anota los litros entregados.</p>
            </div>
            <a href="{{ route('acopio.index') }}" class="bg-spark-dark hover:bg-black text-spark-lime font-bold px-5 py-2.5 rounded-xl text-xs transition shadow-sm">
                Abrir Planilla de Campo
            </a>
        </div>

        @if($miRutaHoy)
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                <span class="text-[10px] font-bold text-slate-400 uppercase block">Zona Asignada</span>
                <span class="text-sm font-black text-slate-800">{{ $miRutaHoy->zone->name }}</span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                <span class="text-[10px] font-bold text-slate-400 uppercase block">Proveedores en Lista</span>
                <span class="text-sm font-black text-slate-800">{{ $miRutaHoy->zone->producers->count() }} productores</span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                <span class="text-[10px] font-bold text-slate-400 uppercase block">Litros Anotados</span>
                <span class="text-xl font-black text-emerald-700">{{ $miRutaHoy->total_collected_liters }} L</span>
            </div>
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100">
                <span class="text-[10px] font-bold text-slate-400 uppercase block">Estado</span>
                <span class="text-xs font-bold uppercase text-spark-dark bg-spark-lime px-2 py-0.5 rounded-full inline-block mt-1">{{ $miRutaHoy->status }}</span>
            </div>
        </div>
        @endif
    </div>

    @else
    <!-- VISTA DASHBOARD GENERAL PARA ADMIN, PLANTA, VENTAS Y CALIDAD -->

    @if(isset($solicitudesZonaPendientes) && $solicitudesZonaPendientes->count() > 0)
    <!-- SOLICITUDES DE CAMBIO DE ZONA PENDIENTES (ALERTA Y APROBACIÓN INMEDIATA) -->
    <div class="bg-white rounded-3xl p-6 border-2 border-amber-500/30 shadow-[0_4px_20px_rgba(0,0,0,0.04)] mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4 mb-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-amber-500 text-white flex items-center justify-center text-lg shadow-sm">
                    <i class="fa-solid fa-arrows-split-up-and-left"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-base font-black text-slate-900">Solicitudes de Rotación de Zona Pendientes</h3>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-300">
                            {{ $solicitudesZonaPendientes->count() }} por revisar
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Productores que solicitan traslado de ganado entre sectores de Huata.</p>
                </div>
            </div>
            <a href="{{ route('zonas.solicitudes') }}" class="text-xs font-bold text-[#0f1713] hover:text-emerald-700 inline-flex items-center gap-1.5 transition">
                <span>Gestionar Todas</span>
                <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-slate-400 font-bold uppercase tracking-wider text-[10px] border-b border-slate-100">
                        <th class="pb-3 px-3">Fecha</th>
                        <th class="pb-3 px-3">Productor</th>
                        <th class="pb-3 px-3">Zona Origen</th>
                        <th class="pb-3 px-3">Zona Destino</th>
                        <th class="pb-3 px-3">Motivo</th>
                        <th class="pb-3 px-3 text-right">Decisión Rápida</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($solicitudesZonaPendientes as $req)
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-3 px-3 text-slate-500 font-medium whitespace-nowrap">{{ $req->created_at->format('d/m/Y') }}</td>
                        <td class="py-3 px-3 font-bold text-slate-900">{{ $req->producer->name }}</td>
                        <td class="py-3 px-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-700">
                                {{ $req->currentZone->name }}
                            </span>
                        </td>
                        <td class="py-3 px-3">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#bef264]/40 text-[#0f1713] border border-[#bef264]/60">
                                {{ $req->requestedZone->name }}
                            </span>
                        </td>
                        <td class="py-3 px-3 text-slate-600 max-w-xs truncate">{{ $req->reason ?: 'Sin motivo detallado' }}</td>
                        <td class="py-3 px-3 text-right whitespace-nowrap">
                            <form action="{{ route('zonas.review-request', $req->id) }}" method="POST" class="inline-flex items-center gap-2">
                                @csrf
                                <button type="submit" name="decision" value="aprobado" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-[#0f1713] hover:bg-slate-900 text-[#bef264] transition shadow-sm inline-flex items-center gap-1">
                                    <i class="fa-solid fa-check text-[10px]"></i> Aprobar
                                </button>
                                <button type="submit" name="decision" value="rechazado" class="px-3 py-1.5 rounded-xl text-xs font-bold bg-rose-50 hover:bg-rose-100 text-rose-700 transition inline-flex items-center gap-1">
                                    <i class="fa-solid fa-xmark text-[10px]"></i> Rechazar
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <a href="{{ route('acopio.index') }}" class="group bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm hover:border-spark-lime hover:shadow-md transition">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 group-hover:bg-spark-dark group-hover:text-spark-lime text-slate-700 flex items-center justify-center text-xl transition">
                    <i class="fa-solid fa-truck-fast"></i>
                </div>
                <div>
                    <h4 class="font-bold text-slate-900 text-sm group-hover:text-emerald-900 transition">Acopio 4:30 AM</h4>
                    <p class="text-xs text-slate-500">Planillas de 5 acopiadores en las 4 zonas.</p>
                </div>
            </div>
        </a>

        <a href="{{ route('planta.verificacion') }}" class="group bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm hover:border-spark-lime hover:shadow-md transition">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 group-hover:bg-spark-dark group-hover:text-spark-lime text-slate-700 flex items-center justify-center text-xl transition">
                    <i class="fa-solid fa-water"></i>
                </div>
                <div>
                    <h4 class="font-bold text-slate-900 text-sm group-hover:text-emerald-900 transition">Caudalímetro de Planta</h4>
                    <p class="text-xs text-slate-500">Verificación y contraste de leche descargada.</p>
                </div>
            </div>
        </a>

        <a href="{{ route('produccion.index') }}" class="group bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm hover:border-spark-lime hover:shadow-md transition">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 group-hover:bg-spark-dark group-hover:text-spark-lime text-slate-700 flex items-center justify-center text-xl transition">
                    <i class="fa-solid fa-cheese"></i>
                </div>
                <div>
                    <h4 class="font-bold text-slate-900 text-sm group-hover:text-emerald-900 transition">Quesería (-10L/molde)</h4>
                    <p class="text-xs text-slate-500">Transformación y control de lotes.</p>
                </div>
            </div>
        </a>

        <a href="{{ route('ventas.index') }}" class="group bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm hover:border-spark-lime hover:shadow-md transition">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 group-hover:bg-spark-dark group-hover:text-spark-lime text-slate-700 flex items-center justify-center text-xl transition">
                    <i class="fa-solid fa-cash-register"></i>
                </div>
                <div>
                    <h4 class="font-bold text-slate-900 text-sm group-hover:text-emerald-900 transition">Ventas & Recibos</h4>
                    <p class="text-xs text-slate-500">Tarifas S/18, S/19 y S/20 (Solo Efectivo).</p>
                </div>
            </div>
        </a>

        <a href="{{ route('calidad.index') }}" class="group bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm hover:border-spark-lime hover:shadow-md transition">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 group-hover:bg-spark-dark group-hover:text-spark-lime text-slate-700 flex items-center justify-center text-xl transition">
                    <i class="fa-solid fa-microscope"></i>
                </div>
                <div>
                    <h4 class="font-bold text-slate-900 text-sm group-hover:text-emerald-900 transition">Calidad Lactoscan</h4>
                    <p class="text-xs text-slate-500">Análisis y agendamiento de visitas técnicas.</p>
                </div>
            </div>
        </a>

        <a href="{{ route('zonas.index') }}" class="group bg-white p-6 rounded-3xl border border-slate-200/80 shadow-sm hover:border-spark-lime hover:shadow-md transition">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 group-hover:bg-spark-dark group-hover:text-spark-lime text-slate-700 flex items-center justify-center text-xl transition">
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>
                <div>
                    <h4 class="font-bold text-slate-900 text-sm group-hover:text-emerald-900 transition">Zonas Huata 1 a 4</h4>
                    <p class="text-xs text-slate-500">Mapeo y aprobación de rotación de pastoreo.</p>
                </div>
            </div>
        </a>
    </div>
    @endif
</div>
@endsection
