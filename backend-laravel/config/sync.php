<?php

use App\Models\Aviso;
use App\Models\ControlCalidad;
use App\Models\DescargaTina;
use App\Models\Liquidacion;
use App\Models\MovimientoStock;
use App\Models\PrecioCompraLeche;
use App\Models\PrecioVenta;
use App\Models\Producto;
use App\Models\Productor;
use App\Models\RegistroAcopio;
use App\Models\Ruta;
use App\Models\RutaAcopio;
use App\Models\Sancion;
use App\Models\SolicitudCambioZona;
use App\Models\Zona;

return [

    // Presupuesto global de filas por respuesta de /api/sync/pull. La bajada
    // recorre las entidades en orden y corta al llegar a este número,
    // marcando hay_mas para que el cliente vuelva a pedir de inmediato.
    'lote_pull' => (int) env('SYNC_LOTE_PULL', 500),

    // Colchón de reloj (segundos). Al pedir "desde X" el servidor resta este
    // margen: prefiere reenviar filas ya vistas (el cliente las aplica de forma
    // idempotente) antes que perder una escritura con timestamp casi igual al
    // corte anterior.
    'margen_reloj_segundos' => (int) env('SYNC_MARGEN_RELOJ', 2),

    'lote_push_max' => (int) env('SYNC_LOTE_PUSH_MAX', 500),

    /*
    |--------------------------------------------------------------------------
    | Catálogo de entidades sincronizables
    |--------------------------------------------------------------------------
    | direccion : bajada | subida | ambas
    | conflicto : cómo se resuelve un choque en la SUBIDA
    |     - solo_insercion : recolecciones e inspecciones; nunca se actualizan.
    |                        Reenviar el mismo id es no-op idempotente; un id
    |                        existente con distinto contenido es conflicto.
    |     - conmutativo    : movimientos de stock; el orden no importa y cada
    |                        id se inserta una sola vez (append-only).
    |     - servidor_gana  : precios, avisos, liquidaciones; el móvil nunca
    |                        escribe, el servidor es la única autoridad.
    |     - version        : concurrencia optimista por `version` (por si se
    |                        habilita edición desde el móvil en el futuro).
    | visibilidad: estrategia de filtrado por rol para la BAJADA.
    |     todos | ninguno | propio | ruta | zona | vigentes
    |     La semántica concreta de cada estrategia por entidad vive en
    |     App\Services\Sync\ResolutorAmbito.
    */
    'entidades' => [

        'rutas' => [
            'modelo' => Ruta::class,
            'direccion' => 'bajada',
            'conflicto' => 'servidor_gana',
            'visibilidad' => [
                // El productor necesita el catálogo de rutas para ver su ruta
                // asignada y para pedir un cambio de zona/ruta (pantalla móvil).
                'acopiador' => 'ruta', 'supervisor_calidad' => 'todos', 'productor' => 'todos',
                'jefe_produccion' => 'todos', 'despacho_ventas' => 'ninguno', 'administracion' => 'todos',
            ],
        ],

        'zonas' => [
            'modelo' => Zona::class,
            'direccion' => 'bajada',
            'conflicto' => 'servidor_gana',
            'visibilidad' => [
                // 'todos' para el productor: sin el catálogo completo de zonas no
                // hay ninguna zona destino que elegir al solicitar cambio de ruta.
                'acopiador' => 'ruta', 'supervisor_calidad' => 'ruta', 'productor' => 'todos',
                'jefe_produccion' => 'todos', 'despacho_ventas' => 'ninguno', 'administracion' => 'todos',
            ],
        ],

        // El "padrón" del acopiador: solo los productores de las zonas de su ruta.
        'productores' => [
            'modelo' => Productor::class,
            'direccion' => 'bajada',
            'conflicto' => 'servidor_gana',
            'visibilidad' => [
                'acopiador' => 'ruta', 'supervisor_calidad' => 'ruta', 'productor' => 'propio',
                'jefe_produccion' => 'todos', 'despacho_ventas' => 'ninguno', 'administracion' => 'todos',
            ],
        ],

        'productos' => [
            'modelo' => Producto::class,
            'direccion' => 'bajada',
            'conflicto' => 'servidor_gana',
            'visibilidad' => [
                'acopiador' => 'ninguno', 'supervisor_calidad' => 'ninguno', 'productor' => 'todos',
                'jefe_produccion' => 'todos', 'despacho_ventas' => 'todos', 'administracion' => 'todos',
            ],
        ],

        'precios_venta' => [
            'modelo' => PrecioVenta::class,
            'direccion' => 'bajada',
            'conflicto' => 'servidor_gana',
            'visibilidad' => [
                'acopiador' => 'ninguno', 'supervisor_calidad' => 'ninguno', 'productor' => 'vigentes',
                'jefe_produccion' => 'ninguno', 'despacho_ventas' => 'todos', 'administracion' => 'todos',
            ],
        ],

        // Tarifa de compra de leche. El acopiador recibe SOLO la vigente.
        'precios_compra_leche' => [
            'modelo' => PrecioCompraLeche::class,
            'direccion' => 'bajada',
            'conflicto' => 'servidor_gana',
            'visibilidad' => [
                'acopiador' => 'vigentes', 'supervisor_calidad' => 'vigentes', 'productor' => 'vigentes',
                'jefe_produccion' => 'ninguno', 'despacho_ventas' => 'ninguno', 'administracion' => 'todos',
            ],
        ],

        'avisos' => [
            'modelo' => Aviso::class,
            'direccion' => 'bajada',
            'conflicto' => 'servidor_gana',
            'visibilidad' => [
                'acopiador' => 'ninguno', 'supervisor_calidad' => 'ninguno', 'productor' => 'vigentes',
                'jefe_produccion' => 'ninguno', 'despacho_ventas' => 'ninguno', 'administracion' => 'todos',
            ],
        ],

        // La cabecera de jornada SÍ se puede re-sincronizar (hora de cierre,
        // litros declarados): concurrencia optimista por `version`.
        'rutas_acopio' => [
            'modelo' => RutaAcopio::class,
            'direccion' => 'ambas',
            'conflicto' => 'version',
            'visibilidad' => [
                'acopiador' => 'propio', 'supervisor_calidad' => 'ruta', 'productor' => 'ninguno',
                'jefe_produccion' => 'todos', 'despacho_ventas' => 'ninguno', 'administracion' => 'todos',
            ],
        ],

        'registros_acopio' => [
            'modelo' => RegistroAcopio::class,
            'direccion' => 'ambas',
            'conflicto' => 'solo_insercion',
            'visibilidad' => [
                'acopiador' => 'propio', 'supervisor_calidad' => 'ruta', 'productor' => 'propio',
                'jefe_produccion' => 'todos', 'despacho_ventas' => 'ninguno', 'administracion' => 'todos',
            ],
        ],

        'descargas_tina' => [
            'modelo' => DescargaTina::class,
            'direccion' => 'ambas',
            'conflicto' => 'solo_insercion',
            'visibilidad' => [
                'acopiador' => 'propio', 'supervisor_calidad' => 'ninguno', 'productor' => 'ninguno',
                'jefe_produccion' => 'todos', 'despacho_ventas' => 'ninguno', 'administracion' => 'todos',
            ],
        ],

        'controles_calidad' => [
            'modelo' => ControlCalidad::class,
            'direccion' => 'ambas',
            'conflicto' => 'solo_insercion',
            'visibilidad' => [
                // El acopiador puede acarrear en su lote las inspecciones que el
                // supervisor tomó en la misma ruta (un solo dispositivo en campo).
                'acopiador' => 'ruta', 'supervisor_calidad' => 'propio', 'productor' => 'propio',
                'jefe_produccion' => 'todos', 'despacho_ventas' => 'ninguno', 'administracion' => 'todos',
            ],
        ],

        'movimientos_stock' => [
            'modelo' => MovimientoStock::class,
            'direccion' => 'ambas',
            'conflicto' => 'conmutativo',
            'visibilidad' => [
                'acopiador' => 'ninguno', 'supervisor_calidad' => 'ninguno', 'productor' => 'ninguno',
                'jefe_produccion' => 'todos', 'despacho_ventas' => 'todos', 'administracion' => 'todos',
            ],
        ],

        'liquidaciones' => [
            'modelo' => Liquidacion::class,
            'direccion' => 'bajada',
            'conflicto' => 'servidor_gana',
            'visibilidad' => [
                'acopiador' => 'ninguno', 'supervisor_calidad' => 'ninguno', 'productor' => 'propio',
                'jefe_produccion' => 'ninguno', 'despacho_ventas' => 'ninguno', 'administracion' => 'todos',
            ],
        ],

        'sanciones' => [
            'modelo' => Sancion::class,
            'direccion' => 'bajada',
            'conflicto' => 'servidor_gana',
            'visibilidad' => [
                'acopiador' => 'ninguno', 'supervisor_calidad' => 'todos', 'productor' => 'propio',
                'jefe_produccion' => 'ninguno', 'despacho_ventas' => 'ninguno', 'administracion' => 'todos',
            ],
        ],

        'solicitudes_cambio_zona' => [
            'modelo' => SolicitudCambioZona::class,
            'direccion' => 'ambas',
            'conflicto' => 'version',
            'visibilidad' => [
                'acopiador' => 'ninguno', 'supervisor_calidad' => 'ninguno', 'productor' => 'propio',
                'jefe_produccion' => 'todos', 'despacho_ventas' => 'ninguno', 'administracion' => 'todos',
            ],
        ],
    ],

    // Orden de recorrido en la bajada: catálogos antes que hechos, para que el
    // cliente pueda resolver las claves foráneas al aplicar.
    'orden_pull' => [
        'rutas', 'zonas', 'productos', 'productores',
        'precios_compra_leche', 'precios_venta', 'avisos',
        'rutas_acopio', 'registros_acopio', 'descargas_tina', 'controles_calidad',
        'movimientos_stock', 'sanciones', 'liquidaciones', 'solicitudes_cambio_zona',
    ],
];
