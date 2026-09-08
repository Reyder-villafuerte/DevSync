@extends('layouts.app',['title'=>$definition['title']])
@section('content')<div class="page-header">
    <div><span class="eyebrow">DETALLE E HISTORIAL</span>
        <h1>{{ $row->nombre ?? $row->codigo_lote ?? $definition['title'].' #'.$row->id }}</h1>
    </div><a href="{{ $base }}">← Volver</a>
</div>
<div class="mf-card">
    <dl class="detail-grid">@foreach($definition['columns'] as $field=>$label)<div>
            <dt>{{ $label }}</dt>
            <dd>{{ app(App\Services\DisplayValue::class)->get($row,$field) }}</dd>
        </div>@endforeach</dl>@if($module==='lotes')<span class="mf-badge {{ $row->rendimiento>=11 && $row->rendimiento<=12?'':'warning' }}">{{ $row->rendimiento>=11 && $row->rendimiento<=12?'Rendimiento esperado':'Fuera del rendimiento esperado' }}</span>@endif
</div>@if($history->isNotEmpty())<div class="mf-card">
    <h2>{{ $module==='producers'?'Historial de entregas':'Registros relacionados' }}</h2>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>@foreach(array_keys($history->first()) as $key)<th>{{ str_replace('_',' ',$key) }}</th>@endforeach</tr>
            </thead>
            <tbody>@foreach($history as $item)<tr>@foreach($item as $value)<td>{{ is_array($value)?json_encode($value,JSON_UNESCAPED_UNICODE):$value }}</td>@endforeach</tr>@endforeach</tbody>
        </table>
    </div>
</div>@endif @endsection
