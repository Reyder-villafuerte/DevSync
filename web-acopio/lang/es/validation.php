<?php

return [
    'required' => 'El campo :attribute es obligatorio.', 'required_if' => 'El campo :attribute es obligatorio cuando :other es :value.',
    'email' => 'Ingresa un correo electrónico válido.', 'unique' => 'El valor de :attribute ya está registrado.', 'exists' => 'La selección de :attribute no es válida.',
    'string' => 'El campo :attribute debe ser texto.', 'numeric' => 'El campo :attribute debe ser numérico.', 'integer' => 'El campo :attribute debe ser un número entero.',
    'confirmed' => 'La confirmación de :attribute no coincide.', 'in' => 'La selección de :attribute no es válida.', 'regex' => 'El formato de :attribute no es válido.',
    'date' => 'El campo :attribute debe ser una fecha válida.', 'date_format' => 'El campo :attribute debe tener el formato :format.',
    'before' => 'El campo :attribute debe ser anterior a :date.', 'after' => 'El campo :attribute debe ser posterior a :date.', 'after_or_equal' => 'El campo :attribute debe ser igual o posterior a :date.',
    'uuid' => 'La referencia :attribute debe ser un UUID válido.', 'boolean' => 'El campo :attribute debe ser verdadero o falso.', 'alpha_num' => 'El campo :attribute solo admite letras y números.', 'distinct' => 'El campo :attribute contiene un valor duplicado.', 'array' => 'El campo :attribute debe ser una lista.',
    'min' => ['string' => 'El campo :attribute debe tener al menos :min caracteres.', 'numeric' => 'El campo :attribute debe ser al menos :min.', 'array' => 'Selecciona al menos :min elementos.'],
    'max' => ['string' => 'El campo :attribute no puede superar :max caracteres.', 'numeric' => 'El campo :attribute no puede superar :max.', 'array' => 'Selecciona como máximo :max elementos.'],
    'between' => ['string' => 'El campo :attribute debe tener entre :min y :max caracteres.', 'numeric' => 'El campo :attribute debe estar entre :min y :max.'],
    'gt' => ['numeric' => 'El campo :attribute debe ser mayor que :value.'], 'lte' => ['numeric' => 'El campo :attribute debe ser menor o igual que :value.'],
    'password' => ['letters' => 'La contraseña debe incluir letras.', 'mixed' => 'La contraseña debe incluir mayúsculas y minúsculas.', 'numbers' => 'La contraseña debe incluir números.', 'symbols' => 'La contraseña debe incluir un símbolo.'],
    'attributes' => ['email' => 'correo', 'password' => 'contraseña', 'documento' => 'documento', 'telefono' => 'teléfono', 'productor_id' => 'productor', 'ruta_id' => 'ruta', 'sector_id' => 'sector', 'zona_id' => 'zona', 'role_id' => 'rol', 'agua_agregada_porcentaje' => 'porcentaje de agua agregada', 'litros_leche' => 'litros de leche', 'moldes_obtenidos' => 'moldes obtenidos'],
];
