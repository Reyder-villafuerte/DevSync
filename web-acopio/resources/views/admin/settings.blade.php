@extends('layouts.app',['title'=>'Configuración'])
@section('content')<div class="page-header">
    <h1>Configuración de planta</h1>
</div>
<div class="mf-card" style="max-width:800px">
    <form method="POST">@csrf<div class="form-grid">@foreach(['hora_inicio'=>'Inicio de acopio','hora_fin'=>'Fin de acopio','tarifa_normal'=>'Tarifa normal por litro (S/)','tarifa_castigo'=>'Tarifa de castigo por litro (S/)','descuento_reincidencia'=>'Descuento por reincidencia (S/)'] as $key=>$label)<label>{{ $label }}<input name="{{ $key }}" type="{{ str_starts_with($key,'hora')?'time':'number' }}" step="{{ str_starts_with($key,'hora')?'60':'0.01' }}" value="{{ old($key,$settings[$key]) }}" required></label>@endforeach</div>
        <p class="muted mt-3">Distrito operativo: HUARI. Ciclo semanal: jueves a miércoles. Precios de queso: S/20, S/18 y S/21 según tipo de cliente.</p><button class="mf-button">GUARDAR CONFIGURACIÓN</button>
    </form>
</div>@endsection
