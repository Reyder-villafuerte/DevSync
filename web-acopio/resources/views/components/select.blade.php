@props(['name','label','options'=>[],'value'=>''])
<label>{{ $label }}<select name="{{ $name }}" {{ $attributes }}><option value="">Seleccionar</option>@foreach($options as $key=>$text)<option value="{{ $key }}" @selected((string)old($name,$value)===(string)$key)>{{ $text }}</option>@endforeach</select>@error($name)<small class="text-danger">{{ $message }}</small>@enderror</label>
