<?php

namespace App\Models;

use App\Enums\RolUsuario;
use App\Support\Concerns\Sincronizable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Identidad del sistema. Guard 'web' (sesión) para el panel Blade y guard
 * 'sanctum' (token) para el móvil, ambos sobre esta misma tabla.
 */
class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Sincronizable;

    protected $table = 'usuarios';

    protected $fillable = [
        'nombres', 'apellidos', 'dni', 'email', 'telefono', 'password', 'rol', 'activo',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'activo' => 'boolean',
            'rol' => RolUsuario::class,
            'ultimo_acceso_en' => 'datetime',
        ];
    }

    // --- Relaciones ---
    public function acopiador(): HasOne
    {
        return $this->hasOne(Acopiador::class, 'usuario_id')->whereNull('vigente_hasta');
    }

    public function productor(): HasOne
    {
        return $this->hasOne(Productor::class, 'usuario_id');
    }

    public function dispositivos(): HasMany
    {
        return $this->hasMany(Dispositivo::class, 'usuario_id');
    }

    // --- Scopes ---
    public function scopeActivos($q)
    {
        return $q->where('activo', true);
    }

    public function scopeRol($q, RolUsuario|string $rol)
    {
        return $q->where('rol', $rol instanceof RolUsuario ? $rol->value : $rol);
    }

    // --- Helpers de autorización ---
    public function esRol(RolUsuario ...$roles): bool
    {
        return in_array($this->rol, $roles, true);
    }
}
