<?php

use Laravel\Sanctum\Sanctum;

return [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', 'localhost,127.0.0.1')),
    'guard' => ['web'],

    // Los tokens del móvil no expiran por tiempo (el acopiador puede pasar
    // días sin cobertura); se revocan explícitamente al dar de baja el
    // dispositivo o el usuario.
    'expiration' => null,
    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', 'milkflow_'),

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],
];
