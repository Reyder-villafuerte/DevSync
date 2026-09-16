@props(['name', 'label', 'autocomplete' => 'new-password', 'minlength' => null])
<div x-data="{ visible: false }">
<label for="{{ $name }}">{{ $label }}</label>
<div class="auth-password">
<input id="{{ $name }}" name="{{ $name }}" type="password" :type="visible ? 'text' : 'password'" autocomplete="{{ $autocomplete }}" @if($minlength) minlength="{{ $minlength }}" @endif required>
<button x-cloak type="button" @click="visible = !visible" :aria-pressed="visible" :aria-label="(visible ? 'Ocultar' : 'Mostrar') + ' {{ mb_strtolower($label) }}'" aria-controls="{{ $name }}">
<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path x-show="visible" d="m3 3 18 18"/></svg>
</button>
</div></div>
