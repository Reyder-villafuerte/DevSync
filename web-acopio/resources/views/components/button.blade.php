@props(['variant'=>'primary','href'=>null])
@if($href)<a href="{{ $href }}" {{ $attributes->class(['mf-button',$variant==='secondary'?'secondary':'',$variant==='danger'?'danger':'']) }}>{{ $slot }}</a>@else<button {{ $attributes->class(['mf-button',$variant==='secondary'?'secondary':'',$variant==='danger'?'danger':'']) }}>{{ $slot }}</button>@endif
