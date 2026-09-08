@props(['variant'=>'success'])
<div role="alert" {{ $attributes->class(['mf-alert',$variant==='error'?'error':'',$variant==='warning'?'warning':'']) }}>{{ $slot }}</div>
