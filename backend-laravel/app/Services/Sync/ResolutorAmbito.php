<?php

namespace App\Services\Sync;

use App\Enums\RolUsuario;
use App\Exceptions\ReglaNegocioException;
use App\Models\Ruta;
use App\Models\Usuario;
use App\Models\Zona;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Traduce (rol + ámbito) a un filtro de consulta para la bajada.
 *
 * La estrategia de visibilidad de cada entidad se declara en config/sync.php;
 * aquí vive su semántica concreta. Un acopiador de la Ruta 01 ve el padrón de
 * las zonas de esa ruta y la tarifa vigente, nunca las ventas de planta.
 */
class ResolutorAmbito
{
    /** Ámbito "natural" del usuario según su rol y sus asignaciones. */
    public function ambitoAsignado(Usuario $usuario): AmbitoSincronizacion
    {
        return match ($usuario->rol) {
            RolUsuario::ACOPIADOR => ($ruta = $usuario->acopiador?->ruta)
                ? AmbitoSincronizacion::de('ruta', $ruta->codigo ?? $ruta->id)
                : AmbitoSincronizacion::global(),
            RolUsuario::PRODUCTOR => ($p = $usuario->productor)
                ? AmbitoSincronizacion::de('productor', $p->id)
                : AmbitoSincronizacion::global(),
            default => AmbitoSincronizacion::global(),
        };
    }

    /**
     * Un rol de campo solo puede pedir su propio ámbito; los roles de oficina
     * pueden pedir cualquiera.
     */
    public function validarAmbitoSolicitado(Usuario $usuario, AmbitoSincronizacion $solicitado): void
    {
        if (in_array($usuario->rol, [RolUsuario::JEFE_PRODUCCION, RolUsuario::ADMINISTRACION, RolUsuario::DESPACHO_VENTAS], true)) {
            return;
        }
        if ($solicitado->esGlobal()) {
            return; // se interpretará como el ámbito asignado
        }

        $propio = $this->ambitoAsignado($usuario);
        if ((string) $propio !== (string) $solicitado) {
            throw new ReglaNegocioException(
                "El ámbito solicitado ({$solicitado}) no coincide con el asignado a su usuario ({$propio}).",
                'AMBITO_NO_AUTORIZADO',
            );
        }
    }

    /**
     * Aplica el filtro de visibilidad. Devuelve null cuando la entidad no
     * corresponde al rol (estrategia "ninguno").
     */
    public function aplicar(Builder $q, string $entidad, string $estrategia, Usuario $usuario, AmbitoSincronizacion $ambito): ?Builder
    {
        if ($estrategia === 'ninguno') {
            return null;
        }
        if ($estrategia === 'todos') {
            return $q;
        }

        if ($ambito->esGlobal()) {
            $ambito = $this->ambitoAsignado($usuario);
        }

        return match ($estrategia) {
            'vigentes' => $this->filtroVigentes($q, $entidad),
            'ruta', 'zona' => $this->filtroTerritorial($q, $entidad, $ambito),
            'propio' => $this->filtroPropio($q, $entidad, $usuario),
            default => $q,
        };
    }

    // ------------------------------------------------------------------
    private function filtroVigentes(Builder $q, string $entidad): Builder
    {
        $hoy = now()->toDateString();

        return match ($entidad) {
            'avisos' => $q->vigentes(),
            'precios_venta', 'precios_compra_leche' => $q->where('vigente_desde', '<=', $hoy)
                ->where(fn (Builder $b) => $b->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $hoy)),
            default => $q,
        };
    }

    private function filtroTerritorial(Builder $q, string $entidad, AmbitoSincronizacion $ambito): Builder
    {
        $rutaId = $this->rutaId($ambito);
        $zonaIds = $this->zonaIds($ambito, $rutaId);

        // Territorio no resoluble => no se expone nada.
        if ($rutaId === null && $zonaIds === []) {
            return $q->whereRaw('1 = 0');
        }

        return match ($entidad) {
            'rutas' => $rutaId ? $q->where('id', $rutaId) : $q->whereRaw('1 = 0'),
            'zonas' => $this->whereInOVacio($q, 'id', $zonaIds),
            'productores' => $this->whereInOVacio($q, 'zona_id', $zonaIds),
            'rutas_acopio' => $rutaId ? $q->where('ruta_id', $rutaId) : $q->whereRaw('1 = 0'),
            'registros_acopio' => $rutaId
                ? $q->whereHas('rutaAcopio', fn (Builder $b) => $b->where('ruta_id', $rutaId))
                : $q->whereRaw('1 = 0'),
            'controles_calidad', 'sanciones' => $this->whereHasInOVacio($q, 'productor', 'zona_id', $zonaIds),
            default => $q,
        };
    }

    private function filtroPropio(Builder $q, string $entidad, Usuario $usuario): Builder
    {
        $productorId = $usuario->productor?->id;
        $esProductor = $usuario->rol === RolUsuario::PRODUCTOR;
        $esSupervisor = $usuario->rol === RolUsuario::SUPERVISOR_CALIDAD;

        return match ($entidad) {
            'productores' => $this->whereIdOVacio($q, 'id', $productorId),
            'zonas' => $this->whereIdOVacio($q, 'id', $usuario->productor?->zona_id),
            'liquidaciones', 'sanciones', 'solicitudes_cambio_zona' => $this->whereIdOVacio($q, 'productor_id', $productorId),
            'rutas_acopio' => $q->whereHas('acopiador', fn (Builder $b) => $b->where('usuario_id', $usuario->id)),
            'registros_acopio' => $esProductor
                ? $this->whereIdOVacio($q, 'productor_id', $productorId)
                : $q->whereHas('rutaAcopio.acopiador', fn (Builder $b) => $b->where('usuario_id', $usuario->id)),
            'controles_calidad' => $esSupervisor
                ? $q->where('supervisor_id', $usuario->id)
                : $this->whereIdOVacio($q, 'productor_id', $productorId),
            default => $q,
        };
    }

    // ------------------------------------------------------------------
    // Helpers: evitan pasar '-' a columnas uuid (PostgreSQL es estricto).
    // ------------------------------------------------------------------
    private function whereIdOVacio(Builder $q, string $columna, ?string $valor): Builder
    {
        return $valor ? $q->where($columna, $valor) : $q->whereRaw('1 = 0');
    }

    private function whereInOVacio(Builder $q, string $columna, array $valores): Builder
    {
        return $valores === [] ? $q->whereRaw('1 = 0') : $q->whereIn($columna, $valores);
    }

    private function whereHasInOVacio(Builder $q, string $relacion, string $columna, array $valores): Builder
    {
        return $valores === []
            ? $q->whereRaw('1 = 0')
            : $q->whereHas($relacion, fn (Builder $b) => $b->whereIn($columna, $valores));
    }

    private function rutaId(AmbitoSincronizacion $ambito): ?string
    {
        $valor = $ambito->valor;
        $esUuid = $valor !== null && Str::isUuid($valor);

        return match ($ambito->tipo) {
            'ruta' => Ruta::query()
                ->where('codigo', $valor)
                ->when($esUuid, fn ($q) => $q->orWhere('id', $valor))
                ->value('id'),
            'zona' => Zona::query()
                ->where('codigo', $valor)
                ->when($esUuid, fn ($q) => $q->orWhere('id', $valor))
                ->value('ruta_id'),
            default => null,
        };
    }

    private function zonaIds(AmbitoSincronizacion $ambito, ?string $rutaId): array
    {
        if ($ambito->tipo === 'zona') {
            $valor = $ambito->valor;
            $esUuid = $valor !== null && Str::isUuid($valor);

            return Zona::query()
                ->where('codigo', $valor)
                ->when($esUuid, fn ($q) => $q->orWhere('id', $valor))
                ->pluck('id')->all();
        }

        return $rutaId ? Zona::query()->where('ruta_id', $rutaId)->pluck('id')->all() : [];
    }
}
