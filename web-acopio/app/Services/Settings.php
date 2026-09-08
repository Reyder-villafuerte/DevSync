<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class Settings
{
    public static function get(string $key, string $default = ''): string
    {
        return (string) (DB::table('configuraciones')->where('clave', $key)->value('valor') ?? $default);
    }
}
