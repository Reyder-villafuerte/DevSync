<?php

/**
 * Catálogo de sincronización para la app móvil (MilkFlowMovil).
 *
 * Cada entidad declara:
 *  - modelo:   clase Eloquent de origen.
 *  - columnas: columnas que viajan al dispositivo (nunca password ni tokens).
 *  - acceso:   ámbito por rol. El rol '*' es el valor por defecto.
 *
 * Ámbitos soportados (ver App\Services\Sync\ResolutorAmbito):
 *  - todos                : todas las filas.
 *  - ninguno              : el rol no recibe la entidad.
 *  - propio:<columna>     : filas cuya <columna> es igual al id del usuario.
 *  - padron               : usuarios visibles (productores, acopiadores y uno mismo).
 *  - mis_rutas            : filas ligadas a rutas donde el usuario es el acopiador.
 *  - mis_registros        : entregas propias (como productor) o de mis rutas (como acopiador).
 *  - mis_compras          : ventas cuyo cliente está vinculado al usuario.
 *  - avisos               : anuncios dirigidos al rol o al usuario.
 */

use App\Models\Announcement;
use App\Models\CheeseProduction;
use App\Models\CollectionRecord;
use App\Models\CollectionRoute;
use App\Models\Customer;
use App\Models\DailyCashClosure;
use App\Models\InventoryStock;
use App\Models\LactoscanAnalysis;
use App\Models\OperationalExpense;
use App\Models\PlantReception;
use App\Models\ProducerDeduction;
use App\Models\ProducerSettlement;
use App\Models\Sale;
use App\Models\SystemPrice;
use App\Models\TechnicalVisit;
use App\Models\User;
use App\Models\Zone;
use App\Models\ZoneChangeRequest;

return [

    /*
     * Margen (en segundos) que se resta al cursor de bajada para tolerar
     * desfases de reloj entre el servidor de aplicación y la base de datos.
     */
    'margen_cursor_segundos' => 2,

    /* Tope de filas por entidad en una sola bajada. */
    'limite_por_entidad' => 500,

    'entidades' => [

        'zones' => [
            'modelo' => Zone::class,
            'columnas' => ['id', 'code', 'name', 'description', 'is_active', 'updated_at'],
            'acceso' => ['*' => 'todos'],
        ],

        'users' => [
            'modelo' => User::class,
            'columnas' => ['id', 'name', 'email', 'role', 'phone', 'dni', 'zone_id', 'is_active', 'updated_at'],
            'acceso' => [
                'productor' => 'propio:id',
                '*' => 'padron',
            ],
        ],

        'system_prices' => [
            'modelo' => SystemPrice::class,
            'columnas' => ['id', 'client_uuid', 'season_name', 'price_milk_base', 'price_milk_water_penalty_low',
                'price_milk_water_penalty_high', 'price_cheese_provider', 'price_cheese_wholesale',
                'price_cheese_local', 'is_active', 'updated_by', 'notes', 'updated_at'],
            'acceso' => ['*' => 'todos'],
        ],

        'inventory_stocks' => [
            'modelo' => InventoryStock::class,
            'columnas' => ['id', 'item_code', 'item_name', 'current_stock', 'unit', 'updated_at'],
            'acceso' => [
                'productor' => 'ninguno',
                '*' => 'todos',
            ],
        ],

        'collection_routes' => [
            'modelo' => CollectionRoute::class,
            'columnas' => ['id', 'client_uuid', 'date', 'zone_id', 'collector_id', 'start_time', 'status',
                'total_collected_liters', 'updated_at'],
            'acceso' => [
                'acopiador' => 'propio:collector_id',
                '*' => 'todos',
            ],
        ],

        'collection_records' => [
            'modelo' => CollectionRecord::class,
            'columnas' => ['id', 'client_uuid', 'collection_route_id', 'producer_id', 'liters', 'collected_at',
                'notes', 'updated_at'],
            'acceso' => [
                'productor' => 'propio:producer_id',
                'acopiador' => 'mis_registros',
                '*' => 'todos',
            ],
        ],

        'plant_receptions' => [
            'modelo' => PlantReception::class,
            'columnas' => ['id', 'client_uuid', 'collection_route_id', 'verifier_id', 'collector_declared_liters',
                'flowmeter_liters', 'difference_liters', 'verification_status', 'observation', 'verified_at', 'updated_at'],
            'acceso' => [
                'productor' => 'ninguno',
                'acopiador' => 'mis_rutas',
                '*' => 'todos',
            ],
        ],

        'cheese_productions' => [
            'modelo' => CheeseProduction::class,
            'columnas' => ['id', 'client_uuid', 'production_date', 'supervisor_id', 'cheese_molds_produced',
                'milk_liters_used', 'batch_number', 'status', 'updated_at'],
            'acceso' => [
                'productor' => 'ninguno',
                'acopiador' => 'ninguno',
                'pagador_campo' => 'ninguno',
                '*' => 'todos',
            ],
        ],

        'customers' => [
            'modelo' => Customer::class,
            'columnas' => ['id', 'client_uuid', 'first_name', 'last_name', 'dni_ruc', 'phone', 'type',
                'linked_user_id', 'is_wholesale_approved', 'updated_at'],
            'acceso' => [
                'productor' => 'propio:linked_user_id',
                'acopiador' => 'ninguno',
                'pagador_campo' => 'ninguno',
                '*' => 'todos',
            ],
        ],

        'sales' => [
            'modelo' => Sale::class,
            'columnas' => ['id', 'client_uuid', 'receipt_number', 'customer_id', 'seller_id', 'closure_id',
                'cheese_molds_quantity', 'unit_price', 'total_amount', 'payment_method', 'sold_at', 'updated_at'],
            'acceso' => [
                'productor' => 'mis_compras',
                'acopiador' => 'ninguno',
                '*' => 'todos',
            ],
        ],

        'daily_cash_closures' => [
            'modelo' => DailyCashClosure::class,
            'columnas' => ['id', 'client_uuid', 'date', 'closed_by', 'total_cash', 'total_milk_discount',
                'total_amount', 'cheese_molds_quantity', 'sales_count', 'notes', 'closed_at', 'updated_at'],
            'acceso' => [
                'personal_venta' => 'todos',
                'admin' => 'todos',
                'jefe_general' => 'todos',
                '*' => 'ninguno',
            ],
        ],

        'lactoscan_analyses' => [
            'modelo' => LactoscanAnalysis::class,
            'columnas' => ['id', 'client_uuid', 'producer_id', 'inspector_id', 'analysis_date', 'fat_percentage',
                'snf_percentage', 'density', 'protein_percentage', 'water_addition_percentage', 'temperature',
                'ph_or_acidity', 'verdict', 'notes', 'updated_at'],
            'acceso' => [
                'productor' => 'propio:producer_id',
                'acopiador' => 'ninguno',
                '*' => 'todos',
            ],
        ],

        'technical_visits' => [
            'modelo' => TechnicalVisit::class,
            'columnas' => ['id', 'client_uuid', 'lactoscan_analysis_id', 'producer_id', 'inspector_id',
                'scheduled_date', 'scheduled_time', 'status', 'reason', 'resolution_report', 'updated_at'],
            'acceso' => [
                'productor' => 'propio:producer_id',
                'acopiador' => 'ninguno',
                '*' => 'todos',
            ],
        ],

        'zone_change_requests' => [
            'modelo' => ZoneChangeRequest::class,
            'columnas' => ['id', 'client_uuid', 'producer_id', 'current_zone_id', 'requested_zone_id', 'status',
                'reason', 'reviewed_by', 'reviewed_at', 'updated_at'],
            'acceso' => [
                'productor' => 'propio:producer_id',
                'admin' => 'todos',
                'jefe_general' => 'todos',
                '*' => 'ninguno',
            ],
        ],

        'producer_settlements' => [
            'modelo' => ProducerSettlement::class,
            'columnas' => ['id', 'client_uuid', 'settlement_code', 'producer_id', 'start_date', 'end_date',
                'total_liters', 'price_per_liter', 'gross_total', 'deductions_total', 'net_total', 'status',
                'paid_at', 'payment_method', 'paid_by', 'notes', 'updated_at'],
            'acceso' => [
                'productor' => 'propio:producer_id',
                'acopiador' => 'ninguno',
                'inspector_calidad' => 'ninguno',
                '*' => 'todos',
            ],
        ],

        'producer_deductions' => [
            'modelo' => ProducerDeduction::class,
            'columnas' => ['id', 'client_uuid', 'producer_id', 'settlement_id', 'sale_id', 'date', 'concept',
                'amount', 'status', 'created_by', 'notes', 'updated_at'],
            'acceso' => [
                'productor' => 'propio:producer_id',
                'acopiador' => 'ninguno',
                'inspector_calidad' => 'ninguno',
                '*' => 'todos',
            ],
        ],

        'operational_expenses' => [
            'modelo' => OperationalExpense::class,
            'columnas' => ['id', 'client_uuid', 'category', 'description', 'amount', 'expense_date', 'user_id',
                'beneficiary_name', 'payment_method', 'receipt_number', 'registered_by', 'notes', 'updated_at'],
            'acceso' => [
                'admin' => 'todos',
                'jefe_general' => 'todos',
                '*' => 'ninguno',
            ],
        ],

        'announcements' => [
            'modelo' => Announcement::class,
            'columnas' => ['id', 'client_uuid', 'title', 'message', 'start_date', 'end_date', 'target_role',
                'target_user_id', 'created_by', 'is_active', 'updated_at'],
            'acceso' => [
                'admin' => 'todos',
                'jefe_general' => 'todos',
                '*' => 'avisos',
            ],
        ],
    ],

    /*
     * Orden de bajada: las entidades de las que otras dependen van primero,
     * para que el dispositivo nunca inserte una fila hija antes que su padre.
     */
    'orden_pull' => [
        'zones',
        'users',
        'system_prices',
        'inventory_stocks',
        'collection_routes',
        'collection_records',
        'plant_receptions',
        'cheese_productions',
        'customers',
        'sales',
        'daily_cash_closures',
        'lactoscan_analyses',
        'technical_visits',
        'zone_change_requests',
        'producer_settlements',
        'producer_deductions',
        'operational_expenses',
        'announcements',
    ],

    /*
     * Comandos de subida permitidos por rol (ver App\Services\Sync\SyncService).
     */
    'comandos' => [
        'abrir_ruta' => ['acopiador'],
        'registrar_entrega' => ['acopiador'],
        'cerrar_ruta' => ['acopiador'],
        'asignar_ruta' => ['admin', 'jefe_general'],
        'verificar_recepcion' => ['jefe_produccion', 'jefe_general'],
        'producir_queso' => ['jefe_produccion', 'jefe_general'],
        'registrar_venta' => ['personal_venta', 'jefe_general'],
        'cerrar_caja' => ['personal_venta', 'jefe_general'],
        'registrar_analisis' => ['inspector_calidad', 'jefe_general'],
        'agendar_visita' => ['inspector_calidad', 'jefe_general'],
        'completar_visita' => ['inspector_calidad', 'jefe_general'],
        'solicitar_cambio_zona' => ['productor'],
        'revisar_solicitud_zona' => ['admin', 'jefe_general'],
        'autorizar_pago' => ['personal_pago', 'admin', 'jefe_general'],
        'entregar_sobre' => ['pagador_campo', 'personal_pago', 'jefe_general'],
        'registrar_egreso' => ['admin', 'jefe_general'],
        'actualizar_tarifas' => ['admin', 'jefe_general'],
        'publicar_aviso' => ['admin', 'jefe_general'],
    ],
];
