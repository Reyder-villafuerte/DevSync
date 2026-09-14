@extends('layouts.app')

@section('title', 'Descuentos y Anticipos - Proveedor')

@section('content')
<div class="space-y-6">
    <!-- Header Banner -->
    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)] flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-[#0f1713] text-[#bef264] flex items-center justify-center text-xl shadow-sm">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-black text-slate-900 tracking-tight">Descuentos, Anticipos e Insumos</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#bef264]/30 text-[#0f1713] border border-[#bef264]/50 uppercase tracking-wide">Deducciones</span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Registro transparente de adelantos en efectivo, alimentos balanceados, medicinas o servicios aplicados a tus pagos de leche.
                </p>
            </div>
        </div>

        <a href="{{ route('productor.acopio') }}" class="px-3.5 py-2 rounded-2xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 transition inline-flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i> Volver a Acopio
        </a>
    </div>

    <!-- TARJETAS DE RESUMEN DE DESCUENTOS -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Pendiente de Liquidar -->
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Pendiente de Deducción</span>
            <div class="flex items-baseline gap-2 mt-2">
                <span class="text-3xl font-black text-rose-600">S/ {{ number_format($totalPendiente, 2) }}</span>
            </div>
            <p class="text-xs text-slate-400 mt-2">Se deducirá del pago de tu semana activa actual.</p>
        </div>

        <!-- Total Ya Descontado -->
        <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Total Ya Descontado</span>
            <div class="flex items-baseline gap-2 mt-2">
                <span class="text-3xl font-black text-[#0f1713]">S/ {{ number_format($totalDescontado, 2) }}</span>
            </div>
            <p class="text-xs text-slate-400 mt-2">Deducido y conciliado en liquidaciones cerradas.</p>
        </div>

        <!-- Info / Ayuda -->
        <div class="bg-[#0f1713] text-white rounded-3xl p-6 shadow-md flex flex-col justify-between">
            <div>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#bef264] text-[#0f1713] uppercase tracking-wider">Transparencia</span>
                <p class="text-xs text-slate-300 mt-3 leading-relaxed">
                    Cualquier adelanto solicitado en oficina o alimento retirado en almacén se registra aquí de forma inmediata.
                </p>
            </div>
            <span class="text-[11px] text-[#bef264] font-medium mt-3">Huata Gestión Lechera</span>
        </div>
    </div>

    <!-- TABLA DE DETALLE DE DESCUENTOS -->
    <div class="bg-white rounded-3xl p-6 border border-slate-100 shadow-[0_4px_20px_rgba(0,0,0,0.03)]">
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-list-ol"></i>
                </div>
                <h3 class="text-sm font-bold text-slate-900">Detalle de Anticipos y Descuentos</h3>
            </div>
            <span class="text-xs text-slate-400 font-medium">Total registros: {{ $descuentos->count() }}</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                        <th class="pb-3 px-3">Fecha</th>
                        <th class="pb-3 px-3">Concepto / Motivo</th>
                        <th class="pb-3 px-3">Monto Descontado</th>
                        <th class="pb-3 px-3">Liquidación Asociada</th>
                        <th class="pb-3 px-3 text-center">Estado</th>
                        <th class="pb-3 px-3 text-right">Comprobante</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($descuentos as $desc)
                    @php
                        $sale = $desc->matched_sale;
                    @endphp
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-3.5 px-3 font-medium text-slate-600">{{ $desc->date->format('d/m/Y') }}</td>
                        <td class="py-3.5 px-3">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-slate-900 block text-xs">{{ $desc->concept }}</span>
                                @if($sale)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 text-[10px] font-bold border border-emerald-200">
                                    <i class="fa-solid fa-receipt text-[9px]"></i> {{ $sale->receipt_number }}
                                </span>
                                @endif
                            </div>
                            @if($desc->notes)
                            <span class="text-[10px] text-slate-400 block">{{ $desc->notes }}</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-3 font-black text-rose-600 text-sm">
                            - S/ {{ number_format($desc->amount, 2) }}
                        </td>
                        <td class="py-3.5 px-3 font-mono text-slate-500">
                            {{ $desc->settlement ? $desc->settlement->settlement_code : 'Pendiente de ciclo' }}
                        </td>
                        <td class="py-3.5 px-3 text-center">
                            @if($desc->status === 'descontado')
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-slate-100 text-slate-700">
                                    Descontado
                                </span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-amber-50 text-amber-800 border border-amber-200">
                                    Por Deducir
                                </span>
                            @endif
                        </td>
                        <td class="py-3.5 px-3 text-right">
                            @if($sale)
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button" 
                                    onclick="openReceiptModal('{{ $sale->receipt_number }}', '{{ $sale->sold_at }}', '{{ $sale->seller ? $sale->seller->name : 'Planta Huata' }}', '{{ $sale->cheese_molds_quantity }}', '{{ number_format($sale->unit_price, 2) }}', '{{ number_format($sale->total_amount, 2) }}', '{{ route('ventas.receipt', $sale->id) }}')"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-spark-dark text-spark-lime hover:bg-black font-bold text-xs shadow-sm transition"
                                    title="Previsualizar recibo térmico">
                                    <i class="fa-solid fa-eye text-xs"></i>
                                    <span>Ver Recibo</span>
                                </button>
                                <a href="{{ route('ventas.receipt', $sale->id) }}" 
                                    class="p-1.5 rounded-xl border border-slate-200 text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition"
                                    title="Abrir recibo para impresión">
                                    <i class="fa-solid fa-print text-xs"></i>
                                </a>
                            </div>
                            @else
                            <span class="text-slate-300 font-mono text-xs">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-400 font-medium">No tienes descuentos ni adelantos registrados.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL DE RECIBO DE VENTA (ESTILO TÉRMICO SPARK) -->
<div id="receiptModal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm hidden flex items-center justify-center p-4" onclick="if(event.target === this) closeReceiptModal()">
    <div class="bg-white rounded-3xl p-6 md:p-8 max-w-md w-full shadow-2xl border border-slate-200 font-mono text-slate-800 text-xs relative max-h-[90vh] overflow-y-auto">
        <!-- Botón cerrar X -->
        <button onclick="closeReceiptModal()" class="absolute top-4 right-4 w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-900 flex items-center justify-center transition font-sans text-sm">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <!-- Encabezado térmico -->
        <div class="text-center pb-5 border-b border-dashed border-slate-300">
            <div class="w-10 h-10 rounded-xl bg-spark-dark text-spark-lime mx-auto flex items-center justify-center text-lg mb-2 shadow-sm font-sans">
                <i class="fa-solid fa-asterisk"></i>
            </div>
            <h2 class="text-sm font-black tracking-wider uppercase text-slate-900 font-sans">PLANTA QUESERA HUATA</h2>
            <p class="text-[10px] text-slate-500 font-sans">Distrito de Huata — Puno, Perú</p>
            <div class="mt-3 inline-block bg-slate-100 px-3 py-1 rounded-full text-slate-800 font-bold text-xs">
                RECIBO: <span id="modalReceiptNum" class="font-mono"></span>
            </div>
        </div>

        <!-- Datos de cabecera -->
        <div class="py-4 border-b border-dashed border-slate-300 space-y-1.5 text-[11px]">
            <div class="flex justify-between">
                <span class="text-slate-400 font-sans">Fecha y Hora:</span>
                <strong id="modalReceiptDate" class="text-slate-800"></strong>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400 font-sans">Despachador:</span>
                <span id="modalReceiptSeller" class="text-slate-700 font-sans"></span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400 font-sans">Cliente (Proveedor):</span>
                <strong class="text-slate-900 font-sans">{{ $user->name }}</strong>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-400 font-sans">DNI:</span>
                <span class="text-slate-700 font-mono">{{ $user->dni ?: '—' }}</span>
            </div>
        </div>

        <!-- Detalle de productos -->
        <div class="py-4 border-b border-dashed border-slate-300">
            <table class="w-full text-[11px]">
                <thead>
                    <tr class="text-[10px] text-slate-400 border-b border-slate-200">
                        <th class="text-left pb-1.5 font-sans">ÍTEM</th>
                        <th class="text-center pb-1.5 font-sans">CANT</th>
                        <th class="text-right pb-1.5 font-sans">P.UNIT</th>
                        <th class="text-right pb-1.5 font-sans">SUBTOTAL</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr>
                        <td class="py-2 text-slate-800 font-sans">Molde Queso Madurado</td>
                        <td id="modalReceiptQty" class="text-center py-2 font-black"></td>
                        <td id="modalReceiptUnitPrice" class="text-right py-2 text-slate-600"></td>
                        <td id="modalReceiptSubtotal" class="text-right py-2 font-bold text-slate-900"></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Total y forma de pago -->
        <div class="pt-4 space-y-1.5">
            <div class="flex justify-between text-sm font-black text-slate-900">
                <span class="font-sans">TOTAL CARGADO:</span>
                <span id="modalReceiptTotal" class="text-rose-600 font-mono"></span>
            </div>
            <div class="flex justify-between text-[10px] text-slate-500 font-sans">
                <span>FORMA DE PAGO:</span>
                <strong class="text-amber-900 uppercase bg-amber-50 px-2 py-0.5 rounded border border-amber-200">Descuento a cuenta de leche</strong>
            </div>
        </div>

        <!-- Acciones -->
        <div class="mt-6 pt-4 border-t border-slate-100 flex gap-2 font-sans">
            <a id="modalReceiptFullLink" href="#" class="flex-1 py-2.5 rounded-xl bg-spark-dark hover:bg-black text-spark-lime font-bold text-xs flex items-center justify-center gap-2 shadow-sm transition">
                <i class="fa-solid fa-print"></i> <span>Imprimir Comprobante</span>
            </a>
            <button onclick="closeReceiptModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-600 font-bold text-xs transition">
                Cerrar
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openReceiptModal(num, date, seller, qty, unitPrice, total, url) {
        document.getElementById('modalReceiptNum').innerText = num;
        document.getElementById('modalReceiptDate').innerText = date;
        document.getElementById('modalReceiptSeller').innerText = seller;
        document.getElementById('modalReceiptQty').innerText = qty;
        document.getElementById('modalReceiptUnitPrice').innerText = 'S/ ' + unitPrice;
        document.getElementById('modalReceiptSubtotal').innerText = 'S/ ' + total;
        document.getElementById('modalReceiptTotal').innerText = 'S/ ' + total;
        document.getElementById('modalReceiptFullLink').href = url;
        document.getElementById('receiptModal').classList.remove('hidden');
    }

    function closeReceiptModal() {
        document.getElementById('receiptModal').classList.add('hidden');
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeReceiptModal();
    });
</script>
@endpush
@endsection

