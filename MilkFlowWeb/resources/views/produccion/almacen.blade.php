@extends('layouts.app')

@section('title', 'Almacén e Insumos')

@section('content')
<div class="space-y-6">

    @if($bajoMinimo->isNotEmpty())
    <div class="bg-amber-50 border border-amber-200 rounded-3xl p-5">
        <h3 class="text-xs font-black text-amber-900 uppercase tracking-wider mb-2">Insumos bajo el mínimo</h3>
        <ul class="text-xs text-amber-800 space-y-1">
            @foreach($bajoMinimo as $insumo)
            <li>• <strong>{{ $insumo->name }}</strong> ({{ $insumo->category?->name ?? 'sin categoría' }}): quedan
                {{ number_format($stocks->get($insumo->item_code, 0), 2) }} {{ $insumo->unit }},
                mínimo {{ number_format($insumo->minimum_stock, 2) }}.</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- ---------- LO QUE SE COMPRA ---------- --}}
    @php
        $valorAlmacen = $insumos->sum(fn ($item) => $stocks->get($item->item_code, 0) * (float) $item->unit_cost);
    @endphp

    <x-tabla
        titulo="Insumos"
        descripcion="Lo que se compra y se consume en producción: leche cruda, envases, fruta, cuajo. No se venden, por eso solo llevan costo de compra."
        :coleccion="$insumos"
        :columnas="8"
        vacio="Ningún insumo coincide con el filtro.">

        <x-slot:acciones>
            <div class="flex items-center gap-2">
                <div class="bg-slate-50 border border-slate-200 px-3 py-2 rounded-xl text-right">
                    <span class="text-[10px] text-slate-400 block uppercase font-bold">Valorizado (página)</span>
                    <span class="text-sm font-black text-slate-800">S/ {{ number_format($valorAlmacen, 2) }}</span>
                </div>
                <button type="button" data-abrir="modalInsumo"
                    class="px-4 py-2.5 rounded-xl bg-spark-dark hover:bg-black text-spark-lime font-black text-[11px] uppercase tracking-wider whitespace-nowrap">
                    <i class="fa-solid fa-plus mr-1"></i> Agregar
                </button>
            </div>
        </x-slot:acciones>

        <x-slot:filtros>
            <form method="GET" action="{{ route('produccion.almacen.index') }}" class="flex flex-wrap items-center gap-2">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                    <input type="search" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar insumo..."
                        class="w-52 pl-8 pr-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-spark-lime">
                </div>
                <select name="entrada" class="py-2 px-3 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800">
                    <option value="">Cómo entra: todos</option>
                    <option value="compra" @selected(request('entrada') === 'compra')>Se compra</option>
                    <option value="acopio" @selected(request('entrada') === 'acopio')>Entra por acopio</option>
                </select>
                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-spark-lime font-bold text-[10px] uppercase">Filtrar</button>
                @if(request('buscar') || request('entrada'))
                <a href="{{ route('produccion.almacen.index') }}" class="text-[11px] font-bold text-slate-500 underline">Quitar filtros</a>
                @endif
            </form>
        </x-slot:filtros>

        <x-slot:encabezados>
            <th class="text-left py-3 px-4 font-bold">Insumo</th>
            <th class="text-left py-3 px-4 font-bold">Categoría</th>
            <th class="text-right py-3 px-4 font-bold">En almacén</th>
            <th class="text-right py-3 px-4 font-bold">Mínimo</th>
            <th class="text-left py-3 px-4 font-bold">Cómo entra</th>
            <th class="text-right py-3 px-4 font-bold">Costo unit.</th>
            <th class="text-right py-3 px-4 font-bold">Valorizado</th>
            <th class="text-right py-3 px-4 font-bold">Entrada / Salida</th>
        </x-slot:encabezados>

        @foreach($insumos as $insumo)
        @php
            $saldo = $stocks->get($insumo->item_code, 0);
            $bajoElMinimo = $saldo < (float) $insumo->minimum_stock;
        @endphp
        <tr class="border-b border-slate-50 hover:bg-slate-50/60 transition">
            <td class="py-3 px-4 font-mono text-slate-400">{{ $insumo->id }}</td>
            <td class="py-3 px-4 font-bold text-slate-800">
                {{ $insumo->name }}
                <span class="block font-mono font-normal text-[10px] text-slate-400">{{ $insumo->item_code }}</span>
            </td>
            <td class="py-3 px-4 text-slate-500">{{ $insumo->category?->name ?? '—' }}</td>
            <td class="py-3 px-4 text-right font-black {{ $bajoElMinimo ? 'text-amber-600' : 'text-slate-800' }}">
                {{ number_format($saldo, 2) }} {{ $insumo->unit }}
            </td>
            <td class="py-3 px-4 text-right text-slate-500">{{ number_format($insumo->minimum_stock, 2) }}</td>
            <td class="py-3 px-4">
                <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full {{ $insumo->seCompra() ? 'bg-slate-100 text-slate-600' : 'bg-blue-100 text-blue-900' }}">
                    {{ $insumo->etiquetaEntrada() }}
                </span>
            </td>
            <td class="py-3 px-4 text-right text-slate-700">S/ {{ number_format($insumo->unit_cost, 2) }}</td>
            <td class="py-3 px-4 text-right font-bold text-slate-700">
                S/ {{ number_format($saldo * (float) $insumo->unit_cost, 2) }}
            </td>
            <td class="py-3 px-4">
                <form action="{{ route('produccion.almacen.insumos.ajuste', $insumo) }}" method="POST" class="flex justify-end gap-2">
                    @csrf
                    <input type="number" step="0.01" name="delta" required placeholder="+50 / -10"
                        class="w-24 p-1.5 bg-slate-50 border border-slate-200 rounded-lg text-right font-semibold">
                    <input type="text" name="notes" maxlength="255" placeholder="Motivo"
                        class="w-28 p-1.5 bg-slate-50 border border-slate-200 rounded-lg">
                    <button type="submit" class="px-3 py-1.5 rounded-lg bg-slate-900 text-spark-lime font-bold text-[10px] uppercase">Ajustar</button>
                </form>
            </td>
        </tr>
        @endforeach
    </x-tabla>

    {{-- ---------- LO QUE SE VENDE ---------- --}}
    <x-tabla
        titulo="Productos terminados"
        descripcion="Lo que sale de la planta y sí se vende. Entran al almacén cuando se cierra su lote de producción. El precio de cada uno se pone en Productos y Recetas."
        :coleccion="$productos"
        :columnas="7"
        vacio="Todavía no hay productos en el catálogo.">

        <x-slot:acciones>
            <a href="{{ route('produccion.productos.index') }}"
                class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] uppercase tracking-wider whitespace-nowrap">
                Editar precios y recetas
            </a>
        </x-slot:acciones>

        <x-slot:encabezados>
            <th class="text-left py-3 px-4 font-bold">Producto</th>
            <th class="text-left py-3 px-4 font-bold">Categoría</th>
            <th class="text-right py-3 px-4 font-bold">En almacén</th>
            <th class="text-right py-3 px-4 font-bold">Costo receta</th>
            <th class="text-right py-3 px-4 font-bold">Tarifa público</th>
            <th class="text-right py-3 px-4 font-bold">Margen local</th>
            <th class="text-right py-3 px-4 font-bold">Se puede producir</th>
        </x-slot:encabezados>

        @foreach($productos as $producto)
        <tr class="border-b border-slate-50 hover:bg-slate-50/60 transition">
            <td class="py-3 px-4 font-mono text-slate-400">{{ $producto->id }}</td>
            <td class="py-3 px-4 font-bold text-slate-800">
                {{ $producto->name }}
                <span class="block font-mono font-normal text-[10px] text-slate-400">{{ $producto->item_code }}</span>
            </td>
            <td class="py-3 px-4 text-slate-500">{{ $producto->category?->name ?? '—' }}</td>
            <td class="py-3 px-4 text-right font-black text-slate-800">
                {{ number_format($stocks->get($producto->item_code, 0), 2) }} {{ $producto->unit }}
            </td>
            @php
                $costo = $producto->recipeCost();
                $tarifa = $tipoPublico?->priceFor($producto);
                $margen = $tarifa === null ? null : $tarifa - $costo;
            @endphp
            <td class="py-3 px-4 text-right text-slate-500">S/ {{ number_format($costo, 2) }}</td>
            <td class="py-3 px-4 text-right font-bold text-slate-900">
                {{ $tarifa === null ? 'sin tarifa' : 'S/ '.number_format($tarifa, 2) }}
            </td>
            <td class="py-3 px-4 text-right font-black {{ $margen !== null && $margen < 0 ? 'text-rose-600' : 'text-spark-limeText' }}">
                {{ $margen === null ? '—' : 'S/ '.number_format($margen, 2) }}
            </td>
            <td class="py-3 px-4 text-right text-slate-600">
                {{ number_format($producto->maximumProducible(), 0) }} {{ $producto->unit }}
            </td>
        </tr>
        @endforeach
    </x-tabla>

    {{-- ---------- KARDEX ---------- --}}
    <x-tabla
        titulo="Kardex de insumos"
        descripcion="En qué se fue cada insumo y con qué saldo quedó. El consumo de un producto componente no aparece aquí: queda en los renglones de su lote."
        :coleccion="$movimientos"
        :columnas="6"
        vacio="Sin movimientos registrados todavía.">

        <x-slot:encabezados>
            <th class="text-left py-3 px-4 font-bold">Fecha</th>
            <th class="text-left py-3 px-4 font-bold">Insumo</th>
            <th class="text-left py-3 px-4 font-bold">Motivo</th>
            <th class="text-right py-3 px-4 font-bold">Cantidad</th>
            <th class="text-right py-3 px-4 font-bold">Saldo</th>
            <th class="text-left py-3 px-4 font-bold">Lote / Nota</th>
        </x-slot:encabezados>

        @foreach($movimientos as $mov)
        <tr class="border-b border-slate-50 hover:bg-slate-50/60 transition">
            <td class="py-3 px-4 font-mono text-slate-400">{{ $mov->id }}</td>
            <td class="py-3 px-4 text-slate-500">{{ $mov->created_at->format('d/m H:i') }}</td>
            <td class="py-3 px-4 font-bold text-slate-800">{{ $mov->supply->name }}</td>
            <td class="py-3 px-4 text-slate-600">{{ $mov->etiqueta() }}</td>
            <td class="py-3 px-4 text-right font-black {{ $mov->quantity < 0 ? 'text-rose-600' : 'text-spark-limeText' }}">
                {{ $mov->quantity > 0 ? '+' : '' }}{{ rtrim(rtrim(number_format($mov->quantity, 3, '.', ''), '0'), '.') }}
                {{ $mov->supply->unit }}
            </td>
            <td class="py-3 px-4 text-right text-slate-700">{{ rtrim(rtrim(number_format($mov->balance_after, 3, '.', ''), '0'), '.') }}</td>
            <td class="py-3 px-4 text-slate-400">
                @if($mov->order)<span class="font-mono">{{ $mov->order->batch_number }}</span> @endif
                {{ $mov->notes }}
            </td>
        </tr>
        @endforeach
    </x-tabla>

</div>

{{-- Modal: alta de insumo --}}
<div id="modalInsumo" hidden class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50">
    <div class="bg-white w-full max-w-lg rounded-3xl p-6 shadow-xl">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900">Agregar insumo</h3>
                <p class="text-[11px] text-slate-500">El insumo entra al almacén con su unidad y su stock inicial.</p>
            </div>
            <button type="button" data-cerrar class="text-slate-400 hover:text-slate-700 text-lg leading-none">&times;</button>
        </div>

        <form action="{{ route('produccion.almacen.insumos.store') }}" method="POST" class="space-y-3 text-xs">
            @csrf
            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Categoría</label>
                <select name="inventory_category_id" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                    @foreach($categorias as $categoria)
                    <option value="{{ $categoria->id }}">{{ $categoria->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Nombre</label>
                    <input type="text" name="name" required maxlength="120" placeholder="Ej. Botella 1 L"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                </div>
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Unidad de medida</label>
                    <select name="measurement_unit_id" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                        @foreach($unidades as $unidad)
                        <option value="{{ $unidad->id }}">{{ $unidad->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Stock inicial</label>
                    <input type="number" step="0.01" min="0" name="initial_stock" value="0"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                </div>
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Mínimo</label>
                    <input type="number" step="0.01" min="0" name="minimum_stock" value="0"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                </div>
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Costo S/</label>
                    <input type="number" step="0.01" min="0" name="unit_cost" value="0"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                </div>
            </div>
            <div>
                <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Cómo entra al almacén</label>
                <select name="entry_mode" class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                    <option value="compra">Se compra a un proveedor</option>
                    <option value="acopio">Entra por acopio (no se compra)</option>
                </select>
                <span class="text-[10px] text-slate-400 mt-1 block">
                    «Acopio» es para lo que llega por otra vía y se paga aparte, como la leche de los
                    pobladores: queda fuera de la pantalla de Compras para no contarla dos veces.
                </span>
            </div>
            <div class="flex gap-2 pt-2">
                <button type="button" data-cerrar class="flex-1 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] uppercase">Cancelar</button>
                <button type="submit" class="flex-1 bg-spark-dark hover:bg-black text-spark-lime font-black py-2.5 rounded-xl text-[11px] uppercase tracking-wider">Guardar insumo</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('modalInsumo');

    document.querySelectorAll('[data-abrir="modalInsumo"]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            modal.hidden = false;
            modal.querySelector('input[name="name"]')?.focus();
        });
    });

    modal.querySelectorAll('[data-cerrar]').forEach(function (boton) {
        boton.addEventListener('click', function () { modal.hidden = true; });
    });

    modal.addEventListener('click', function (evento) {
        if (evento.target === modal) { modal.hidden = true; }
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') { modal.hidden = true; }
    });

    // Si el alta falló, el formulario vuelve con errores: que el modal siga abierto.
    @if($errors->any())
    modal.hidden = false;
    @endif
})();
</script>
@endsection
