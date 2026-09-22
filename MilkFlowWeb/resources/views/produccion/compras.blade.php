@extends('layouts.app')

@section('title', 'Compras de Insumos')

@section('content')
<div class="space-y-6">

    <div class="bg-blue-50/70 border border-blue-200 rounded-3xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="text-xs text-blue-900">
            <strong class="block text-[11px] uppercase tracking-wider">Esto no es el padrón de proveedores</strong>
            <span class="text-[11px] text-blue-800/90">
                Aquí solo se anota lo que se compró para actualizar el stock y el costo del insumo:
                fruta, envases, cuajo. A quien figure en la boleta no se le abre ficha de proveedor
                ni le corresponde tarifa de proveedor.
                <strong>Los proveedores de la asociación son los productores de leche</strong>,
                y sus beneficios salen de su rol en el padrón: su leche entra por el caudalímetro
                y se le paga en la liquidación semanal, con su penalidad por agua.
            </span>
        </div>
        <div class="text-right whitespace-nowrap">
            <span class="text-[10px] text-blue-700 block uppercase font-bold">Leche del mes</span>
            <span class="text-lg font-black text-blue-900">S/ {{ number_format($leche['importe'], 2) }}</span>
            <span class="text-[10px] text-blue-700 block">{{ number_format($leche['litros'], 2) }} L en liquidaciones</span>
        </div>
    </div>

    <x-tabla
        titulo="Compras de insumos"
        descripcion="Qué entró al almacén, a qué precio y contra qué boleta. Cada compra ingresa la mercadería y recalcula el costo promedio del insumo; corregirla o anularla la devuelve."
        :coleccion="$compras"
        :columnas="5"
        vacio="Ninguna compra coincide con el filtro.">

        <x-slot:acciones>
            <div class="flex items-center gap-2">
                <div class="bg-slate-50 border border-slate-200 px-3 py-2 rounded-xl text-right">
                    <span class="text-[10px] text-slate-400 block uppercase font-bold">Gasto del mes</span>
                    <span class="text-sm font-black text-spark-limeText">S/ {{ number_format($resumen['gasto_mes'], 2) }}</span>
                </div>
                <button type="button" data-nueva-compra
                    class="px-4 py-2.5 rounded-xl bg-spark-dark hover:bg-black text-spark-lime font-black text-[11px] uppercase tracking-wider whitespace-nowrap">
                    <i class="fa-solid fa-plus mr-1"></i> Agregar
                </button>
            </div>
        </x-slot:acciones>

        <x-slot:filtros>
            <form method="GET" action="{{ route('produccion.compras.index') }}" class="flex flex-wrap items-center gap-2">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                    <input type="search" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre o documento..."
                        class="w-56 pl-8 pr-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-spark-lime">
                </div>
                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-spark-lime font-bold text-[10px] uppercase">Buscar</button>
                @if(request('buscar'))
                <a href="{{ route('produccion.compras.index') }}" class="text-[11px] font-bold text-slate-500 underline">Quitar búsqueda</a>
                @endif
            </form>
        </x-slot:filtros>

        <x-slot:encabezados>
            <th class="text-left py-3 px-4 font-bold">Fecha</th>
            <th class="text-left py-3 px-4 font-bold">A quién se le compró</th>
            <th class="text-left py-3 px-4 font-bold">Documento</th>
            <th class="text-left py-3 px-4 font-bold">Qué entró</th>
            <th class="text-right py-3 px-4 font-bold">Total</th>
            <th class="text-right py-3 px-4 font-bold">Acciones</th>
        </x-slot:encabezados>

        @foreach($compras as $compra)
        @php
            $datosCompra = [
                'id' => $compra->id,
                'supplier_name' => $compra->supplier->name,
                'supplier_document' => $compra->supplier->document,
                'purchase_date' => $compra->purchase_date?->format('Y-m-d'),
                'document_number' => $compra->document_number,
                'notes' => $compra->notes,
                'items' => $compra->items->map(fn ($item) => [
                    'supply_id' => $item->supply_id,
                    'quantity' => (float) $item->quantity,
                    'unit_cost' => (float) $item->unit_cost,
                ])->values(),
            ];
        @endphp
        <tr class="border-b border-slate-50 hover:bg-slate-50/60 transition align-top">
            <td class="py-3 px-4 font-mono text-slate-400">{{ $compra->id }}</td>
            <td class="py-3 px-4 text-slate-600 whitespace-nowrap">{{ $compra->purchase_date?->format('d/m/Y') }}</td>
            <td class="py-3 px-4 font-bold text-slate-800">
                {{ $compra->supplier->name }}
                @if($compra->supplier->document)
                <span class="block font-normal text-[10px] text-slate-400">{{ $compra->supplier->document }}</span>
                @endif
            </td>
            <td class="py-3 px-4 font-mono text-slate-500">{{ $compra->document_number ?: '—' }}</td>
            <td class="py-3 px-4 text-slate-600">
                @foreach($compra->items as $item)
                <div>
                    {{ rtrim(rtrim(number_format($item->quantity, 3, '.', ''), '0'), '.') }}
                    {{ $item->supply->unit }} de <strong>{{ $item->supply->name }}</strong>
                    <span class="text-slate-400">a S/ {{ number_format($item->unit_cost, 2) }}</span>
                </div>
                @endforeach
                @if($compra->notes)
                <div class="text-[10px] text-slate-400 italic mt-1">{{ $compra->notes }}</div>
                @endif
            </td>
            <td class="py-3 px-4 text-right font-black text-slate-900 whitespace-nowrap">
                S/ {{ number_format($compra->total_amount, 2) }}
            </td>
            <td class="py-3 px-4">
                <div class="flex justify-end gap-1.5">
                    <button type="button" data-editar-compra
                        data-compra="{{ json_encode($datosCompra) }}"
                        class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10px] uppercase">
                        Editar
                    </button>
                    <form action="{{ route('produccion.compras.destroy', $compra) }}" method="POST"
                        onsubmit="return confirm('¿Anular esta compra? Se devolverá su mercadería al almacén.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-[10px] uppercase">
                            Anular
                        </button>
                    </form>
                </div>
            </td>
        </tr>
        @endforeach
    </x-tabla>

</div>

{{-- Modal: registrar o corregir compra --}}
<div id="modalCompra" hidden class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50">
    <div class="bg-white w-full max-w-3xl rounded-3xl p-6 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900" data-titulo>Registrar compra</h3>
                <p class="text-[11px] text-slate-500">Copia la boleta tal como vino. La mercadería entra al almacén al guardar.</p>
            </div>
            <button type="button" data-cerrar class="text-slate-400 hover:text-slate-700 text-lg leading-none">&times;</button>
        </div>

        <form method="POST" action="{{ route('produccion.compras.store') }}" class="space-y-3 text-xs" data-formulario-compra
            data-url-crear="{{ route('produccion.compras.store') }}"
            data-url-editar="{{ route('produccion.compras.update', ['compra' => '__ID__']) }}">
            @csrf
            <input type="hidden" name="_method" value="POST" data-metodo>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Nombre o razón social</label>
                    <input type="text" name="new_supplier_name" maxlength="120" required data-campo="new_supplier_name"
                        placeholder="Envases del Altiplano SAC"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                </div>
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">RUC / DNI</label>
                    <input type="text" name="new_supplier_document" maxlength="20" data-campo="new_supplier_document"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Fecha</label>
                    <input type="date" name="purchase_date" required data-campo="purchase_date" value="{{ now()->format('Y-m-d') }}"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                </div>
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Boleta / Factura</label>
                    <input type="text" name="document_number" maxlength="50" data-campo="document_number" placeholder="F001-00123"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                </div>
            </div>

            <p class="text-[10px] text-slate-500">
                Como figura en la boleta. Si ese RUC o ese nombre ya salió en una compra anterior,
                esta se le carga al mismo y no se duplica el nombre en el historial.
            </p>

            <div class="border border-slate-200 rounded-2xl p-3">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] uppercase font-bold text-slate-600">Qué se compró</span>
                    <button type="button" data-agregar-renglon
                        class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10px] uppercase">
                        <i class="fa-solid fa-plus mr-1"></i> Agregar insumo
                    </button>
                </div>

                @if($insumos->isEmpty())
                <p class="text-[11px] text-amber-700">
                    No hay insumos que se compren. Da de alta uno en
                    <a href="{{ route('produccion.almacen.index') }}" class="font-bold underline">Almacén e Insumos</a>.
                </p>
                @endif

                <div data-renglones class="space-y-2"></div>

                <div class="flex justify-end items-center gap-3 mt-3 pt-3 border-t border-slate-100">
                    <span class="text-[10px] uppercase font-bold text-slate-500">Total</span>
                    <span class="text-lg font-black text-slate-900">S/ <span data-total>0.00</span></span>
                </div>
            </div>

            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Nota (opcional)</label>
                <input type="text" name="notes" maxlength="255" data-campo="notes"
                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800">
            </div>

            <div class="flex gap-2 pt-2">
                <button type="button" data-cerrar class="flex-1 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] uppercase">Cancelar</button>
                <button type="submit" class="flex-1 bg-spark-dark hover:bg-black text-spark-lime font-black py-2.5 rounded-xl text-[11px] uppercase tracking-wider">Guardar compra</button>
            </div>
        </form>
    </div>
</div>

{{-- Plantilla de renglón de compra --}}
<template id="plantillaRenglon">
    <div class="grid grid-cols-12 gap-2 items-center" data-renglon>
        <select data-renglon-insumo required
            class="col-span-5 p-2 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800 text-xs">
            @foreach($insumos as $insumo)
            <option value="{{ $insumo->id }}" data-unidad="{{ $insumo->unit }}">{{ $insumo->name }} ({{ $insumo->unit }})</option>
            @endforeach
        </select>
        <input type="number" step="0.0001" min="0.0001" required data-renglon-cantidad placeholder="Cantidad"
            class="col-span-2 p-2 bg-slate-50 border border-slate-200 rounded-xl text-right font-semibold text-xs">
        <input type="number" step="0.0001" min="0" required data-renglon-costo placeholder="Costo S/"
            class="col-span-2 p-2 bg-slate-50 border border-slate-200 rounded-xl text-right font-semibold text-xs">
        <span class="col-span-2 text-right text-xs font-bold text-slate-700">S/ <span data-renglon-subtotal>0.00</span></span>
        <button type="button" data-quitar-renglon
            class="col-span-1 py-2 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-[10px] uppercase">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
</template>

<script>
(function () {
    const modalCompra = document.getElementById('modalCompra');
    const plantilla = document.getElementById('plantillaRenglon');

    function cerrar(modal) {
        if (modal) { modal.hidden = true; }
    }

    document.querySelectorAll('[data-cerrar]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            cerrar(boton.closest('[id^="modal"]'));
        });
    });

    modalCompra.addEventListener('click', function (evento) {
        if (evento.target === modalCompra) { cerrar(modalCompra); }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') { cerrar(modalCompra); }
    });

    // --- Renglones dinámicos ---
    const formCompra = modalCompra.querySelector('[data-formulario-compra]');

    function contenedorRenglones() {
        return formCompra.querySelector('[data-renglones]');
    }

    function renumerar() {
        contenedorRenglones().querySelectorAll('[data-renglon]').forEach(function (fila, indice) {
            fila.querySelector('[data-renglon-insumo]').name = 'items[' + indice + '][supply_id]';
            fila.querySelector('[data-renglon-cantidad]').name = 'items[' + indice + '][quantity]';
            fila.querySelector('[data-renglon-costo]').name = 'items[' + indice + '][unit_cost]';
        });
    }

    function recalcular() {
        let total = 0;

        contenedorRenglones().querySelectorAll('[data-renglon]').forEach(function (fila) {
            const cantidad = parseFloat(fila.querySelector('[data-renglon-cantidad]').value) || 0;
            const costo = parseFloat(fila.querySelector('[data-renglon-costo]').value) || 0;
            const subtotal = cantidad * costo;

            fila.querySelector('[data-renglon-subtotal]').textContent = subtotal.toFixed(2);
            total += subtotal;
        });

        formCompra.querySelector('[data-total]').textContent = total.toFixed(2);
    }

    function agregarRenglon(valores) {
        const fila = plantilla.content.firstElementChild.cloneNode(true);

        if (valores) {
            fila.querySelector('[data-renglon-insumo]').value = valores.supply_id;
            fila.querySelector('[data-renglon-cantidad]').value = valores.quantity;
            fila.querySelector('[data-renglon-costo]').value = valores.unit_cost;
        }

        fila.querySelector('[data-quitar-renglon]').addEventListener('click', function () {
            // Una compra sin renglones no existe: el último no se quita.
            if (contenedorRenglones().querySelectorAll('[data-renglon]').length > 1) {
                fila.remove();
                renumerar();
                recalcular();
            }
        });

        fila.querySelectorAll('[data-renglon-cantidad], [data-renglon-costo]').forEach(function (campo) {
            campo.addEventListener('input', recalcular);
        });

        contenedorRenglones().appendChild(fila);
        renumerar();
        recalcular();
    }

    formCompra?.querySelector('[data-agregar-renglon]')?.addEventListener('click', function () {
        agregarRenglon(null);
    });

    function abrirCompra(compra) {
        modalCompra.hidden = false;

        // Sin insumos que comprar no hay formulario, solo el aviso.
        if (!formCompra) { return; }

        modalCompra.querySelector('[data-titulo]').textContent = compra ? 'Corregir compra' : 'Registrar compra';
        formCompra.action = compra
            ? formCompra.dataset.urlEditar.replace('__ID__', compra.id)
            : formCompra.dataset.urlCrear;
        formCompra.querySelector('[data-metodo]').value = compra ? 'PUT' : 'POST';

        formCompra.querySelector('[data-campo="new_supplier_name"]').value = compra ? compra.supplier_name : '';
        formCompra.querySelector('[data-campo="new_supplier_document"]').value = compra ? (compra.supplier_document ?? '') : '';
        formCompra.querySelector('[data-campo="purchase_date"]').value = compra
            ? compra.purchase_date
            : new Date().toISOString().slice(0, 10);
        formCompra.querySelector('[data-campo="document_number"]').value = compra ? (compra.document_number ?? '') : '';
        formCompra.querySelector('[data-campo="notes"]').value = compra ? (compra.notes ?? '') : '';

        contenedorRenglones().innerHTML = '';

        if (compra && compra.items.length) {
            compra.items.forEach(agregarRenglon);
        } else {
            agregarRenglon(null);
        }
    }

    document.querySelectorAll('[data-nueva-compra]').forEach(function (boton) {
        boton.addEventListener('click', function () { abrirCompra(null); });
    });

    document.querySelectorAll('[data-editar-compra]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            abrirCompra(JSON.parse(boton.dataset.compra));
        });
    });
})();
</script>
@endsection
