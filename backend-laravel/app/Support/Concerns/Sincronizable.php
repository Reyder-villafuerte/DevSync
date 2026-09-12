<?php

namespace App\Support\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Contrato de sincronización offline-first (requisito técnico 2).
 *
 * Toda tabla sincronizable expone: updated_at, version y deleted.
 *  - updated_at: marca de tiempo del servidor; base del cursor de bajada.
 *  - version:    entero monótono que se incrementa en CADA escritura. Sirve
 *                como detector de conflictos independiente del reloj.
 *  - deleted:    borrado lógico. Nunca se hace DELETE físico de una fila
 *                sincronizable: el cliente necesita "ver" la baja para
 *                replicarla.
 */
trait Sincronizable
{
    use UsaUuid;

    /**
     * Formato de escritura de fechas CON offset explícito. Se sobreescribe el
     * método (y no la propiedad `$dateFormat`) porque PHP no admite que un
     * trait redeclare una propiedad que el modelo base ya define.
     *
     * Sin la `P` final, Eloquent escribe el reloj de pared del Carbon sin zona
     * ("2026-09-10 20:09:56") y PostgreSQL lo interpreta en la zona de la
     * sesión. Una hora que llegó del móvil en UTC se guardaba entonces como
     * hora local: +5 h por cada viaje, y el error se acumulaba en cada
     * re-sincronización (una jornada abierta a las 16:09 acababa marcando
     * 21:09, y al siguiente push 02:09). Con el offset explícito no hay nada
     * que adivinar, venga el dato del móvil (UTC) o del panel (America/Lima).
     */
    public function getDateFormat(): string
    {
        return 'Y-m-d H:i:sP';
    }

    public static function bootSincronizable(): void
    {
        // Incremento de versión en cada guardado. Al venir de sincronización,
        // SyncService fija la versión explícitamente y este hook la respeta
        // solo si no cambió el campo a mano.
        static::saving(function ($model) {
            if (! $model->isDirty('version')) {
                $model->version = ($model->version ?? 0) + 1;
            }
        });
    }

    public function initializeSincronizable(): void
    {
        $this->mergeCasts([
            'deleted' => 'boolean',
            'version' => 'integer',
        ]);
    }

    /** Excluye los registros con borrado lógico. */
    public function scopeVivos(Builder $q): Builder
    {
        return $q->where($this->qualifyColumn('deleted'), false);
    }

    /**
     * Cursor de bajada: cambios ocurridos después de (updated_at, id).
     * El par (updated_at, id) desempata timestamps idénticos y da un orden
     * total estable para paginar sin perder ni repetir filas.
     */
    public function scopeCambiadosDesde(Builder $q, ?string $desde, ?string $ultimoId = null): Builder
    {
        if (! $desde) {
            return $q->orderBy('updated_at')->orderBy('id');
        }

        return $q->where(function (Builder $sub) use ($desde, $ultimoId) {
            $sub->where('updated_at', '>', $desde)
                ->orWhere(function (Builder $b) use ($desde, $ultimoId) {
                    $b->where('updated_at', '=', $desde);
                    if ($ultimoId) {
                        $b->where('id', '>', $ultimoId);
                    }
                });
        })->orderBy('updated_at')->orderBy('id');
    }

    /** Borrado lógico: marca deleted y sube versión, sin tocar la fila física. */
    public function borrarLogico(): void
    {
        $this->forceFill(['deleted' => true])->save();
    }

    public function borradoLogicamente(): bool
    {
        return (bool) $this->deleted;
    }
}
