@props(['title','subtitle'=>'','eyebrow'=>'MILKFLOW'])
<div class="page-header"><div><span class="eyebrow">{{ $eyebrow }}</span><h1>{{ $title }}</h1>@if($subtitle)<p class="muted">{{ $subtitle }}</p>@endif</div>{{ $slot }}</div>
