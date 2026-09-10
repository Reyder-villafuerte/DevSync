<?php

return [
    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'usuarios'),
    ],

    'guards' => [
        // Panel Blade/Livewire: sesión clásica.
        'web' => [
            'driver' => 'session',
            'provider' => 'usuarios',
        ],
        // API móvil: token personal de Sanctum.
        'sanctum' => [
            'driver' => 'sanctum',
            'provider' => 'usuarios',
        ],
    ],

    'providers' => [
        // La tabla de identidades se llama `usuarios` (restricción de idioma).
        'usuarios' => [
            'driver' => 'eloquent',
            'model' => App\Models\Usuario::class,
        ],
    ],

    'passwords' => [
        'usuarios' => [
            'provider' => 'usuarios',
            'table' => 'tokens_restablecimiento_password',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,
];
