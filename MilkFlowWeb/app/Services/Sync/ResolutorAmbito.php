<?php

namespace App\Services\Sync;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Traduce el ámbito declarado en config/sync.php a un filtro de consulta.
 *
 * Es el punto único donde se decide qué filas puede ver cada rol en el
 * dispositivo: si aquí no pasa, nunca llega al teléfono.
 */
class ResolutorAmbito
{
    /** Devuelve la consulta ya acotada, o null si el rol no accede a la entidad. */
    public function consulta(array $config, User $usuario): ?Builder
    {
        $ambito = $config['acceso'][$usuario->role] ?? $config['acceso']['*'] ?? 'ninguno';

        if ($ambito === 'ninguno') {
            return null;
        }

        /** @var class-string<\Illuminate\Database\Eloquent\Model> $modelo */
        $modelo = $config['modelo'];
        $consulta = $modelo::query();

        if ($ambito === 'todos') {
            return $consulta;
        }

        if (str_starts_with($ambito, 'propio:')) {
            $columna = substr($ambito, strlen('propio:'));

            return $consulta->where($columna, $usuario->id);
        }

        return match ($ambito) {
            // El padrón operativo: productores, acopiadores y uno mismo.
            'padron' => $consulta->where(function ($q) use ($usuario) {
                $q->whereIn('role', ['productor', 'acopiador'])->orWhere('id', $usuario->id);
            }),

            // Filas colgadas de una ruta que conduce este acopiador.
            'mis_rutas', 'mis_registros' => $consulta->whereHas(
                'route',
                fn ($q) => $q->where('collector_id', $usuario->id)
            ),

            // Ventas cuyo cliente está vinculado a este productor.
            'mis_compras' => $consulta->whereHas(
                'customer',
                fn ($q) => $q->where('linked_user_id', $usuario->id)
            ),

            // Avisos generales, del rol o dirigidos a esta persona.
            'avisos' => $consulta->where(function ($q) use ($usuario) {
                $q->whereNull('target_role')
                    ->orWhere('target_role', $usuario->role)
                    ->orWhere('target_user_id', $usuario->id);
            }),

            default => null,
        };
    }
}
