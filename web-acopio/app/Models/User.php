<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'nombres',
        'apellidos',
        'documento',
        'telefono',
        'email',
        'username',
        'password',
        'status',
        'active',
        'approved_at',
        'rejected_at',
        'productor_id',
        'acopiador_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'email_verified_at' => 'datetime',
            'active' => 'boolean',
        ];
    }

    /**
     * Roles asignados al usuario.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Productor relacionado con el usuario.
     */
    public function productor()
    {
        return $this->hasOne(Productor::class, 'user_id');
    }

    /**
     * Acopiador relacionado con el usuario.
     */
    public function acopiador()
    {
        return $this->hasOne(Acopiador::class, 'user_id');
    }

    /**
     * Auditorías realizadas por el usuario.
     */
    public function auditorias()
    {
        return $this->hasMany(Auditoria::class, 'usuario_id');
    }

    /**
     * Verifica si el usuario tiene un determinado rol.
     */
    public function hasRole(string $slug): bool
    {
        return $this->roles()
            ->where('slug', $slug)
            ->exists();
    }

    /**
     * Verifica si el usuario tiene un determinado permiso.
     */
    public function hasPermission(string $permission): bool
    {
        if (($this->status ?? null) !== 'ACTIVO') {
            return false;
        }

        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permission) {
                $query->where('name', $permission)
                    ->orWhere('slug', $permission);
            })
            ->exists();
    }

    /**
     * Obtiene el rol principal del usuario.
     */
    public function primaryRole(): ?Role
    {
        return $this->roles()->first();
    }

    /**
     * Obtiene el slug del rol principal.
     */
    public function roleSlug(): ?string
    {
        return $this->roles()->value('slug');
    }

    /**
     * Obtiene el nombre del rol principal.
     */
    public function roleName(): ?string
    {
        return $this->roles()->value('name');
    }

    /**
     * Obtiene la ruta del dashboard según su rol.
     */
    public function dashboard(): string
    {
        $role = $this->roleSlug();

        if (!$role) {
            return '/registro/pendiente';
        }

        return match ($role) {
            'admin' => '/dashboard',
            'acopiador' => '/dashboard',
            'supervisor' => '/dashboard',
            'produccion' => '/dashboard',
            'despacho' => '/dashboard',
            'productor' => '/dashboard',
            default => '/dashboard',
        };
    }
}
