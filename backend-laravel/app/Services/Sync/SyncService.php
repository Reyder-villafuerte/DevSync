<?php

namespace App\Services\Sync;

use App\Enums\RolUsuario;
use App\Exceptions\ReglaNegocioException;
use App\Models\ControlCalidad;
use App\Models\Dispositivo;
use App\Models\SyncLog;
use App\Models\Usuario;
use App\Services\Calidad\EvaluacionCalidadService;
use App\Services\Stock\StockService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Núcleo del protocolo offline-first. Un solo par de operaciones para todas
 * las entidades:
 *
 *   pull  — GET  /api/sync/pull?desde=&ambito=
 *   push  — POST /api/sync/push  { operaciones: [...] }
 *
 * Contrato de datos: BD en snake_case, JSON en camelCase (se traduce aquí).
 */
class SyncService
{
    public function __construct(
        private readonly ResolutorAmbito $resolutor,
        private readonly EvaluacionCalidadService $evaluacionCalidad,
        private readonly StockService $stock,
    ) {}

    // =====================================================================
    // BAJADA
    // =====================================================================
    public function pull(Usuario $usuario, ?string $desde, ?string $ambitoCadena, ?Dispositivo $dispositivo = null): array
    {
        $ambito = AmbitoSincronizacion::desdeCadena($ambitoCadena);
        $this->resolutor->validarAmbitoSolicitado($usuario, $ambito);

        // Colchón de reloj: se retrocede el cursor para no perder escrituras
        // con timestamp casi simultáneo (el cliente reaplica de forma idempotente).
        $margen = (int) config('sync.margen_reloj_segundos');
        // El cursor viaja en UTC (ISO-8601 con 'Z'). Al comparar hay que pasar
        // el string CON zona horaria: si se pasa un Carbon, el grammar lo
        // formatea como 'Y-m-d H:i:s' sin zona y PostgreSQL lo interpreta en la
        // zona de sesión (America/Lima) -> se pierden ~5 h de cambios recientes.
        $cursor = $desde ? Carbon::parse($desde)->subSeconds($margen)->toIso8601String() : null;

        // Límite POR ENTIDAD (no un presupuesto global compartido): así una
        // entidad con mucho movimiento no "mata de hambre" a las siguientes y
        // el cursor único de timestamp sigue siendo seguro.
        $limitePorEntidad = (int) config('sync.lote_pull');
        $rol = $usuario->rol->value;

        $cambios = [];
        $topesEntidadesCapadas = [];

        foreach (config('sync.orden_pull') as $entidad) {
            $def = config("sync.entidades.{$entidad}");
            $estrategia = $def['visibilidad'][$rol] ?? 'ninguno';

            /** @var Model $modelo */
            $modelo = new $def['modelo'];
            $q = $this->resolutor->aplicar($modelo->newQuery(), $entidad, $estrategia, $usuario, $ambito);
            if ($q === null) {
                continue; // el rol no ve esta entidad
            }

            // Incluye deleted = true (no se aplica el scope `vivos`): el cliente
            // necesita ver la baja para replicarla.
            if ($cursor) {
                $q->where('updated_at', '>', $cursor);
            }
            $q->orderBy('updated_at')->orderBy('id');

            $filas = $q->limit($limitePorEntidad + 1)->get();
            $capada = $filas->count() > $limitePorEntidad;
            $filas = $filas->take($limitePorEntidad);

            if ($filas->isNotEmpty()) {
                $cambios[Str::camel($entidad)] = $filas
                    ->map(fn (Model $m) => $this->aCamel($m->attributesToArray()))
                    ->all();
            }
            if ($capada && $filas->isNotEmpty()) {
                $topesEntidadesCapadas[] = Carbon::parse($filas->last()->updated_at);
            }
        }

        $servidorEn = now();
        $hayMas = $topesEntidadesCapadas !== [];

        // Cursor siguiente: si alguna entidad quedó capada, el mínimo de los
        // últimos updated_at entregados garantiza que todo lo estrictamente
        // anterior ya viajó en TODAS las entidades. Si nada quedó capado, el
        // reloj del servidor.
        $siguiente = $hayMas
            ? collect($topesEntidadesCapadas)->min()
            : $servidorEn;

        $this->registrarLog('bajada', 'lote', $dispositivo, $usuario,
            recibidos: 0,
            aceptados: array_sum(array_map('count', $cambios)),
            conflictos: 0,
            cursorDesde: $desde,
            cursorHasta: $siguiente->toISOString(),
        );

        return [
            'servidorEn' => $servidorEn->toISOString(),
            'cursor' => $siguiente->toISOString(),
            'hayMas' => $hayMas,
            'ambito' => (string) ($ambito->esGlobal() ? $this->resolutor->ambitoAsignado($usuario) : $ambito),
            'cambios' => $cambios,
        ];
    }

    // =====================================================================
    // SUBIDA
    // =====================================================================
    /**
     * @param  list<array{entidad:string,id:string,versionBase?:int,atributos?:array,eliminar?:bool}>  $operaciones
     */
    public function push(Usuario $usuario, array $operaciones, Dispositivo $dispositivo): ResultadoPush
    {
        $resultado = new ResultadoPush;

        // Agrupar por entidad conservando el orden de llegada dentro del grupo.
        $grupos = [];
        foreach ($operaciones as $op) {
            $entidad = $op['entidad'] ?? null;
            if (! $entidad || ! config()->has("sync.entidades.{$entidad}")) {
                $resultado->rechazar($op['id'] ?? null, $entidad, 'entidad_desconocida');

                continue;
            }
            $grupos[$entidad][] = $op;
        }

        // Procesar los grupos en el orden de dependencias (cabeceras antes que
        // detalles) para que las claves foráneas ya existan; los que no estén
        // en la lista de orden van al final.
        $orden = config('sync.orden_pull');
        $entidadesOrdenadas = array_merge(
            array_values(array_intersect($orden, array_keys($grupos))),
            array_values(array_diff(array_keys($grupos), $orden)),
        );
        foreach ($entidadesOrdenadas as $entidad) {
            $this->procesarGrupo($entidad, $grupos[$entidad], $usuario, $dispositivo, $resultado);
        }

        $dispositivo->forceFill(['ultima_sincronizacion_en' => now()])->save();

        // El stock que llegó como movimientos "conmutativos" se insertó saltando
        // StockService: se refresca la vista materializada una sola vez.
        if (isset($grupos['movimientos_stock'])) {
            $this->stock->refrescarVistaStock();
        }

        $this->registrarLog('subida', 'lote', $dispositivo, $usuario,
            recibidos: count($operaciones),
            aceptados: count($resultado->aceptadas),
            conflictos: count($resultado->conflictos),
        );

        return $resultado;
    }

    private function procesarGrupo(string $entidad, array $ops, Usuario $usuario, Dispositivo $dispositivo, ResultadoPush $res): void
    {
        $def = config("sync.entidades.{$entidad}");

        if ($def['direccion'] === 'bajada') {
            foreach ($ops as $op) {
                $res->rechazar($op['id'] ?? null, $entidad, 'entidad_solo_lectura');
            }

            return;
        }

        $estrategia = $def['visibilidad'][$usuario->rol->value] ?? 'ninguno';
        if ($estrategia === 'ninguno') {
            foreach ($ops as $op) {
                $res->rechazar($op['id'] ?? null, $entidad, 'rol_sin_acceso');
            }

            return;
        }

        // UNA transacción por tabla afectada. Cada operación va además en un
        // SAVEPOINT: un error inesperado en una fila la marca como rechazada
        // sin abortar el resto del lote de esa tabla.
        DB::transaction(function () use ($entidad, $def, $ops, $usuario, $dispositivo, $res) {
            foreach ($ops as $op) {
                $id = $op['id'] ?? null;
                if (! $id || ! Str::isUuid($id)) {
                    $res->rechazar($id, $entidad, 'uuid_invalido');

                    continue;
                }

                try {
                    DB::transaction(fn () => $this->aplicarOperacion($entidad, $def, $op, $usuario, $dispositivo, $res));
                } catch (\Throwable $e) {
                    $res->rechazar($id, $entidad, $this->motivoDeExcepcion($e));
                }
            }
        });
    }

    private function aplicarOperacion(string $entidad, array $def, array $op, Usuario $usuario, Dispositivo $dispositivo, ResultadoPush $res): void
    {
        /** @var class-string<Model> $clase */
        $clase = $def['modelo'];
        $id = $op['id'];
        $eliminar = (bool) ($op['eliminar'] ?? false);
        $versionBase = (int) ($op['versionBase'] ?? $op['version_base'] ?? 0);

        /** @var Model|null $existente */
        $existente = $clase::query()->whereKey($id)->lockForUpdate()->first();

        $atributos = $this->prepararAtributos(new $clase, $op['atributos'] ?? [], $usuario, $entidad);

        // El borrado lógico solo tiene sentido para entidades editables. En las
        // de solo-inserción / conmutativas se ignora (no-op idempotente).
        if ($eliminar && ! in_array($def['conflicto'], ['version'], true)) {
            $res->aceptar($id, $entidad, (int) ($existente->version ?? 0), 'ignorado');

            return;
        }

        switch ($def['conflicto']) {

            // Recolecciones e inspecciones: SOLO inserción, nunca update.
            case 'solo_insercion':
                if ($existente) {
                    if ($this->contenidoCoincide($existente, $atributos, $eliminar)) {
                        $res->aceptar($id, $entidad, (int) $existente->version, 'idempotente');
                    } else {
                        $res->conflicto($id, $entidad, 'solo_insercion_no_actualizable', $this->aCamel($existente->attributesToArray()));
                    }

                    return;
                }
                $this->insertar($clase, $id, $atributos, $entidad, $res);

                return;

            // Movimientos de stock: conmutativos. Cada UUID se inserta una vez;
            // reenviarlo es no-op. El orden entre movimientos no altera el saldo.
            case 'conmutativo':
                if ($existente) {
                    $res->aceptar($id, $entidad, (int) $existente->version, 'idempotente');

                    return;
                }
                $this->insertar($clase, $id, $atributos, $entidad, $res);

                return;

            // Precios, avisos, liquidaciones: gana el servidor SIEMPRE.
            case 'servidor_gana':
                if ($existente) {
                    $res->conflicto($id, $entidad, 'servidor_autoritativo', $this->aCamel($existente->attributesToArray()));
                } else {
                    $res->rechazar($id, $entidad, 'entidad_administrada_por_servidor');
                }

                return;

            // Concurrencia optimista por `version`.
            case 'version':
            default:
                if (! $existente) {
                    $this->insertar($clase, $id, $atributos, $entidad, $res);

                    return;
                }
                $versionServidor = (int) $existente->version;
                if ($versionBase < $versionServidor) {
                    if ($this->contenidoCoincide($existente, $atributos, $eliminar)) {
                        $res->aceptar($id, $entidad, $versionServidor, 'idempotente');
                    } else {
                        $res->conflicto($id, $entidad, 'version_desactualizada', $this->aCamel($existente->attributesToArray()));
                    }

                    return;
                }
                $existente->forceFill($eliminar ? ['deleted' => true] : $atributos)->save();
                $this->posproceso($entidad, $existente);
                $res->aceptar($id, $entidad, (int) $existente->version, $eliminar ? 'eliminado' : 'actualizado');

                return;
        }
    }

    private function insertar(string $clase, string $id, array $atributos, string $entidad, ResultadoPush $res): void
    {
        // Choques de unicidad ANTES de tocar la BD: si se dejan estallar salen
        // como 'violacion_integridad', un motivo que el acopiador no puede
        // interpretar ni resolver desde el móvil.
        $duplicado = $this->filaQueOcupaLaClaveUnica($clase, $id, $entidad, $atributos);
        if ($duplicado) {
            $res->conflicto($id, $entidad, 'jornada_del_dia_ya_existe', $this->aCamel($duplicado->attributesToArray()));

            return;
        }

        /** @var Model $modelo */
        $modelo = new $clase;
        $modelo->forceFill(array_merge($atributos, [$modelo->getKeyName() => $id]))->save();
        $this->posproceso($entidad, $modelo);
        $res->aceptar($id, $entidad, (int) $modelo->version, 'insertado');
    }

    /**
     * Fila distinta que ya ocupa la clave única de negocio de la entidad.
     *
     * Solo aplica si `sync.jornada_unica_por_dia` está activo (en la fase de
     * pruebas no lo está y se admiten varias jornadas por día).
     *
     * Hoy solo `rutas_acopio`, con UNIQUE(acopiador_id, fecha): un móvil
     * que perdió su caché local abre una jornada nueva para un día que el
     * servidor ya tiene y su id nunca podrá insertarse. Devolverla como
     * conflicto le entrega al cliente la jornada buena del servidor.
     */
    private function filaQueOcupaLaClaveUnica(string $clase, string $id, string $entidad, array $atributos): ?Model
    {
        if ($entidad !== 'rutas_acopio' || ! config('sync.jornada_unica_por_dia')) {
            return null;
        }
        if (! isset($atributos['acopiador_id'], $atributos['fecha'])) {
            return null;
        }

        return $clase::query()
            ->where('acopiador_id', $atributos['acopiador_id'])
            ->whereDate('fecha', $atributos['fecha'])
            ->whereKeyNot($id)
            ->first();
    }

    /** Reglas de dominio disparadas tras aceptar ciertos registros. */
    private function posproceso(string $entidad, Model $modelo): void
    {
        if ($modelo instanceof ControlCalidad && ! $modelo->deleted) {
            // El servidor es la autoridad del dictamen: se (re)evalúa aunque el
            // móvil lo haya traído calculado.
            $this->evaluacionCalidad->evaluar($modelo);
        }
    }

    // ---------------------------------------------------------------------
    // Helpers de forma de datos
    // ---------------------------------------------------------------------

    /** camelCase (JSON) -> snake_case (BD), recorta a $fillable y quita claves de servidor. */
    private function prepararAtributos(Model $modelo, array $atributos, Usuario $usuario, string $entidad): array
    {
        $snake = [];
        foreach ($atributos as $clave => $valor) {
            $snake[Str::snake($clave)] = $valor;
        }
        unset($snake['version'], $snake['created_at'], $snake['updated_at'], $snake[$modelo->getKeyName()]);

        $limpio = array_intersect_key($snake, array_flip($modelo->getFillable()));

        // Trazabilidad de origen: el dispositivo/usuario que sube manda quién.
        if ($entidad === 'controles_calidad' && ! isset($limpio['supervisor_id'])) {
            $limpio['supervisor_id'] = $usuario->id;
        }
        if ($entidad === 'solicitudes_cambio_zona' && ! isset($limpio['productor_id'])) {
            $limpio['productor_id'] = $usuario->productor?->id;
        }
        // El móvil del acopiador no conoce su fila en `acopiadores` (solo su
        // usuario y su ruta): el servidor fija el acopiador_id desde el token.
        if ($entidad === 'rutas_acopio' && $usuario->rol === RolUsuario::ACOPIADOR && $usuario->acopiador) {
            $limpio['acopiador_id'] = $usuario->acopiador->id;
        }

        return $limpio;
    }

    /** snake_case (BD) -> camelCase (JSON). Solo el primer nivel de claves. */
    private function aCamel(array $fila): array
    {
        $salida = [];
        foreach ($fila as $clave => $valor) {
            $salida[Str::camel($clave)] = $valor;
        }

        return $salida;
    }

    /**
     * ¿El registro del servidor ya refleja lo que trae la operación? Compara
     * los valores YA CASTEADOS por Eloquent en ambos lados, para que "12.5" vs
     * "12.50" o una fecha ISO vs un timestamp no cuenten como diferencia.
     */
    private function contenidoCoincide(Model $existente, array $atributos, bool $eliminar): bool
    {
        if ($eliminar) {
            return (bool) $existente->deleted === true;
        }

        $temp = $existente->newInstance()->forceFill($atributos);

        foreach (array_keys($atributos) as $clave) {
            $a = $existente->getAttribute($clave);
            $b = $temp->getAttribute($clave);

            if ($a instanceof \DateTimeInterface || $b instanceof \DateTimeInterface) {
                if (! $a || ! $b || ! Carbon::parse($a)->equalTo(Carbon::parse($b))) {
                    return false;
                }
            } elseif ((string) $a !== (string) $b) {
                return false;
            }
        }

        return true;
    }

    private function motivoDeExcepcion(\Throwable $e): string
    {
        if ($e instanceof ReglaNegocioException) {
            return $e->regla;
        }
        if ($e instanceof \Illuminate\Database\QueryException) {
            return 'violacion_integridad'; // FK/único/CHECK
        }

        return 'error_inesperado';
    }

    private function registrarLog(string $direccion, string $entidad, ?Dispositivo $dispositivo, ?Usuario $usuario, int $recibidos, int $aceptados, int $conflictos, ?string $cursorDesde = null, ?string $cursorHasta = null): void
    {
        SyncLog::create([
            'dispositivo_id' => $dispositivo?->id,
            'usuario_id' => $usuario?->id ?? $dispositivo?->usuario_id,
            'direccion' => $direccion,
            'entidad' => $entidad,
            'recibidos' => $recibidos,
            'aceptados' => $aceptados,
            'conflictos' => $conflictos,
            'cursor_desde' => $cursorDesde,
            'cursor_hasta' => $cursorHasta,
            'ejecutado_en' => now(),
        ]);
    }
}
