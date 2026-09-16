@props(['state'=>'ACTIVO'])
<span {{ $attributes->class(['mf-badge','warning'=>in_array($state,['PENDIENTE','OBSERVADA']),'error'=>in_array($state,['RECHAZADA','RECHAZADO','INACTIVO','ERROR'])]) }}>{{ $slot->isEmpty()?$state:$slot }}</span>
