<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liquidación de Acopio - {{ $settlement->settlement_code }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen p-4 sm:p-8 flex flex-col items-center justify-center font-sans antialiased text-slate-800">

    <div class="no-print mb-6 flex gap-3">
        <button onclick="window.print()" class="bg-[#0f1713] text-[#bef264] px-5 py-2.5 rounded-2xl font-bold text-xs flex items-center gap-2 shadow-lg hover:bg-slate-900 transition">
            <i class="fa-solid fa-print"></i> Imprimir Liquidación
        </button>
        <button onclick="window.close()" class="bg-white border border-slate-300 text-slate-700 px-5 py-2.5 rounded-2xl font-bold text-xs hover:bg-slate-50 transition">
            Cerrar
        </button>
    </div>

    <!-- Comprobante formal de liquidación -->
    <div class="w-full max-w-md bg-white p-8 rounded-3xl shadow-xl border border-slate-200">
        <!-- Encabezado -->
        <div class="text-center pb-6 border-b border-dashed border-slate-300">
            <div class="w-12 h-12 mx-auto mb-2 rounded-2xl bg-[#0f1713] text-[#bef264] flex items-center justify-center text-xl">
                <i class="fa-solid fa-asterisk"></i>
            </div>
            <h2 class="text-lg font-black text-slate-900 tracking-tight">MILKFLOW HUATA</h2>
            <p class="text-[11px] text-slate-500">Planta de Acopio y Transformación Lechera</p>
            <p class="text-[10px] text-slate-400">Distrito de Huata - Puno, Perú</p>
            <span class="inline-block mt-3 px-3 py-1 bg-slate-100 rounded-full font-mono text-xs font-black text-slate-800">
                {{ $settlement->settlement_code }}
            </span>
        </div>

        <!-- Datos del Proveedor y Período -->
        <div class="py-5 space-y-2 text-xs border-b border-dashed border-slate-300">
            <div class="flex justify-between">
                <span class="text-slate-500">Proveedor / Productor:</span>
                <span class="font-bold text-slate-900">{{ $settlement->producer->name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">DNI / Documento:</span>
                <span class="font-mono text-slate-700">{{ $settlement->producer->dni ?: '—' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Zona de Acopio:</span>
                <span class="font-bold text-slate-700">{{ $settlement->producer->zone ? $settlement->producer->zone->name : 'Huata' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Período Acopiado:</span>
                <span class="font-bold text-slate-900">{{ $settlement->start_date->format('d/m/Y') }} al {{ $settlement->end_date->format('d/m/Y') }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Fecha de Liquidación:</span>
                <span class="text-slate-700">{{ $settlement->paid_at ? $settlement->paid_at->format('d/m/Y H:i') : 'Pendiente' }}</span>
            </div>
        </div>

        <!-- Desglose de Leche -->
        <div class="py-5 space-y-3 text-xs border-b border-dashed border-slate-300">
            <div class="flex justify-between items-center">
                <div>
                    <span class="font-bold text-slate-800 block">Total Leche Fresca Entregada</span>
                    <span class="text-[10px] text-slate-400">Tarifa fija por litro: S/ {{ number_format($settlement->price_per_liter, 2) }}</span>
                </div>
                <span class="text-sm font-black text-[#0f1713]">{{ number_format($settlement->total_liters, 2) }} L</span>
            </div>
            <div class="flex justify-between text-slate-600 pt-1">
                <span>Subtotal Bruto:</span>
                <span class="font-bold">S/ {{ number_format($settlement->gross_total, 2) }}</span>
            </div>
            
            @if($settlement->deductions->count() > 0)
            <div class="pt-2 border-t border-slate-100">
                <span class="text-[10px] uppercase font-bold text-rose-600 block mb-1">Deducciones Aplicadas:</span>
                @foreach($settlement->deductions as $d)
                <div class="flex justify-between text-[11px] text-rose-600">
                    <span>- {{ $d->concept }}:</span>
                    <span>S/ {{ number_format($d->amount, 2) }}</span>
                </div>
                @endforeach
                <div class="flex justify-between text-xs font-bold text-rose-700 pt-1">
                    <span>Total Descuentos:</span>
                    <span>- S/ {{ number_format($settlement->deductions_total, 2) }}</span>
                </div>
            </div>
            @endif
        </div>

        <!-- Total Neto Pagado -->
        <div class="py-6 text-center bg-slate-50 rounded-2xl my-4 border border-slate-200/80">
            <span class="text-[10px] uppercase font-black text-slate-500 tracking-wider block">Monto Neto Pagado (Efectivo)</span>
            <span class="text-3xl font-black text-slate-900 mt-1 block">
                S/ {{ number_format($settlement->net_total, 2) }}
            </span>
            <span class="inline-block mt-2 px-3 py-0.5 rounded-full text-[10px] font-bold uppercase bg-emerald-100 text-emerald-800">
                Cancelado en Planta
            </span>
        </div>

        <!-- Firmas -->
        <div class="mt-8 pt-8 border-t border-slate-300 grid grid-cols-2 gap-4 text-center text-[10px]">
            <div>
                <div class="border-b border-slate-400 pb-12 mb-1"></div>
                <span class="font-bold text-slate-700 block">Firma del Proveedor</span>
                <span class="text-slate-400">{{ $settlement->producer->name }}</span>
            </div>
            <div>
                <div class="border-b border-slate-400 pb-12 mb-1"></div>
                <span class="font-bold text-slate-700 block">VºBº Liquidación</span>
                <span class="text-slate-400">Encargado de Pagos Huata</span>
            </div>
        </div>
    </div>

</body>
</html>
