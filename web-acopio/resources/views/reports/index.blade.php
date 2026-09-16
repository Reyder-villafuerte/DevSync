@extends('layouts.app',['title'=>'Reportes'])
@section('content')<div class="page-header">
    <div><span class="eyebrow">INFORMACIÓN PARA DECIDIR</span>
        <h1>Reportes</h1>
        <p class="muted">{{ $from->format('d/m/Y') }} al {{ $to->format('d/m/Y') }} · Semana operativa jueves a miércoles</p>
    </div>
</div>
<form class="mf-card" method="GET">
    <div class="form-grid"><label>Módulo<select name="modulo">@foreach($allowed as $m)<option value="{{ $m }}" @selected($module===$m)>{{ App\Services\Modules::get($m)['title'] }}</option>@endforeach</select></label><label>Periodo<select name="periodo">@foreach(['diario'=>'Diario','semanal'=>'Semanal','mensual'=>'Mensual','personalizado'=>'Personalizado'] as $key=>$label)<option value="{{ $key }}" @selected($period===$key)>{{ $label }}</option>@endforeach</select></label><label>Fecha inicial<input type="date" name="desde" value="{{ request('desde') }}"></label><label>Fecha final<input type="date" name="hasta" value="{{ request('hasta') }}"></label>@foreach(['productor_id'=>['Productor','todos_productores'],'acopiador_id'=>['Acopiador','acopiadores'],'zona_id'=>['Zona','zonas'],'sector_id'=>['Sector','sectores'],'ruta_id'=>['Ruta','rutas']] as $field=>$spec)<label>{{ $spec[0] }}<select name="{{ $field }}">
                <option value="">Todos</option>@foreach(App\Services\Modules::options($spec[1]) as $id=>$label)<option value="{{ $id }}" @selected(request($field)==$id)>{{ $label }}</option>@endforeach
            </select></label>@endforeach<label>Estado<input name="estado" value="{{ request('estado') }}" placeholder="CONFORME, RECHAZADA…"></label><label>Incidencias de calidad<select name="calidad_filtro">
                <option value="">Todas</option>
                <option value="adulteracion" @selected(request('calidad_filtro')==='adulteracion' )>Adulteraciones</option>
                <option value="acidez" @selected(request('calidad_filtro')==='acidez' )>Rechazos por acidez</option>
            </select></label></div>
    <div class="form-actions"><button class="mf-button">APLICAR FILTROS</button><button class="mf-button secondary" name="export" value="pdf">Descargar PDF</button><button class="mf-button secondary" name="export" value="csv">CSV / Excel</button></div>
    <p class="muted mt-3">Los filtros territoriales se aplican a entregas, calidad y productores. Stock muestra existencias actuales.</p>
</form>
<div class="stats">@foreach($totals as $label=>$value)<div class="mf-card stat-card"><small>{{ $label }}</small><strong>{{ number_format($value,2) }}</strong></div>@endforeach</div>
<div class="mf-card table-wrap">
    <table>
        <thead>
            <tr>@foreach($definition['columns'] as $label)<th>{{ $label }}</th>@endforeach</tr>
        </thead>
        <tbody>@forelse($rows as $row)<tr>@foreach($definition['columns'] as $key=>$label)<td>{{ app(App\Services\DisplayValue::class)->get($row,$key) }}</td>@endforeach</tr>@empty<tr>
                <td class="empty" colspan="{{ count($definition['columns']) }}">No hay resultados para estos filtros.</td>
            </tr>@endforelse</tbody>
    </table>{{ $rows->links() }}
</div>@endsection
