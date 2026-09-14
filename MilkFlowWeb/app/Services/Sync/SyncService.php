<?php

namespace App\Services\Sync;

use App\Exceptions\ReglaNegocioException;
use App\Models\CollectionRoute;
use App\Models\LactoscanAnalysis;
use App\Models\SyncOperation;
use App\Models\TechnicalVisit;
use App\Models\User;
use App\Models\ZoneChangeRequest;
use App\Services\Acopio\AcopioService;
use App\Services\Calidad\CalidadService;
use App\Services\Pagos\LiquidacionService;
use App\Services\Planta\PlantaService;
use App\Services\Sistema\SistemaService;
use App\Services\Ventas\VentaService;
use App\Services\Zonas\ZonaService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Motor de sincronización con la app móvil.
 *
 * Bajada (pull): deltas por entidad usando un cursor `updated_at` por entidad,
 * acotados al ámbito del rol.
 *
 * Subida (push): cola de operaciones con `client_uuid`. La bitácora
 * `sync_operations` garantiza que reenviar una operación ya aplicada devuelva
 * la respuesta original en vez de duplicar el efecto.
 */
class SyncService
{
    public function __construct(
        private ResolutorAmbito $resolutor,
        private AcopioService $acopio,
        private PlantaService $planta,
        private VentaService $ventas,
        private CalidadService $calidad,
        private ZonaService $zonas,
        private LiquidacionService $liquidaciones,
        private SistemaService $sistema,
    ) {
    }

    // ---------------------------------------------------------------- BAJADA

    /**
     * @param  array<string,string>  $cursores  cursor ISO-8601 por entidad
     * @param  array<int,string>  $entidadesPedidas  vacío = todas las permitidas
     */
    public function pull(User $usuario, array $cursores = [], array $entidadesPedidas = []): array
    {
        $catalogo = config('sync.entidades');
        $orden = config('sync.orden_pull');
        $limite = (int) config('sync.limite_por_entidad');
        $margen = (int) config('sync.margen_cursor_segundos');

        $resultado = [];

        foreach ($orden as $entidad) {
            if ($entidadesPedidas && !in_array($entidad, $entidadesPedidas, true)) {
                continue;
            }

            $config = $catalogo[$entidad] ?? null;
            if (!$config) {
                continue;
            }

            $consulta = $this->resolutor->consulta($config, $usuario);
            if (!$consulta) {
                continue;
            }

            $cursor = $cursores[$entidad] ?? null;

            if ($cursor) {
                // El cursor se pasa como objeto de fecha, no como texto ISO: así
                // el motor de base de datos lo compara como fecha y no carácter a
                // carácter (en SQLite 'T' > ' ' y se perderían todos los cambios).
                // Se lleva a la zona horaria de la aplicación, que es la misma con
                // la que se escribió updated_at.
                $desde = Carbon::parse($cursor)
                    ->setTimezone(config('app.timezone'))
                    ->subSeconds($margen);

                $consulta->where('updated_at', '>', $desde);
            }

            $filas = $consulta
                ->orderBy('updated_at')
                ->orderBy('id')
                ->limit($limite)
                ->get($config['columnas']);

            $ultima = $filas->last();

            $resultado[$entidad] = [
                'filas' => $filas->map(fn ($fila) => $fila->only($config['columnas']))->all(),
                'cursor' => $ultima ? $this->iso($ultima->updated_at) : $cursor,
                'hay_mas' => $filas->count() >= $limite,
            ];
        }

        return [
            'servidor_en' => now()->toIso8601String(),
            'entidades' => $resultado,
        ];
    }

    private function iso($valor): ?string
    {
        if (!$valor) {
            return null;
        }

        return $valor instanceof Carbon ? $valor->toIso8601String() : Carbon::parse($valor)->toIso8601String();
    }

    // ---------------------------------------------------------------- SUBIDA

    /**
     * @param  array<int,array>  $operaciones  cada una: {client_uuid, comando, payload}
     */
    public function push(User $usuario, ?string $deviceId, array $operaciones): array
    {
        $resultados = [];

        foreach ($operaciones as $operacion) {
            $resultados[] = $this->procesarOperacion($usuario, $deviceId, $operacion);
        }

        return $resultados;
    }

    private function procesarOperacion(User $usuario, ?string $deviceId, array $operacion): array
    {
        $clientUuid = $operacion['client_uuid'] ?? null;
        $comando = $operacion['comando'] ?? '';
        $payload = $operacion['payload'] ?? [];

        if (!$clientUuid) {
            return $this->rechazo('sin-uuid', $comando, 'La operación no trae client_uuid.');
        }

        // Idempotencia: la misma operación reenviada devuelve su respuesta original.
        $previa = SyncOperation::where('client_uuid', $clientUuid)->first();
        if ($previa) {
            return [
                'client_uuid' => $clientUuid,
                'comando' => $previa->command,
                'estado' => $previa->status,
                'mensaje' => $previa->error_message ?: 'Operación ya aplicada anteriormente.',
                'datos' => $previa->result,
                'repetida' => true,
            ];
        }

        $rolesPermitidos = config("sync.comandos.{$comando}");

        if (!$rolesPermitidos) {
            return $this->rechazo($clientUuid, $comando, "Comando desconocido: {$comando}.");
        }

        if (!in_array($usuario->role, $rolesPermitidos, true)) {
            return $this->rechazo($clientUuid, $comando, "El rol {$usuario->role} no puede ejecutar {$comando}.");
        }

        try {
            $datos = DB::transaction(fn () => $this->ejecutar($comando, $usuario, $payload, $clientUuid));

            SyncOperation::create([
                'client_uuid' => $clientUuid,
                'user_id' => $usuario->id,
                'device_id' => $deviceId,
                'command' => $comando,
                'payload' => $payload,
                'result' => $datos,
                'status' => 'aplicada',
                'applied_at' => now(),
            ]);

            return [
                'client_uuid' => $clientUuid,
                'comando' => $comando,
                'estado' => 'aplicada',
                'mensaje' => null,
                'datos' => $datos,
                'repetida' => false,
            ];
        } catch (ReglaNegocioException $e) {
            // Rechazo definitivo: el dispositivo NO debe reintentar.
            return $this->rechazo($clientUuid, $comando, $e->getMessage(), $usuario, $deviceId, $payload);
        } catch (Throwable $e) {
            // Error técnico: se devuelve como reintentable y no se registra en la bitácora.
            Log::error('Fallo al aplicar operación de sincronización', [
                'client_uuid' => $clientUuid,
                'comando' => $comando,
                'error' => $e->getMessage(),
            ]);

            return [
                'client_uuid' => $clientUuid,
                'comando' => $comando,
                'estado' => 'error',
                'mensaje' => 'Error del servidor al aplicar la operación. Se reintentará.',
                'datos' => null,
                'repetida' => false,
            ];
        }
    }

    private function rechazo(
        string $clientUuid,
        string $comando,
        string $mensaje,
        ?User $usuario = null,
        ?string $deviceId = null,
        array $payload = []
    ): array {
        if ($usuario) {
            SyncOperation::create([
                'client_uuid' => $clientUuid,
                'user_id' => $usuario->id,
                'device_id' => $deviceId,
                'command' => $comando,
                'payload' => $payload,
                'result' => null,
                'status' => 'rechazada',
                'error_message' => $mensaje,
                'applied_at' => now(),
            ]);
        }

        return [
            'client_uuid' => $clientUuid,
            'comando' => $comando,
            'estado' => 'rechazada',
            'mensaje' => $mensaje,
            'datos' => null,
            'repetida' => false,
        ];
    }

    // -------------------------------------------------------------- COMANDOS

    private function ejecutar(string $comando, User $usuario, array $payload, string $clientUuid): array
    {
        return match ($comando) {
            'abrir_ruta' => $this->abrirRuta($usuario, $payload, $clientUuid),
            'registrar_entrega' => $this->registrarEntrega($usuario, $payload, $clientUuid),
            'cerrar_ruta' => $this->cerrarRuta($usuario, $payload),
            'asignar_ruta' => $this->asignarRuta($payload),
            'verificar_recepcion' => $this->verificarRecepcion($usuario, $payload, $clientUuid),
            'producir_queso' => $this->producirQueso($usuario, $payload, $clientUuid),
            'registrar_venta' => $this->registrarVenta($usuario, $payload, $clientUuid),
            'cerrar_caja' => $this->cerrarCaja($usuario, $payload, $clientUuid),
            'registrar_analisis' => $this->registrarAnalisis($usuario, $payload, $clientUuid),
            'agendar_visita' => $this->agendarVisita($usuario, $payload, $clientUuid),
            'completar_visita' => $this->completarVisita($payload),
            'solicitar_cambio_zona' => $this->solicitarCambioZona($usuario, $payload, $clientUuid),
            'revisar_solicitud_zona' => $this->revisarSolicitudZona($usuario, $payload),
            'autorizar_pago' => $this->autorizarPago($usuario, $payload),
            'entregar_sobre' => $this->entregarSobre($usuario, $payload),
            'registrar_egreso' => $this->registrarEgreso($usuario, $payload, $clientUuid),
            'actualizar_tarifas' => $this->actualizarTarifas($usuario, $payload, $clientUuid),
            'publicar_aviso' => $this->publicarAviso($usuario, $payload, $clientUuid),
            default => throw new ReglaNegocioException("Comando no implementado: {$comando}."),
        };
    }

    /** Valida el payload y lanza una regla de negocio si no cumple. */
    private function validar(array $payload, array $reglas): array
    {
        $validador = Validator::make($payload, $reglas);

        if ($validador->fails()) {
            throw new ReglaNegocioException($validador->errors()->first());
        }

        return $validador->validated();
    }

    /**
     * Resuelve la ruta a la que apunta una operación.
     *
     * El móvil pudo haber creado la ruta estando sin señal, así que se acepta
     * el client_uuid de la ruta y, si aún no existe en el servidor, se crea.
     */
    private function resolverRuta(User $usuario, array $payload): CollectionRoute
    {
        if (!empty($payload['ruta_id'])) {
            $ruta = CollectionRoute::find($payload['ruta_id']);
            if ($ruta) {
                return $ruta;
            }
        }

        $uuidRuta = $payload['ruta_client_uuid'] ?? null;

        if ($uuidRuta) {
            $ruta = CollectionRoute::where('client_uuid', $uuidRuta)->first();
            if ($ruta) {
                return $ruta;
            }
        }

        if ($usuario->role === 'acopiador') {
            $ruta = $this->acopio->rutaDelDia($usuario, $payload['fecha'] ?? null, $uuidRuta);
            if ($ruta) {
                return $ruta;
            }

            throw new ReglaNegocioException(
                'No hay zona libre para asignarte en esa fecha: las cuatro zonas de Huata ya tienen acopiador.'
            );
        }

        throw new ReglaNegocioException('La ruta indicada no existe en el servidor.');
    }

    private function abrirRuta(User $usuario, array $payload, string $clientUuid): array
    {
        $datos = $this->validar($payload, [
            'fecha' => ['nullable', 'date'],
        ]);

        $ruta = $this->acopio->rutaDelDia($usuario, $datos['fecha'] ?? null, $clientUuid);

        if (!$ruta) {
            throw new ReglaNegocioException(
                'Las cuatro zonas de Huata ya tienen acopiador asignado para esa fecha (turno de descanso).'
            );
        }

        return ['entidad' => 'collection_routes', 'id' => $ruta->id, 'client_uuid' => $ruta->client_uuid];
    }

    private function registrarEntrega(User $usuario, array $payload, string $clientUuid): array
    {
        $datos = $this->validar($payload, [
            'producer_id' => ['required', 'integer', 'exists:users,id'],
            'liters' => ['required', 'numeric', 'min:0.1'],
            'notes' => ['nullable', 'string', 'max:255'],
            'collected_at' => ['nullable', 'string', 'max:8'],
            'fecha' => ['nullable', 'date'],
            'ruta_id' => ['nullable', 'integer'],
            'ruta_client_uuid' => ['nullable', 'uuid'],
        ]);

        $ruta = $this->resolverRuta($usuario, $payload);

        $registro = $this->acopio->registrarEntrega(
            $ruta,
            (int) $datos['producer_id'],
            (float) $datos['liters'],
            $datos['notes'] ?? null,
            $datos['collected_at'] ?? null,
            $clientUuid
        );

        return [
            'entidad' => 'collection_records',
            'id' => $registro->id,
            'client_uuid' => $clientUuid,
            'ruta_id' => $ruta->id,
            'ruta_client_uuid' => $ruta->client_uuid,
            'total_ruta' => (float) $ruta->fresh()->total_collected_liters,
        ];
    }

    private function cerrarRuta(User $usuario, array $payload): array
    {
        $ruta = $this->resolverRuta($usuario, $payload);
        $ruta = $this->acopio->cerrarRuta($ruta);

        return [
            'entidad' => 'collection_routes',
            'id' => $ruta->id,
            'client_uuid' => $ruta->client_uuid,
            'total_ruta' => (float) $ruta->total_collected_liters,
        ];
    }

    private function asignarRuta(array $payload): array
    {
        $datos = $this->validar($payload, [
            'date' => ['required', 'date'],
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'collector_id' => ['required', 'integer', 'exists:users,id'],
            'start_time' => ['nullable', 'string'],
        ]);

        $ruta = $this->acopio->asignarRuta(
            $datos['date'],
            (int) $datos['zone_id'],
            (int) $datos['collector_id'],
            $datos['start_time'] ?? null
        );

        return ['entidad' => 'collection_routes', 'id' => $ruta->id, 'client_uuid' => $ruta->client_uuid];
    }

    private function verificarRecepcion(User $usuario, array $payload, string $clientUuid): array
    {
        $datos = $this->validar($payload, [
            'flowmeter_liters' => ['required', 'numeric', 'min:0'],
            'verification_status' => ['required', 'in:verificado,incompleto,con_observacion'],
            'observation' => ['nullable', 'string', 'max:500'],
            'ruta_id' => ['nullable', 'integer'],
            'ruta_client_uuid' => ['nullable', 'uuid'],
        ]);

        $ruta = $this->resolverRuta($usuario, $payload);

        $recepcion = $this->planta->verificarRecepcion(
            $ruta,
            $usuario,
            (float) $datos['flowmeter_liters'],
            $datos['verification_status'],
            $datos['observation'] ?? null,
            $clientUuid
        );

        return ['entidad' => 'plant_receptions', 'id' => $recepcion->id, 'client_uuid' => $clientUuid];
    }

    private function producirQueso(User $usuario, array $payload, string $clientUuid): array
    {
        $datos = $this->validar($payload, [
            'cheese_molds_produced' => ['required', 'integer', 'min:1'],
            'batch_number' => ['nullable', 'string', 'max:50'],
            'production_date' => ['nullable', 'date'],
        ]);

        $produccion = $this->planta->producirQueso(
            $usuario,
            (int) $datos['cheese_molds_produced'],
            $datos['batch_number'] ?? null,
            $datos['production_date'] ?? null,
            $clientUuid
        );

        return ['entidad' => 'cheese_productions', 'id' => $produccion->id, 'client_uuid' => $clientUuid];
    }

    private function registrarVenta(User $usuario, array $payload, string $clientUuid): array
    {
        $datos = $this->validar($payload, [
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'customer_client_uuid' => ['nullable', 'uuid'],
            'new_first_name' => ['nullable', 'string', 'max:100'],
            'new_last_name' => ['nullable', 'string', 'max:100'],
            'new_dni_ruc' => ['nullable', 'string', 'max:20'],
            'new_phone' => ['nullable', 'string', 'max:30'],
            'new_type' => ['nullable', 'in:proveedor,mayorista,local'],
            'cheese_molds_quantity' => ['required', 'integer', 'min:1'],
            'payment_method' => ['nullable', 'in:efectivo,descuento_leche'],
            'sold_at' => ['nullable', 'date'],
        ]);

        $venta = $this->ventas->registrarVenta($usuario, $datos, $clientUuid);

        return [
            'entidad' => 'sales',
            'id' => $venta->id,
            'client_uuid' => $clientUuid,
            'receipt_number' => $venta->receipt_number,
            'unit_price' => (float) $venta->unit_price,
            'total_amount' => (float) $venta->total_amount,
        ];
    }

    private function cerrarCaja(User $usuario, array $payload, string $clientUuid): array
    {
        $datos = $this->validar($payload, [
            'notes' => ['nullable', 'string', 'max:500'],
            'fecha' => ['nullable', 'date'],
        ]);

        $cierre = $this->ventas->cerrarCaja($usuario, $datos['notes'] ?? null, $datos['fecha'] ?? null, $clientUuid);

        return [
            'entidad' => 'daily_cash_closures',
            'id' => $cierre->id,
            'client_uuid' => $clientUuid,
            'total_cash' => (float) $cierre->total_cash,
            'total_amount' => (float) $cierre->total_amount,
        ];
    }

    private function registrarAnalisis(User $usuario, array $payload, string $clientUuid): array
    {
        $datos = $this->validar($payload, [
            'producer_id' => ['required', 'integer', 'exists:users,id'],
            'analysis_date' => ['required', 'date'],
            'fat_percentage' => ['nullable', 'numeric'],
            'snf_percentage' => ['nullable', 'numeric'],
            'density' => ['nullable', 'numeric'],
            'protein_percentage' => ['nullable', 'numeric'],
            'water_addition_percentage' => ['nullable', 'numeric'],
            'temperature' => ['nullable', 'numeric'],
            'ph_or_acidity' => ['nullable', 'numeric'],
            'verdict' => ['required', 'in:conforme,acidez_alta,adulterada,sospechosa'],
            'notes' => ['nullable', 'string', 'max:500'],
            'schedule_visit' => ['nullable', 'boolean'],
            'scheduled_date' => ['nullable', 'date'],
            'scheduled_time' => ['nullable', 'string'],
            'visit_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $analisis = $this->calidad->registrarAnalisis($usuario, $datos, $clientUuid);

        return ['entidad' => 'lactoscan_analyses', 'id' => $analisis->id, 'client_uuid' => $clientUuid];
    }

    private function agendarVisita(User $usuario, array $payload, string $clientUuid): array
    {
        $datos = $this->validar($payload, [
            'analysis_id' => ['nullable', 'integer'],
            'analysis_client_uuid' => ['nullable', 'uuid'],
            'scheduled_date' => ['required', 'date'],
            'scheduled_time' => ['nullable', 'string'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $analisis = $this->buscarAnalisis($datos);

        $visita = $this->calidad->agendarVisita(
            $analisis,
            $usuario,
            $datos['scheduled_date'],
            $datos['scheduled_time'] ?? null,
            $datos['reason'],
            $clientUuid
        );

        return ['entidad' => 'technical_visits', 'id' => $visita->id, 'client_uuid' => $clientUuid];
    }

    private function buscarAnalisis(array $datos): LactoscanAnalysis
    {
        $analisis = !empty($datos['analysis_id'])
            ? LactoscanAnalysis::find($datos['analysis_id'])
            : null;

        if (!$analisis && !empty($datos['analysis_client_uuid'])) {
            $analisis = LactoscanAnalysis::where('client_uuid', $datos['analysis_client_uuid'])->first();
        }

        if (!$analisis) {
            throw new ReglaNegocioException('El análisis Lactoscan indicado no existe en el servidor.');
        }

        return $analisis;
    }

    private function completarVisita(array $payload): array
    {
        $datos = $this->validar($payload, [
            'visit_id' => ['required', 'integer', 'exists:technical_visits,id'],
            'resolution_report' => ['required', 'string', 'max:1000'],
        ]);

        $visita = $this->calidad->completarVisita(
            TechnicalVisit::findOrFail($datos['visit_id']),
            $datos['resolution_report']
        );

        return ['entidad' => 'technical_visits', 'id' => $visita->id];
    }

    private function solicitarCambioZona(User $usuario, array $payload, string $clientUuid): array
    {
        $datos = $this->validar($payload, [
            'requested_zone_id' => ['required', 'integer', 'exists:zones,id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $solicitud = $this->zonas->solicitarCambio(
            $usuario,
            (int) $datos['requested_zone_id'],
            $datos['reason'] ?? null,
            $clientUuid
        );

        return ['entidad' => 'zone_change_requests', 'id' => $solicitud->id, 'client_uuid' => $clientUuid];
    }

    private function revisarSolicitudZona(User $usuario, array $payload): array
    {
        $datos = $this->validar($payload, [
            'request_id' => ['required', 'integer', 'exists:zone_change_requests,id'],
            'decision' => ['required', 'in:aprobado,rechazado'],
        ]);

        $solicitud = $this->zonas->revisar(
            ZoneChangeRequest::findOrFail($datos['request_id']),
            $usuario,
            $datos['decision']
        );

        return ['entidad' => 'zone_change_requests', 'id' => $solicitud->id, 'estado' => $solicitud->status];
    }

    private function autorizarPago(User $usuario, array $payload): array
    {
        $datos = $this->validar($payload, [
            'producer_id' => ['required_without:todos', 'nullable', 'integer', 'exists:users,id'],
            'todos' => ['nullable', 'boolean'],
        ]);

        if (!empty($datos['todos'])) {
            $productores = User::where('role', 'productor')->where('is_active', true)->get();
            $creadas = [];

            foreach ($productores as $productor) {
                if ($this->liquidaciones->sobreAutorizado($productor)) {
                    continue;
                }

                $liquidacion = $this->liquidaciones->autorizar($productor, $usuario);
                if ($liquidacion) {
                    $creadas[] = $liquidacion->id;
                }
            }

            return ['entidad' => 'producer_settlements', 'ids' => $creadas, 'total' => count($creadas)];
        }

        $productor = User::where('role', 'productor')->findOrFail($datos['producer_id']);
        $liquidacion = $this->liquidaciones->autorizar($productor, $usuario);

        if (!$liquidacion) {
            throw new ReglaNegocioException("{$productor->name} no tiene litros ni deducciones que liquidar en el ciclo abierto.");
        }

        return [
            'entidad' => 'producer_settlements',
            'id' => $liquidacion->id,
            'net_total' => (float) $liquidacion->net_total,
        ];
    }

    private function entregarSobre(User $usuario, array $payload): array
    {
        $datos = $this->validar($payload, [
            'producer_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $liquidacion = $this->liquidaciones->entregarSobre(
            User::findOrFail($datos['producer_id']),
            $usuario
        );

        return [
            'entidad' => 'producer_settlements',
            'id' => $liquidacion->id,
            'net_total' => (float) $liquidacion->net_total,
        ];
    }

    private function registrarEgreso(User $usuario, array $payload, string $clientUuid): array
    {
        $datos = $this->validar($payload, [
            'category' => ['required', 'in:pago_personal,combustible_ruta,insumos_planta,mantenimiento,otros'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'beneficiary_name' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', 'in:efectivo,transferencia'],
            'receipt_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $egreso = $this->sistema->registrarEgreso($usuario, $datos, $clientUuid);

        return ['entidad' => 'operational_expenses', 'id' => $egreso->id, 'client_uuid' => $clientUuid];
    }

    private function actualizarTarifas(User $usuario, array $payload, string $clientUuid): array
    {
        $datos = $this->validar($payload, [
            'season_name' => ['required', 'string', 'max:100'],
            'price_milk_base' => ['required', 'numeric', 'min:0.5', 'max:10'],
            'price_milk_water_penalty_low' => ['required', 'numeric', 'min:0.5', 'max:10'],
            'price_milk_water_penalty_high' => ['required', 'numeric', 'min:0.1', 'max:10'],
            'price_cheese_provider' => ['required', 'numeric', 'min:5', 'max:100'],
            'price_cheese_wholesale' => ['required', 'numeric', 'min:5', 'max:100'],
            'price_cheese_local' => ['required', 'numeric', 'min:5', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $tarifa = $this->sistema->actualizarTarifas($usuario, $datos, $clientUuid);

        return ['entidad' => 'system_prices', 'id' => $tarifa->id, 'client_uuid' => $clientUuid];
    }

    private function publicarAviso(User $usuario, array $payload, string $clientUuid): array
    {
        $datos = $this->validar($payload, [
            'title' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'target_role' => ['nullable', 'string'],
            'target_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $aviso = $this->sistema->publicarAviso($usuario, [
            'title' => $datos['title'],
            'message' => $datos['message'],
            'start_date' => $datos['start_date'],
            'end_date' => $datos['end_date'],
            'target_role' => $datos['target_role'] ?? null,
            'target_user_id' => $datos['target_user_id'] ?? null,
        ], $clientUuid);

        return ['entidad' => 'announcements', 'id' => $aviso->id, 'client_uuid' => $clientUuid];
    }
}
