@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-slate-200">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('pagos.ruta.index') }}" class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Historial de Pagos de Sobres</h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                    Comprobantes Emitidos
                </span>
            </div>
            <p class="text-sm text-slate-500 mt-1">
                Registro histórico de sobres en efectivo entregados a productores en Huata.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('pagos.ruta.index') }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-bold rounded-xl shadow-sm transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Planilla de Pagos Activa
            </a>
        </div>
    </div>

    <!-- Filtros y Buscador -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4">
        <form method="GET" action="{{ route('pagos.ruta.history') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Buscar Productor / DNI</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Nombre o DNI..." 
                           class="w-full pl-9 pr-3 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:bg-white transition">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1">Zona</label>
                <select name="zone_id" class="w-full py-2 px-3 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-emerald-500 focus:bg-white transition">
                    <option value="">Todas las Zonas</option>
                    @foreach($zones as $z)
                        <option value="{{ $z->id }}" {{ (string)$zoneId === (string)$z->id ? 'selected' : '' }}>{{ $z->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="w-full py-2 px-4 bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold rounded-xl shadow-sm transition">
                    Filtrar Historial
                </button>
                @if($search || $zoneId)
                    <a href="{{ route('pagos.ruta.history') }}" class="py-2 px-3 bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-semibold rounded-xl transition">
                        Limpiar
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabla Histórica de Liquidaciones Pagadas -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-slate-900 text-sm">Comprobantes de Pago Registrados</h3>
            <span class="text-xs text-slate-500 font-semibold">{{ $settlements->total() }} pagos registrados</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-slate-50/75 text-[11px] font-bold text-slate-500 uppercase tracking-wider border-b border-slate-100">
                        <th class="py-3 px-4">Folio / Fecha</th>
                        <th class="py-3 px-4">Productor</th>
                        <th class="py-3 px-4">Zona</th>
                        <th class="py-3 px-4 text-center">Litros</th>
                        <th class="py-3 px-4 text-right">Bruto</th>
                        <th class="py-3 px-4 text-right">Deducciones</th>
                        <th class="py-3 px-4 text-right">Neto Pagado</th>
                        <th class="py-3 px-4 text-center">Pagador</th>
                        <th class="py-3 px-4 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($settlements as $settlement)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="py-3 px-4">
                                <span class="font-mono text-xs font-bold text-slate-900 block">#SOBRE-{{ str_pad($settlement->id, 5, '0', STR_PAD_LEFT) }}</span>
                                <span class="text-[11px] text-slate-500">
                                    {{ $settlement->paid_at ? \Carbon\Carbon::parse($settlement->paid_at)->format('d/m/Y H:i') : \Carbon\Carbon::parse($settlement->updated_at)->format('d/m/Y H:i') }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-900">{{ $settlement->producer->name }}</div>
                                <div class="text-xs text-slate-500">DNI: {{ $settlement->producer->dni ?? 'N/A' }}</div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700">
                                    {{ $settlement->producer->zone?->name ?? 'Sin Zona' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center font-semibold text-slate-800">
                                {{ number_format($settlement->total_liters, 2) }} L
                            </td>
                            <td class="py-3 px-4 text-right text-slate-600">
                                S/ {{ number_format($settlement->gross_total, 2) }}
                            </td>
                            <td class="py-3 px-4 text-right">
                                @if($settlement->deductions_total > 0)
                                    <span class="text-rose-600 font-semibold">- S/ {{ number_format($settlement->deductions_total, 2) }}</span>
                                @else
                                    <span class="text-slate-400 font-normal">S/ 0.00</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                <span class="text-base font-black text-emerald-700">
                                    S/ {{ number_format($settlement->net_total, 2) }}
                                </span>
                                <span class="block text-[10px] text-emerald-600 font-bold uppercase">Sobre Entregado</span>
                            </td>
                            <td class="py-3 px-4 text-center text-xs text-slate-600">
                                {{ $settlement->payer->name ?? 'Pagador' }}
                            </td>

                            <td class="py-3 px-4 text-center">
                                <a href="{{ route('pagos.ruta.receipt', $settlement) }}" 
                                   class="inline-flex items-center px-3 py-1 bg-white border border-slate-300 rounded-lg text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-sm">
                                    <svg class="w-3.5 h-3.5 mr-1 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    Ver Recibo
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-400">
                                <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="text-sm font-semibold">No se encontraron pagos registrados con los filtros aplicados.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($settlements->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $settlements->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
