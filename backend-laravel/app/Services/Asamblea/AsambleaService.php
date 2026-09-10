<?php

namespace App\Services\Asamblea;

use App\Exceptions\ReglaNegocioException;
use App\Models\Asamblea;
use App\Models\AsistenciaAsamblea;
use App\Models\Productor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Use Case: registro de asistencia a asamblea por DNI y cálculo de quórum.
 *
 * El tamaño del padrón y el quórum requerido se CONGELAN al abrir el registro,
 * para que altas/bajas posteriores no invaliden el acta.
 */
class AsambleaService
{
    /** Congela el padrón y fija el quórum requerido. */
    public function abrirRegistro(Asamblea $asamblea): Asamblea
    {
        if ($asamblea->estado !== 'convocada') {
            throw new ReglaNegocioException("La asamblea no está en estado 'convocada'.", 'ASAMBLEA_ESTADO_INVALIDO');
        }

        $padron = Productor::query()->delPadron()->where('deleted', false)->count();
        $fraccion = (float) $asamblea->fraccion_quorum;

        // "Mitad más uno" cuando la fracción es 0.5; en otro caso, techo de la
        // fracción del padrón.
        $quorum = $fraccion === 0.5
            ? intdiv($padron, 2) + 1
            : (int) ceil($padron * $fraccion);

        $asamblea->forceFill([
            'padron_snapshot' => $padron,
            'quorum_requerido' => max($quorum, 1),
            'estado' => 'en_curso',
        ])->save();

        return $asamblea;
    }

    public function registrarAsistencia(Asamblea $asamblea, string $dni, ?string $nombre = null, ?string $registradoPor = null): AsistenciaAsamblea
    {
        if ($asamblea->estado !== 'en_curso') {
            throw new ReglaNegocioException('El registro de asistencia no está abierto.', 'ASAMBLEA_REGISTRO_CERRADO');
        }
        if (! preg_match('/^[0-9]{8}$/', $dni)) {
            throw new ReglaNegocioException('DNI inválido.', 'DNI_INVALIDO');
        }

        return DB::transaction(function () use ($asamblea, $dni, $nombre, $registradoPor) {
            $productor = Productor::query()->where('dni', $dni)->first();

            // La unique (asamblea_id, dni) impide doble marca aunque haya carrera.
            $asistencia = AsistenciaAsamblea::firstOrCreate(
                ['asamblea_id' => $asamblea->id, 'dni' => $dni],
                [
                    'productor_id' => $productor?->id,
                    'nombre_completo' => $nombre ?? ($productor ? "{$productor->nombres} {$productor->apellidos}" : null),
                    'registrado_en' => now(),
                    'registrado_por' => $registradoPor,
                ],
            );

            $asistentes = $asamblea->asistencias()->where('deleted', false)->count();
            $asamblea->forceFill([
                'asistentes' => $asistentes,
                'quorum_alcanzado' => $asamblea->quorum_requerido !== null && $asistentes >= $asamblea->quorum_requerido,
            ])->save();

            return $asistencia;
        });
    }

    /**
     * Buscador del padrón por DNI (prefijo) o por nombre, IGNORANDO acentos y
     * mayúsculas. Se usa `translate()` de PostgreSQL para no depender de la
     * extensión `unaccent`.
     *
     * @return Collection<int,Productor>
     */
    public function buscarPadron(string $termino, int $limite = 15): Collection
    {
        $termino = trim($termino);
        if ($termino === '') {
            return collect();
        }

        $sinAcentos = strtr(mb_strtolower($termino), [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);

        return Productor::query()
            ->delPadron()
            ->where('deleted', false)
            ->where(function ($q) use ($termino, $sinAcentos) {
                $q->where('dni', 'like', $termino.'%')
                    ->orWhereRaw(
                        "translate(lower(nombres || ' ' || apellidos), 'áéíóúüñ', 'aeiouun') like ?",
                        ['%'.$sinAcentos.'%'],
                    );
            })
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->limit($limite)
            ->get();
    }
}
