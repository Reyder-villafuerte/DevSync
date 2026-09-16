@props(['name','label','type'=>'text','value'=>''])
<label>{{ $label }}<input name="{{ $name }}" type="{{ $type }}" value="{{ old($name,$value) }}" {{ $attributes }}>@error($name)<small class="text-danger">{{ $message }}</small>@enderror</label>
