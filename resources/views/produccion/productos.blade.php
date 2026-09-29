@extends('layouts.app')

@section('title', 'Productos y Recetas')

@php
    $hayConQueArmar = $categorias->isNotEmpty() && ($insumos->isNotEmpty() || $componentes->isNotEmpty());
@endphp

@section('content')
<div class="space-y-6">

    @if($categorias->isEmpty())
    <div class="bg-amber-50 border border-amber-200 rounded-3xl p-5 text-xs text-amber-800">
        Primero crea una categoría en
        <a href="{{ route('produccion.categorias.index') }}" class="underline font-bold">Categorías y Unidades</a>.
    </div>
    @elseif($insumos->isEmpty() && $componentes->isEmpty())
    <div class="bg-amber-50 border border-amber-200 rounded-3xl p-5 text-xs text-amber-800">
        Primero da de alta al menos un insumo en
        <a href="{{ route('produccion.almacen.index') }}" class="underline font-bold">Almacén e Insumos</a>.
    </div>
    @endif

    <x-tabla
        titulo="Productos y Recetas"
        descripcion="Qué sabe hacer la planta. Cada producto declara qué consume una unidad —insumos del almacén u otro producto ya fabricado—, cuánto dura su proceso y su tarifa para cada tipo de cliente."
        :coleccion="$productos"
        :columnas="8"
        vacio="Ningún producto coincide con el filtro.">

        <x-slot:acciones>
            @if($hayConQueArmar)
            <button type="button" data-nuevo-producto
                class="px-4 py-2.5 rounded-xl bg-spark-dark hover:bg-black text-spark-lime font-black text-[11px] uppercase tracking-wider whitespace-nowrap">
                <i class="fa-solid fa-plus mr-1"></i> Agregar
            </button>
            @endif
        </x-slot:acciones>

        <x-slot:filtros>
            <form method="GET" action="{{ route('produccion.productos.index') }}" class="flex flex-wrap items-center gap-2">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[10px]"></i>
                    <input type="search" name="buscar" value="{{ request('buscar') }}" placeholder="Nombre o código..."
                        class="w-52 pl-8 pr-3 py-2 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 focus:ring-2 focus:ring-spark-lime">
                </div>
                <select name="categoria" class="py-2 px-3 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800">
                    <option value="">Todas las categorías</option>
                    @foreach($categorias as $categoria)
                    <option value="{{ $categoria->id }}" @selected((string) request('categoria') === (string) $categoria->id)>{{ $categoria->name }}</option>
                    @endforeach
                </select>
                <select name="estado" class="py-2 px-3 bg-white border border-slate-200 rounded-xl text-xs font-semibold text-slate-800">
                    <option value="">Activos e inactivos</option>
                    <option value="activos" @selected(request('estado') === 'activos')>Solo activos</option>
                    <option value="inactivos" @selected(request('estado') === 'inactivos')>Solo inactivos</option>
                </select>
                <button type="submit" class="px-4 py-2 rounded-xl bg-slate-900 text-spark-lime font-bold text-[10px] uppercase">Filtrar</button>
                @if(request('buscar') || request('categoria') || request('estado'))
                <a href="{{ route('produccion.productos.index') }}" class="text-[11px] font-bold text-slate-500 underline">Quitar filtros</a>
                @endif
            </form>
        </x-slot:filtros>

        <x-slot:encabezados>
            <th class="text-left py-3 px-4 font-bold">Producto</th>
            <th class="text-left py-3 px-4 font-bold">Categoría</th>
            <th class="text-left py-3 px-4 font-bold">Receta por unidad</th>
            <th class="text-right py-3 px-4 font-bold">Proceso</th>
            <th class="text-right py-3 px-4 font-bold">Costo receta</th>
            <th class="text-right py-3 px-4 font-bold">En stock</th>
            <th class="text-right py-3 px-4 font-bold">Alcanza para</th>
            <th class="text-right py-3 px-4 font-bold">Acciones</th>
        </x-slot:encabezados>

        @foreach($productos as $producto)
        <tr class="border-b border-slate-50 hover:bg-slate-50/60 transition align-top">
            <td class="py-3 px-4 font-mono text-slate-400">{{ $producto->id }}</td>
            <td class="py-3 px-4 font-bold {{ $producto->is_active ? 'text-slate-800' : 'text-slate-400 line-through' }}">
                {{ $producto->name }}
                <span class="block font-mono font-normal text-[10px] text-slate-400 no-underline">{{ $producto->item_code }}</span>
                @unless($producto->is_active)
                <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 no-underline">Inactivo</span>
                @endunless
            </td>
            <td class="py-3 px-4 text-slate-500">{{ $producto->category->name }}</td>
            <td class="py-3 px-4 text-slate-600">
                @forelse($producto->recipeItems as $renglon)
                <div>
                    {{ rtrim(rtrim(number_format($renglon->quantity_per_unit, 4, '.', ''), '0'), '.') }}
                    {{ $renglon->unidadIngrediente() }} de <strong>{{ $renglon->nombreIngrediente() }}</strong>
                    @if($renglon->esProducto())
                    <span class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded-full bg-lime-100 text-spark-limeText">Producto</span>
                    @endif
                </div>
                @empty
                <span class="text-slate-300 italic">Sin receta</span>
                @endforelse
            </td>
            <td class="py-3 px-4 text-right text-slate-600">{{ number_format($producto->process_hours, 2) }} h</td>
            <td class="py-3 px-4 text-right text-slate-500">S/ {{ number_format($producto->recipeCost(), 2) }}</td>
            <td class="py-3 px-4 text-right font-black text-slate-800">{{ number_format($producto->stock(), 2) }}</td>
            <td class="py-3 px-4 text-right font-bold text-spark-limeText">
                {{ number_format($producto->maximumProducible(), 0) }} {{ $producto->unit }}
            </td>
            <td class="py-3 px-4 text-right">
                <button type="button" data-abrir-modal="modalProducto{{ $producto->id }}"
                    class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10px] uppercase">
                    Editar
                </button>
            </td>
        </tr>
        @endforeach
    </x-tabla>

</div>

{{-- Modal por producto: su receta y sus tarifas --}}
@foreach($productos as $producto)
<div id="modalProducto{{ $producto->id }}" hidden class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50">
    <div class="bg-white w-full max-w-3xl rounded-3xl p-6 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900">
                    #{{ $producto->id }} · {{ $producto->name }}
                </h3>
                <p class="text-[11px] text-slate-500">
                    {{ $producto->category->name }} · <span class="font-mono">{{ $producto->item_code }}</span>
                    @if($producto->process_notes) · {{ $producto->process_notes }} @endif
                </p>
            </div>
            <button type="button" data-cerrar class="text-slate-400 hover:text-slate-700 text-lg leading-none">&times;</button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            {{-- Receta --}}
            <div>
                <h4 class="text-[10px] uppercase font-black text-slate-500 tracking-wider mb-2">Receta por unidad</h4>

                <form action="{{ route('produccion.productos.receta', $producto) }}" method="POST" class="space-y-2 text-xs">
                    @csrf
                    @foreach($producto->recipeItems as $i => $renglon)
                    @php($valorActual = $renglon->esProducto() ? 'p:'.$renglon->component_product_id : 'i:'.$renglon->supply_id)
                    <div class="flex items-end gap-2">
                        <div class="flex-1">
                            <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">Ingrediente</label>
                            <select name="receta[{{ $i }}][ingrediente]"
                                class="w-full p-2 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                                <option value="">— Quitar de la receta —</option>
                                @if($insumos->isNotEmpty())
                                <optgroup label="Insumos del almacén">
                                    @foreach($insumos as $insumo)
                                    <option value="i:{{ $insumo->id }}" @selected($valorActual === 'i:'.$insumo->id)>{{ $insumo->name }} ({{ $insumo->category->name }} · {{ $insumo->unit }})</option>
                                    @endforeach
                                </optgroup>
                                @endif
                                @if($componentes->isNotEmpty())
                                <optgroup label="Productos de la planta">
                                    @foreach($componentes as $componente)
                                    <option value="p:{{ $componente->id }}" @selected($valorActual === 'p:'.$componente->id)>{{ $componente->name }} ({{ $componente->unit }})</option>
                                    @endforeach
                                </optgroup>
                                @endif
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">Cantidad</label>
                            <input type="number" step="0.0001" min="0.0001" name="receta[{{ $i }}][quantity_per_unit]"
                                value="{{ rtrim(rtrim(number_format($renglon->quantity_per_unit, 4, '.', ''), '0'), '.') }}"
                                class="w-24 p-2 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                        </div>
                    </div>
                    @endforeach

                    @php($siguiente = $producto->recipeItems->count())
                    <div class="flex items-end gap-2 pt-2 border-t border-slate-100">
                        <div class="flex-1">
                            <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">Agregar ingrediente</label>
                            <select name="receta[{{ $siguiente }}][ingrediente]"
                                class="w-full p-2 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                                <option value="">— Ninguno —</option>
                                @if($insumos->isNotEmpty())
                                <optgroup label="Insumos del almacén">
                                    @foreach($insumos as $insumo)
                                    <option value="i:{{ $insumo->id }}">{{ $insumo->name }} ({{ $insumo->category->name }} · {{ $insumo->unit }})</option>
                                    @endforeach
                                </optgroup>
                                @endif
                                @if($componentes->isNotEmpty())
                                <optgroup label="Productos de la planta">
                                    @foreach($componentes as $componente)
                                    <option value="p:{{ $componente->id }}">{{ $componente->name }} ({{ $componente->unit }})</option>
                                    @endforeach
                                </optgroup>
                                @endif
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">Cantidad</label>
                            <input type="number" step="0.0001" min="0.0001" name="receta[{{ $siguiente }}][quantity_per_unit]" value="1"
                                class="w-24 p-2 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                        </div>
                    </div>

                    <button type="submit" class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10px] uppercase">
                        Guardar receta
                    </button>
                </form>
            </div>

            {{-- Tarifas y proceso --}}
            <div>
                <h4 class="text-[10px] uppercase font-black text-slate-500 tracking-wider mb-2">Tarifas y proceso</h4>

                <form action="{{ route('produccion.productos.precios', $producto) }}" method="POST" class="space-y-3 text-xs">
                    @csrf
                    @php($propias = $tiposCliente->filter(fn ($t) => $t->tieneTarifaPropia($producto)))
                    <div class="border border-slate-200 rounded-2xl p-3">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[10px] uppercase font-bold text-slate-600">Tarifa por tipo de cliente</span>
                            <button type="button" data-agregar-tarifa
                                class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10px] uppercase">
                                <i class="fa-solid fa-plus mr-1"></i> Agregar
                            </button>
                        </div>

                        <div data-tarifas data-iniciales="{{ json_encode($propias->map(fn ($t) => [
                            'client_type_id' => $t->id,
                            'price_per_unit' => number_format($t->priceFor($producto), 2, '.', ''),
                        ])->values()) }}" class="space-y-2"></div>

                        @php($sinTarifa = $tiposCliente->reject(fn ($t) => $t->tieneTarifaPropia($producto)))
                        @if($sinTarifa->isNotEmpty())
                        <span class="text-[10px] text-amber-600 mt-2 block">
                            Sin tarifa propia, pagan la del público: {{ $sinTarifa->pluck('name')->implode(', ') }}
                        </span>
                        @endif
                    </div>

                    <div class="flex items-end gap-3">
                        <div>
                            <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">Proceso (horas)</label>
                            <input type="number" step="0.25" min="0" name="process_hours" value="{{ $producto->process_hours }}"
                                class="w-28 p-2 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                        </div>
                        <label class="flex items-center gap-2 text-[11px] font-bold text-slate-600 pb-2">
                            <input type="checkbox" name="is_active" value="1" @checked($producto->is_active)> Activo
                        </label>
                        <button type="submit" class="ml-auto px-3 py-2 rounded-xl bg-slate-900 text-spark-lime font-bold text-[10px] uppercase">
                            Guardar tarifas
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endforeach

@if($hayConQueArmar)
{{-- Modal: crear producto --}}
<div id="modalProducto" hidden class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50">
    <div class="bg-white w-full max-w-3xl rounded-3xl p-6 shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900">Crear producto</h3>
                <p class="text-[11px] text-slate-500">
                    Arma la receta con insumos del almacén —o con otro producto que la planta ya fabrique— y
                    ponle su tarifa a cada tipo de cliente.
                </p>
            </div>
            <button type="button" data-cerrar class="text-slate-400 hover:text-slate-700 text-lg leading-none">&times;</button>
        </div>

        <form action="{{ route('produccion.productos.store') }}" method="POST" class="space-y-4 text-xs">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Categoría</label>
                    <select name="inventory_category_id" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                        @foreach($categorias as $categoria)
                        <option value="{{ $categoria->id }}">{{ $categoria->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Nombre</label>
                    <input type="text" name="name" required maxlength="120" value="{{ old('name') }}" placeholder="Ej. Yogurt de fresa 1 L"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                </div>
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Unidad de medida</label>
                    <select name="measurement_unit_id" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                        @foreach($unidades as $unidad)
                        <option value="{{ $unidad->id }}" @selected(old('measurement_unit_id') == $unidad->id)>{{ $unidad->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Proceso (horas)</label>
                    <input type="number" step="0.25" min="0" name="process_hours" required value="{{ old('process_hours', 0) }}"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800">
                </div>
            </div>

            <div class="border border-slate-200 rounded-2xl p-3">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] uppercase font-bold text-slate-600">Tarifa por tipo de cliente</span>
                    <button type="button" data-agregar-tarifa
                        class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10px] uppercase">
                        <i class="fa-solid fa-plus mr-1"></i> Agregar tarifa
                    </button>
                </div>

                <div data-tarifas class="space-y-2"></div>

                <span class="text-[10px] text-slate-400 mt-2 block">
                    Ponle su precio a cada tipo que vaya a comprarlo. Al tipo que no listes se le cobra la
                    tarifa del público general.
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Código (opcional)</label>
                    <input type="text" name="item_code" maxlength="50" value="{{ old('item_code') }}" placeholder="YOGURT_FRESA_1L"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-mono text-slate-800">
                </div>
                <div>
                    <label class="block text-[10px] uppercase font-bold text-slate-600 mb-1">Notas del proceso (opcional)</label>
                    <input type="text" name="process_notes" maxlength="1000" value="{{ old('process_notes') }}"
                        placeholder="Ej. Fermentar a 42 °C, enfriar y embotellar."
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800">
                </div>
            </div>

            <div class="border border-slate-200 rounded-2xl p-3">
                <div class="flex items-center justify-between mb-2">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-600 block">Receta por unidad</span>
                        <span class="text-[10px] text-slate-400">Cuánto consume una unidad. Aquí van los litros de leche.</span>
                    </div>
                    <button type="button" id="agregarRenglon"
                        class="px-3 py-1.5 rounded-lg bg-spark-dark hover:bg-black text-spark-lime font-bold text-[10px] uppercase">
                        <i class="fa-solid fa-plus mr-1"></i> Insumo
                    </button>
                </div>

                <div id="recetaFilas" class="space-y-2"></div>
            </div>

            <div class="flex gap-2 pt-2">
                <button type="button" data-cerrar class="flex-1 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] uppercase">Cancelar</button>
                <button type="submit" class="flex-1 bg-spark-dark hover:bg-black text-spark-lime font-black py-2.5 rounded-xl text-[11px] uppercase tracking-wider">
                    Crear producto
                </button>
            </div>
        </form>
    </div>
</div>

<template id="plantillaRenglon">
    <div class="grid grid-cols-12 gap-2 items-end receta-fila">
        <div class="col-span-6">
            <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">Ingrediente</label>
            <select data-campo="ingrediente" required class="w-full p-2.5 bg-white border border-slate-200 rounded-xl font-semibold text-slate-800">
                @if($insumos->isNotEmpty())
                <optgroup label="Insumos del almacén">
                    @foreach($insumos as $insumo)
                    <option value="i:{{ $insumo->id }}">{{ $insumo->name }} ({{ $insumo->category->name }} · {{ $insumo->unit }})</option>
                    @endforeach
                </optgroup>
                @endif
                @if($componentes->isNotEmpty())
                <optgroup label="Productos de la planta">
                    @foreach($componentes as $componente)
                    <option value="p:{{ $componente->id }}">{{ $componente->name }} ({{ $componente->unit }})</option>
                    @endforeach
                </optgroup>
                @endif
            </select>
        </div>
        <div class="col-span-4">
            <label class="block text-[10px] uppercase font-bold text-slate-500 mb-1">Cantidad por unidad</label>
            <input type="number" step="0.0001" min="0.0001" data-campo="quantity_per_unit" required placeholder="Ej. 10"
                class="w-full p-2.5 bg-white border border-slate-200 rounded-xl font-semibold text-slate-800">
        </div>
        <div class="col-span-2">
            <button type="button" class="quitar-fila w-full px-3 py-2.5 rounded-xl bg-rose-50 text-rose-600 font-bold text-[10px] uppercase">Quitar</button>
        </div>
    </div>
</template>

<script>
(function () {
    const contenedor = document.getElementById('recetaFilas');
    const plantilla = document.getElementById('plantillaRenglon');

    // Los name[] se renumeran en cada cambio para que Laravel reciba receta[0], receta[1]...
    function renumerar() {
        contenedor.querySelectorAll('.receta-fila').forEach(function (fila, indice) {
            fila.querySelectorAll('[data-campo]').forEach(function (campo) {
                campo.name = 'receta[' + indice + '][' + campo.dataset.campo + ']';
            });
        });
    }

    function agregar() {
        contenedor.appendChild(plantilla.content.cloneNode(true));
        renumerar();
    }

    document.getElementById('agregarRenglon').addEventListener('click', agregar);

    contenedor.addEventListener('click', function (evento) {
        if (evento.target.closest('.quitar-fila')) {
            evento.target.closest('.receta-fila').remove();
            renumerar();
        }
    });

    agregar();
})();
</script>
@endif

<template id="plantillaTarifa">
    <div class="flex items-end gap-2" data-tarifa>
        <div class="flex-1">
            <select data-campo="client_type_id" required
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-800 text-xs">
                @foreach($tiposCliente as $tipo)
                <option value="{{ $tipo->id }}">{{ $tipo->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-28">
            <input type="number" step="0.01" min="0" data-campo="price_per_unit" required placeholder="S/"
                class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-right font-semibold text-slate-800 text-xs">
        </div>
        <button type="button" data-quitar-tarifa
            class="px-3 py-2.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-[10px] uppercase">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
</template>

<script>
(function () {
    const plantilla = document.getElementById('plantillaTarifa');

    // Cada formulario de tarifas es independiente: el de alta y el de cada
    // producto conviven en la misma pantalla.
    document.querySelectorAll('[data-tarifas]').forEach(function (contenedor) {
        const formulario = contenedor.closest('form');

        function renumerar() {
            contenedor.querySelectorAll('[data-tarifa]').forEach(function (fila, indice) {
                fila.querySelectorAll('[data-campo]').forEach(function (campo) {
                    campo.name = 'tarifas[' + indice + '][' + campo.dataset.campo + ']';
                });
            });
        }

        function agregar(valores) {
            const fila = plantilla.content.firstElementChild.cloneNode(true);
            const select = fila.querySelector('[data-campo="client_type_id"]');
            const precio = fila.querySelector('[data-campo="price_per_unit"]');

            if (valores) {
                select.value = valores.client_type_id;
                precio.value = valores.price_per_unit;
            }

            fila.querySelector('[data-quitar-tarifa]').addEventListener('click', function () {
                fila.remove();
                renumerar();
            });

            contenedor.appendChild(fila);
            renumerar();
        }

        formulario?.querySelectorAll('[data-agregar-tarifa]').forEach(function (boton) {
            boton.addEventListener('click', function () { agregar(null); });
        });

        // El formulario de edición llega con las tarifas que el producto ya tiene.
        const iniciales = contenedor.dataset.iniciales;

        if (iniciales) {
            JSON.parse(iniciales).forEach(agregar);
        }
    });

    // --- Modales ---
    function cerrar(modal) {
        if (modal) { modal.hidden = true; }
    }

    document.querySelectorAll('[data-nuevo-producto]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            document.getElementById('modalProducto').hidden = false;
        });
    });

    document.querySelectorAll('[data-abrir-modal]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            document.getElementById(boton.dataset.abrirModal).hidden = false;
        });
    });

    document.querySelectorAll('[data-cerrar]').forEach(function (boton) {
        boton.addEventListener('click', function () {
            cerrar(boton.closest('[id^="modalProducto"]'));
        });
    });

    document.querySelectorAll('[id^="modalProducto"]').forEach(function (modal) {
        modal.addEventListener('click', function (evento) {
            if (evento.target === modal) { cerrar(modal); }
        });
    });

    document.addEventListener('keydown', function (evento) {
        if (evento.key === 'Escape') {
            document.querySelectorAll('[id^="modalProducto"]').forEach(cerrar);
        }
    });

    // Si el alta falló, el formulario vuelve con errores: que el modal siga abierto.
    @if($errors->any())
    document.getElementById('modalProducto')?.removeAttribute('hidden');
    @endif
})();
</script>
@endsection
