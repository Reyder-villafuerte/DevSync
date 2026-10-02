<?php

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

/**
 * Huella de los datos compartidos con la app móvil.
 *
 * La web la consulta cada pocos segundos: si cambió (un acopiador registró
 * litros, la planta verificó una ruta, etc.), la pantalla se recarga sola.
 * Usa las mismas tablas que se sincronizan con el móvil (config/sync.php).
 */
class TiempoRealController extends Controller
{
    public function version(): JsonResponse
    {
        $partes = collect(config('sync.entidades', []))
            ->map(function (array $entidad, string $nombre): string {
                /** @var Model $modelo */
                $modelo = new $entidad['modelo'];
                $consulta = $modelo->newQuery();

                return $nombre.':'.$consulta->count().':'.$consulta->max('updated_at');
            })
            ->implode('|');

        return response()->json(['version' => md5($partes)]);
    }
}
