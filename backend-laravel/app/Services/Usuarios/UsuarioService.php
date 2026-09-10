<?php

namespace App\Services\Usuarios;

use App\Enums\RolUsuario;
use App\Exceptions\ReglaNegocioException;
use App\Models\Acopiador;
use App\Models\Productor;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Use Case: alta y estado de usuarios del ecosistema.
 *
 * El "ámbito asignado" no es una columna de `usuarios`: se materializa según el
 * rol —
 *  - acopiador  → fila en `acopiadores` con la ruta,
 *  - productor  → fila en `productores` con la zona,
 *  - roles de planta (jefe_produccion, despacho_ventas, administracion,
 *    supervisor_calidad) → sin registro de ámbito (operan sobre toda la planta).
 *
 * Transaccional: usuario + ámbito son un mismo hecho.
 */
class UsuarioService
{
    /**
     * @param  array{nombres:string,apellidos:string,dni:string,email?:?string,telefono?:?string,password?:?string,rol:string,ruta_id?:?string,zona_id?:?string}  $datos
     */
    public function crear(array $datos): Usuario
    {
        $rol = $datos['rol'] instanceof RolUsuario ? $datos['rol'] : RolUsuario::from($datos['rol']);

        return DB::transaction(function () use ($datos, $rol) {
            $usuario = Usuario::create([
                'nombres' => $datos['nombres'],
                'apellidos' => $datos['apellidos'],
                'dni' => $datos['dni'],
                'email' => $datos['email'] ?? null,
                'telefono' => $datos['telefono'] ?? null,
                'password' => $datos['password'] ?: Str::password(12),
                'rol' => $rol->value,
                'activo' => true,
            ]);

            if ($rol === RolUsuario::ACOPIADOR && ! empty($datos['ruta_id'])) {
                Acopiador::updateOrCreate(
                    ['usuario_id' => $usuario->id, 'vigente_hasta' => null],
                    ['ruta_id' => $datos['ruta_id'], 'vigente_desde' => now()->toDateString()],
                );
            }

            if ($rol === RolUsuario::PRODUCTOR && ! empty($datos['zona_id'])) {
                Productor::updateOrCreate(
                    ['dni' => $datos['dni']],
                    [
                        'usuario_id' => $usuario->id,
                        'codigo_padron' => 'P-'.$datos['dni'],
                        'nombres' => $datos['nombres'],
                        'apellidos' => $datos['apellidos'],
                        'zona_id' => $datos['zona_id'],
                        'telefono' => $datos['telefono'] ?? null,
                        'estado' => 'activo',
                        'fecha_ingreso' => now()->toDateString(),
                    ],
                );
            }

            return $usuario;
        });
    }

    public function suspender(Usuario $usuario): void
    {
        if (! $usuario->activo) {
            throw new ReglaNegocioException('El usuario ya está suspendido.', 'USUARIO_YA_SUSPENDIDO');
        }
        $usuario->forceFill(['activo' => false])->save();
    }

    public function reactivar(Usuario $usuario): void
    {
        if ($usuario->activo) {
            throw new ReglaNegocioException('El usuario ya está activo.', 'USUARIO_YA_ACTIVO');
        }
        $usuario->forceFill(['activo' => true])->save();
    }
}
