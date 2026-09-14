@extends('layouts.app')

@section('title', 'Quesería y Transformación')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200/80 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <span class="text-[10px] bg-lime-100 text-spark-limeText font-extrabold px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                Producción Láctea (-10L / Molde)
            </span>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight mt-1">
                Elaboración y Maduración de Quesos
            </h1>
            <p class="text-xs text-slate-500 mt-1">
                Cada molde de queso descuenta automáticamente 10 L de leche e ingresa al inventario de ventas.
            </p>
        </div>

        <div class="flex gap-3">
            <div class="bg-slate-50 border border-slate-200 px-4 py-2.5 rounded-2xl text-right">
                <span class="text-[10px] text-slate-400 block uppercase font-bold">Leche Disponible</span>
                <span class="text-xl font-black text-slate-800">{{ number_format($stockLeche, 1) }} L</span>
            </div>
            <div class="bg-slate-50 border border-slate-200 px-4 py-2.5 rounded-2xl text-right">
                <span class="text-[10px] text-slate-400 block uppercase font-bold">Quesos en Stock</span>
                <span class="text-xl font-black text-spark-limeText">{{ (int)$stockQueso }} Moldes</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Formulario Spark Style -->
        <div class="bg-white p-6 sm:p-7 rounded-3xl border border-slate-200/80 shadow-sm">
            <h3 class="text-base font-bold text-slate-900 mb-2">Registrar Elaboración</h3>
            <p class="text-[11px] text-slate-500 mb-4">Máximo con stock actual: <strong>{{ $maxMoldesPosibles }} moldes</strong>.</p>

            <form action="{{ route('produccion.store') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Moldes a Elaborar</label>
                    <input type="number" name="cheese_molds_produced" id="moldesInput" min="1" max="{{ max(1, $maxMoldesPosibles) }}" required
                        placeholder="Ej. 10" class="w-full text-xl font-black p-3 bg-slate-50 border border-slate-200 rounded-2xl text-slate-900 focus:bg-white focus:ring-2 focus:ring-spark-lime">
                </div>

                <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-100 text-[11px] space-y-1">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Leche a descontar:</span>
                        <strong id="lecheDescuento" class="text-rose-600 font-black">0.0 L</strong>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Quesos a stock:</span>
                        <strong id="quesosAumento" class="text-emerald-600 font-black">+0 moldes</strong>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Lote (Opcional)</label>
                    <input type="text" name="batch_number" placeholder="Ej. LOTE-QUESO-01" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl">
                </div>

                <button type="submit" class="w-full bg-spark-dark hover:bg-black text-spark-lime font-black py-3 rounded-2xl transition shadow-sm text-xs tracking-wider uppercase">
                    Elaborar y Transferir a Stock
                </button>
            </form>
        </div>

        <!-- Tabla Historial Spark Style -->
        <div class="lg:col-span-2 bg-white p-6 sm:p-7 rounded-3xl border border-slate-200/80 shadow-sm">
            <h3 class="text-base font-bold text-slate-900 mb-4">Lotes Producidos Recientes</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider">
                        <tr>
                            <th class="p-3 rounded-l-xl">Fecha</th>
                            <th class="p-3">Lote</th>
                            <th class="p-3">Moldes</th>
                            <th class="p-3">Leche Consumida</th>
                            <th class="p-3 rounded-r-xl">Supervisor</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($producciones as $prod)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="p-3 text-slate-700 font-semibold">{{ $prod->production_date }}</td>
                            <td class="p-3 font-mono text-slate-500">{{ $prod->batch_number }}</td>
                            <td class="p-3 font-black text-emerald-700 text-sm">+{{ $prod->cheese_molds_produced }}</td>
                            <td class="p-3 font-bold text-rose-600">-{{ $prod->milk_liters_used }} L</td>
                            <td class="p-3 text-slate-500">{{ $prod->supervisor->name }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="p-6 text-center text-slate-400">Sin lotes registrados.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $producciones->links() }}
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const input = document.getElementById('moldesInput');
    const lecheSpan = document.getElementById('lecheDescuento');
    const quesoSpan = document.getElementById('quesosAumento');

    input.addEventListener('input', function() {
        const val = parseInt(this.value) || 0;
        lecheSpan.textContent = (val * 10).toFixed(1) + ' L';
        quesoSpan.textContent = '+' + val + ' moldes';
    });
</script>
@endpush
@endsection
