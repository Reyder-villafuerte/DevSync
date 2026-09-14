@extends('layouts.app')

@section('title', 'Configuración de Precios y Temporada')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-[#0f1713] text-[#bef264] flex items-center justify-center text-xl shadow-sm">
                <i class="fa-solid fa-tags"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">Tarifas y Precios de Temporada</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#bef264]/30 text-[#0f1713] border border-[#bef264]/50 uppercase tracking-wide">
                        {{ $currentPrice->season_name }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Configura los precios estacionales de compra de leche, penalizaciones por agua detectada en Lactoscan y venta de quesos.
                </p>
            </div>
        </div>

        <a href="{{ route('admin.pagos.autorizacion') }}" class="px-3.5 py-2 rounded-2xl bg-[#0f1713] text-[#bef264] text-xs font-bold hover:bg-slate-900 transition inline-flex items-center gap-2 shadow-sm">
            <i class="fa-solid fa-money-check-dollar"></i> Autorizar Pagos
        </a>
    </div>

    <!-- TARJETAS DE TARIFAS VIGENTES -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Leche Normal -->
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] flex flex-col justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Leche Base Conforme</span>
                <div class="flex items-baseline gap-1 mt-2">
                    <span class="text-3xl font-black text-[#0f1713]">S/ {{ number_format($currentPrice->price_milk_base, 2) }}</span>
                    <span class="text-xs font-bold text-slate-500">/ Litro</span>
                </div>
                <p class="text-xs text-slate-400 mt-2">Tarifa oficial para leche fresca óptima.</p>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-emerald-600">
                <span>Sin agua añadida</span>
                <i class="fa-solid fa-check"></i>
            </div>
        </div>

        <!-- Leche con Agua <= 5% -->
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] flex flex-col justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-amber-600">Penalidad Agua &le; 5%</span>
                <div class="flex items-baseline gap-1 mt-2">
                    <span class="text-3xl font-black text-amber-600">S/ {{ number_format($currentPrice->price_milk_water_penalty_low, 2) }}</span>
                    <span class="text-xs font-bold text-slate-500">/ Litro</span>
                </div>
                <p class="text-xs text-slate-400 mt-2">Descuento aplicado si Lactoscan detecta hasta 5% de agua.</p>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-amber-600">
                <span>Descuento leve</span>
                <i class="fa-solid fa-droplet-slash"></i>
            </div>
        </div>

        <!-- Leche con Agua > 5% -->
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] flex flex-col justify-between">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-rose-600">Penalidad Agua > 5%</span>
                <div class="flex items-baseline gap-1 mt-2">
                    <span class="text-3xl font-black text-rose-600">S/ {{ number_format($currentPrice->price_milk_water_penalty_high, 2) }}</span>
                    <span class="text-xs font-bold text-slate-500">/ Litro</span>
                </div>
                <p class="text-xs text-slate-400 mt-2">Penalidad drástica + advertencia de expulsión del padrón.</p>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-bold text-rose-600">
                <span>Riesgo expulsión</span>
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
        </div>

        <!-- Queso Proveedor -->
        <div class="bg-[#0f1713] text-white rounded-3xl p-6 shadow-md flex flex-col justify-between">
            <div>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#bef264] text-[#0f1713] uppercase tracking-wider">Venta de Quesos</span>
                <div class="mt-3 space-y-1.5 text-xs">
                    <div class="flex justify-between">
                        <span class="text-slate-400">A Proveedores:</span>
                        <span class="font-black text-[#bef264]">S/ {{ number_format($currentPrice->price_cheese_provider, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Mayoristas (&ge;10):</span>
                        <span class="font-black text-white">S/ {{ number_format($currentPrice->price_cheese_wholesale, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-400">Público Local:</span>
                        <span class="font-black text-white">S/ {{ number_format($currentPrice->price_cheese_local, 2) }}</span>
                    </div>
                </div>
            </div>
            <span class="text-[10px] text-slate-400 mt-4 block">* Descontable a cuenta de leche.</span>
        </div>
    </div>

    <!-- FORMULARIO DE CAMBIO DE PRECIOS -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] space-y-4">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-pen-to-square"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-900">Actualizar Tarifas de Temporada</h3>
            </div>

            <form action="{{ route('admin.precios.update') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Nombre de la Temporada</label>
                    <input type="text" name="season_name" value="{{ old('season_name', $currentPrice->season_name) }}" required placeholder="Ej. Temporada Seca 2026" class="w-full text-xs p-3 rounded-2xl border border-slate-200 bg-slate-50/50 font-bold focus:bg-white focus:border-[#0f1713] focus:ring-0 transition">
                </div>

                <div class="pt-2 border-t border-slate-100">
                    <span class="text-[11px] font-bold text-[#0f1713] uppercase tracking-wider block mb-2">
                        <i class="fa-solid fa-bucket text-emerald-600 mr-1"></i> Precios de Compra de Leche (S/ por Litro)
                    </span>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 mb-1">Tarifa Base (Normal sin agua)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-xs text-slate-400 font-bold">S/</span>
                                <input type="number" step="0.05" name="price_milk_base" value="{{ old('price_milk_base', $currentPrice->price_milk_base) }}" required class="w-full pl-8 pr-3 py-2.5 rounded-xl border border-slate-200 text-xs font-bold bg-slate-50/50 focus:bg-white">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 mb-1">Penalidad Agua &le; 5% (Leve)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-xs text-slate-400 font-bold">S/</span>
                                <input type="number" step="0.05" name="price_milk_water_penalty_low" value="{{ old('price_milk_water_penalty_low', $currentPrice->price_milk_water_penalty_low) }}" required class="w-full pl-8 pr-3 py-2.5 rounded-xl border border-amber-200 text-xs font-bold bg-amber-50/30 focus:bg-white">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 mb-1">Penalidad Agua > 5% (Grave / Expulsión)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-xs text-slate-400 font-bold">S/</span>
                                <input type="number" step="0.05" name="price_milk_water_penalty_high" value="{{ old('price_milk_water_penalty_high', $currentPrice->price_milk_water_penalty_high) }}" required class="w-full pl-8 pr-3 py-2.5 rounded-xl border border-rose-200 text-xs font-bold bg-rose-50/30 focus:bg-white">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100">
                    <span class="text-[11px] font-bold text-[#0f1713] uppercase tracking-wider block mb-2">
                        <i class="fa-solid fa-cheese text-amber-500 mr-1"></i> Precios de Venta de Queso (S/ por Molde)
                    </span>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 mb-1">Tarifa a Proveedores de Leche</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-xs text-slate-400 font-bold">S/</span>
                                <input type="number" step="0.50" name="price_cheese_provider" value="{{ old('price_cheese_provider', $currentPrice->price_cheese_provider) }}" required class="w-full pl-8 pr-3 py-2.5 rounded-xl border border-slate-200 text-xs font-bold bg-slate-50/50 focus:bg-white">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 mb-1">Tarifa Mayorista (&ge; 10 moldes)</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-xs text-slate-400 font-bold">S/</span>
                                <input type="number" step="0.50" name="price_cheese_wholesale" value="{{ old('price_cheese_wholesale', $currentPrice->price_cheese_wholesale) }}" required class="w-full pl-8 pr-3 py-2.5 rounded-xl border border-slate-200 text-xs font-bold bg-slate-50/50 focus:bg-white">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-600 mb-1">Tarifa Cliente Local / Público</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-xs text-slate-400 font-bold">S/</span>
                                <input type="number" step="0.50" name="price_cheese_local" value="{{ old('price_cheese_local', $currentPrice->price_cheese_local) }}" required class="w-full pl-8 pr-3 py-2.5 rounded-xl border border-slate-200 text-xs font-bold bg-slate-50/50 focus:bg-white">
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-semibold text-slate-600 mb-1">Notas o Justificación del Cambio</label>
                    <textarea name="notes" rows="2" placeholder="Ej. Aumento de tarifa por escasez de pastos en temporada seca..." class="w-full text-xs p-2.5 rounded-xl border border-slate-200 bg-slate-50/50"></textarea>
                </div>

                <button type="submit" class="w-full bg-[#0f1713] hover:bg-slate-900 text-[#bef264] font-bold py-3 rounded-2xl text-xs transition shadow-md flex items-center justify-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar y Aplicar Nuevas Tarifas
                </button>
            </form>
        </div>

        <!-- HISTORIAL DE CAMBIOS DE TARIFAS -->
        <div class="lg:col-span-2 bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-5">
                    <div class="flex items-center gap-2">
                        <div class="w-7 h-7 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-xs">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                        <h3 class="text-sm font-bold text-slate-900">Historial de Actualizaciones de Precios</h3>
                    </div>
                    <span class="text-xs text-slate-400 font-medium">Total registros: {{ $history->total() }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                                <th class="pb-3 px-3">Fecha</th>
                                <th class="pb-3 px-3">Temporada</th>
                                <th class="pb-3 px-3">Leche (Base / &le;5% / >5%)</th>
                                <th class="pb-3 px-3">Queso (Prov / May / Loc)</th>
                                <th class="pb-3 px-3">Modificado Por</th>
                                <th class="pb-3 px-3 text-right">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($history as $item)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="py-3.5 px-3 text-slate-500 font-medium whitespace-nowrap">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                                <td class="py-3.5 px-3 font-bold text-slate-900">{{ $item->season_name }}</td>
                                <td class="py-3.5 px-3 whitespace-nowrap">
                                    <span class="font-bold text-[#0f1713]">S/ {{ number_format($item->price_milk_base, 2) }}</span> / 
                                    <span class="text-amber-600 font-semibold">{{ number_format($item->price_milk_water_penalty_low, 2) }}</span> / 
                                    <span class="text-rose-600 font-semibold">{{ number_format($item->price_milk_water_penalty_high, 2) }}</span>
                                </td>
                                <td class="py-3.5 px-3 whitespace-nowrap text-slate-600">
                                    S/ {{ number_format($item->price_cheese_provider, 2) }} / {{ number_format($item->price_cheese_wholesale, 2) }} / {{ number_format($item->price_cheese_local, 2) }}
                                </td>
                                <td class="py-3.5 px-3 text-slate-700 font-medium">{{ $item->updater->name ?? 'Sistema' }}</td>
                                <td class="py-3.5 px-3 text-right">
                                    @if($item->is_active)
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-[#bef264]/40 text-[#0f1713] border border-[#bef264]/60">
                                            Activa
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-100 text-slate-500">
                                            Archivada
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="p-8 text-center text-slate-400 font-medium">No hay historial previo registrado.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100">
                {{ $history->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
