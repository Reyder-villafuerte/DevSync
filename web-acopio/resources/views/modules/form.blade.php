@extends('layouts.app',['title'=>$definition['title']])
@section('content')<div class="page-header">
    <div><span class="eyebrow">{{ $row?'ACTUALIZAR':'REGISTRAR' }}</span>
        <h1>{{ $definition['title'] }}</h1>
        <p class="muted">La fecha, hora y usuario responsable se registran automáticamente.</p>
    </div><a href="{{ $base }}">← Volver</a>
</div>
<div class="mf-card" style="max-width:850px">@if($module==='ventas')<div class="mf-alert">Precio automático: Mayorista S/ 20 · Productor / Socio S/ 18 · Público General S/ 21.</div>@endif @if($module==='calidad')<div class="mf-alert warning">pH menor a 6.5: rechazo y capacitación BPO. Agua agregada: sanción según porcentaje y reincidencia.</div>@endif @if($module==='lotes')<p class="muted">Solo se utiliza leche CONFORME disponible. Rendimiento esperado: 11–12 moldes por 100 litros.</p>@endif<form method="POST" action="{{ $base }}{{ $row?'/'.$row->id:'' }}">@csrf @if($row) @method('PATCH') @endif<div class="form-grid">@foreach($definition['fields'] as $name=>$field) @php($value=old($name,$row?->$name ?? ($field[1]==='hidden'?(string)Illuminate\Support\Str::uuid():($name==='activo'?1:'')))) @if($field[1]==='hidden')<input type="hidden" name="{{ $name }}" value="{{ $value }}">@else<label>{{ $field[0] }}@if($field[1]==='textarea')<textarea name="{{ $name }}">{{ $value }}</textarea>@elseif(in_array($field[1],['text','number','date','time']))<input name="{{ $name }}" type="{{ $field[1] }}" value="{{ $field[1]==='time'?substr($value,0,5):$value }}" @if($field[1]==='number' ) step="any" @endif>@else<select name="{{ $name }}{{ $field[1]==='sectores_multi'?'[]':'' }}" @if($field[1]==='sectores_multi' ) multiple @endif>
                    <option value="">Seleccionar</option>@foreach(App\Services\Modules::options($field[1]) as $key=>$label)<option value="{{ $key }}" @selected($field[1]==='sectores_multi' ?in_array($key,old('sectores',$row?->sectores?->pluck('id')->all()??[])):(string)$value===(string)$key)>{{ $label }}</option>@endforeach
                </select>@endif @error($name)<small class="text-danger">{{ $message }}</small>@enderror</label>@endif @endforeach</div>
        <div class="form-actions"><button class="mf-button">{{ $row?'GUARDAR CAMBIOS':'GUARDAR REGISTRO' }}</button><a class="mf-button secondary" href="{{ $base }}">Cancelar</a></div>
    </form>
</div>@endsection
