<?php

namespace App\Services;

use App\Models as M;
use Illuminate\Support\Facades\Auth;

class Modules
{
    /** Field definitions drive reusable forms; validation and business rules run on the server. */
    public static function definitions(): array
    {
        return [
            'producers' => [
                'title' => 'Productores',
                'model' => M\Productor::class,
                'permission' => 'catalogs.manage',
                'columns' => [
                    'nombre' => 'Nombre',
                    'documento' => 'Documento',
                    'telefono' => 'Teléfono',
                    'zona_id' => 'Zona',
                    'sector_id' => 'Sector',
                    'activo' => 'Activo'
                ],
                'fields' => [
                    'nombre' => ['Nombre', 'text', 'required|string|max:150'],
                    'documento' => ['Documento', 'text', 'required|alpha_num|between:8,20'],
                    'telefono' => ['Teléfono', 'text', 'required|regex:/^\+?[0-9 ()-]{9,20}$/'],
                    'sector_id' => ['Sector', 'sectores', 'required|exists:sectores,id'],
                    'user_id' => ['Cuenta del productor', 'productor_users', 'nullable|exists:users,id'],
                    'activo' => ['Estado', 'activo', 'required|boolean']
                ]
            ],

            'acopiadores' => [
                'title' => 'Acopiadores',
                'model' => M\Acopiador::class,
                'permission' => 'catalogs.manage',
                'columns' => [
                    'nombre' => 'Nombre',
                    'telefono' => 'Teléfono',
                    'activo' => 'Activo'
                ],
                'fields' => [
                    'user_id' => ['Cuenta del acopiador', 'acopiador_users', 'required|exists:users,id'],
                    'nombre' => ['Nombre', 'text', 'required|string|max:150'],
                    'telefono' => ['Teléfono', 'text', 'required|string|max:25'],
                    'activo' => ['Estado', 'activo', 'required|boolean']
                ]
            ],

            'zones' => [
                'title' => 'Zonas de Huari',
                'model' => M\Zona::class,
                'permission' => 'catalogs.manage',
                'columns' => [
                    'nombre' => 'Nombre',
                    'activo' => 'Activo'
                ],
                'fields' => [
                    'nombre' => ['Nombre', 'text', 'required|string|max:100'],
                    'activo' => ['Estado', 'activo', 'required|boolean']
                ]
            ],

            'sectors' => [
                'title' => 'Sectores',
                'model' => M\Sector::class,
                'permission' => 'catalogs.manage',
                'columns' => [
                    'nombre' => 'Nombre',
                    'zona_id' => 'Zona',
                    'activo' => 'Activo'
                ],
                'fields' => [
                    'nombre' => ['Nombre', 'text', 'required|string|max:100'],
                    'zona_id' => ['Zona', 'zonas', 'required|exists:zonas,id'],
                    'activo' => ['Estado', 'activo', 'required|boolean']
                ]
            ],

            'routes' => [
                'title' => 'Rutas de acopio',
                'model' => M\Ruta::class,
                'permission' => 'catalogs.manage',
                'columns' => [
                    'nombre' => 'Nombre',
                    'codigo' => 'Código',
                    'vehiculo' => 'Vehículo',
                    'hora_inicio' => 'Inicio',
                    'hora_fin' => 'Fin',
                    'activo' => 'Activo'
                ],
                'fields' => [
                    'nombre' => ['Nombre', 'text', 'required|string|max:100'],
                    'codigo' => ['Código', 'text', 'required|string|max:30'],
                    'vehiculo' => ['Vehículo', 'text', 'required|string|max:100'],
                    'acopiador_id' => ['Acopiador', 'acopiadores', 'required|exists:acopiadores,id'],
                    'sectores' => ['Sectores (2 o 3)', 'sectores_multi', 'required|array|min:2|max:3'],
                    'hora_inicio' => ['Hora de inicio', 'time', 'required|date_format:H:i'],
                    'hora_fin' => ['Hora de fin', 'time', 'required|date_format:H:i|after:hora_inicio'],
                    'activo' => ['Estado', 'activo', 'required|boolean']
                ]
            ],

            'entregas' => [
                'title' => 'Entregas de leche',
                'model' => M\Entrega::class,
                'permission' => 'entregas.create',
                'columns' => [
                    'uuid' => 'Referencia',
                    'productor_id' => 'Productor',
                    'tipo' => 'Tipo',
                    'litros' => 'Litros',
                    'fecha_hora' => 'Fecha y hora',
                    'estado' => 'Estado'
                ],
                'fields' => [
                    'uuid' => ['UUID', 'hidden', 'required|uuid'],
                    'productor_id' => ['Productor activo', 'productores', 'required|exists:productores,id'],
                    'tipo' => ['Tipo de entrega', 'tipos_entrega', 'required|in:DIRECTA,RECOGIDA'],
                    'ruta_id' => ['Ruta asignada', 'rutas', 'nullable|required_if:tipo,RECOGIDA|exists:rutas,id'],
                    'litros' => ['Litros', 'number', 'required|numeric|gt:0|max:100000'],
                    'observacion' => ['Observación', 'textarea', 'nullable|string|max:2000']
                ]
            ],

            'calidad' => [
                'title' => 'Control de calidad',
                'model' => M\PruebaCalidad::class,
                'permission' => 'quality.manage',
                'columns' => [
                    'entrega_id' => 'Entrega',
                    'ph' => 'pH',
                    'temperatura' => 'Temperatura °C',
                    'agua_agregada_porcentaje' => 'Agua %',
                    'resultado' => 'Resultado',
                    'fecha_hora' => 'Fecha'
                ],
                'fields' => [
                    'entrega_id' => ['Entrega por revisar', 'entregas_pendientes', 'required|exists:entregas,id'],
                    'ph' => ['pH', 'number', 'required|numeric|between:0,14'],
                    'temperatura' => ['Temperatura °C', 'number', 'required|numeric|between:-10,100'],
                    'agua_agregada_porcentaje' => ['Agua agregada % (Lactoscan)', 'number', 'required|numeric|between:0,100'],
                    'observacion' => ['Observación', 'textarea', 'nullable|string|max:2000']
                ]
            ],

            'problemas' => [
                'title' => 'Problemas de leche',
                'model' => M\ProblemaLeche::class,
                'permission' => 'quality.manage',
                'columns' => [
                    'entrega_id' => 'Entrega',
                    'tipo' => 'Tipo',
                    'descripcion' => 'Descripción',
                    'severidad' => 'Severidad',
                    'estado' => 'Estado'
                ],
                'fields' => [
                    'entrega_id' => ['Entrega', 'entregas', 'required|exists:entregas,id'],
                    'tipo' => ['Tipo', 'tipos_problema', 'required|in:ACIDEZ,ADULTERACION,HIGIENE,OTRO'],
                    'descripcion' => ['Descripción', 'textarea', 'required|string|max:2000'],
                    'severidad' => ['Severidad', 'severidad', 'required|in:BAJA,MEDIA,ALTA'],
                    'estado' => ['Estado', 'problema_estado', 'required|in:ABIERTO,RESUELTO']
                ]
            ],

            'capacitaciones' => [
                'title' => 'Capacitación y visitas BPO',
                'model' => M\Capacitacion::class,
                'permission' => 'quality.manage',
                'columns' => [
                    'productor_id' => 'Productor',
                    'tema' => 'Tema',
                    'fecha' => 'Fecha',
                    'estado' => 'Estado'
                ],
                'fields' => [
                    'fecha' => ['Fecha de visita', 'date', 'required|date'],
                    'estado' => ['Estado', 'capacitacion_estado', 'required|in:PENDIENTE,REALIZADA'],
                    'observacion' => ['Observación de la visita', 'textarea', 'nullable|string|max:2000']
                ]
            ],

            'lotes' => [
                'title' => 'Lotes de producción',
                'model' => M\LoteProduccion::class,
                'permission' => 'production.manage',
                'columns' => [
                    'codigo_lote' => 'Lote',
                    'tipo_producto' => 'Producto',
                    'litros_leche' => 'Litros usados',
                    'moldes_obtenidos' => 'Moldes',
                    'rendimiento' => 'Moldes / 100 L',
                    'fecha' => 'Fecha'
                ],
                'fields' => [
                    'codigo_lote' => ['Código de lote', 'text', 'required|string|max:50|unique:lotes_produccion,codigo_lote'],
                    'tipo_producto' => ['Tipo de producto', 'productos', 'required|in:Queso para fresco,Queso para pasteurizado,Modulado,Yogurt'],
                    'litros_leche' => ['Litros de leche conforme', 'number', 'required|numeric|gt:0|max:100000'],
                    'moldes_obtenidos' => ['Moldes / unidades obtenidos', 'number', 'required|integer|min:1|max:100000'],
                    'observaciones' => ['Observaciones', 'textarea', 'nullable|string|max:2000']
                ]
            ],

            'ventas' => [
                'title' => 'Ventas de queso',
                'model' => M\Venta::class,
                'permission' => 'sales.manage',
                'columns' => [
                    'cliente' => 'Cliente',
                    'tipo_cliente' => 'Tipo',
                    'cantidad' => 'Cantidad',
                    'precio_unitario' => 'Precio S/',
                    'total' => 'Total S/',
                    'fecha' => 'Fecha'
                ],
                'fields' => [
                    'uuid' => ['UUID', 'hidden', 'required|uuid'],
                    'cliente' => ['Cliente', 'text', 'required|string|max:150'],
                    'tipo_cliente' => ['Tipo de cliente', 'clientes', 'required|in:Mayorista,Productor / Socio,Público General'],
                    'stock_queso_id' => ['Queso disponible', 'stock', 'required|exists:stock_quesos,id'],
                    'cantidad' => ['Cantidad', 'number', 'required|integer|min:1|max:100000']
                ]
            ],

            'stock' => [
                'title' => 'Stock disponible',
                'model' => M\StockQueso::class,
                'permission' => 'sales.manage',
                'columns' => [
                    'tipo_producto' => 'Producto',
                    'cantidad' => 'Moldes disponibles'
                ],
                'fields' => []
            ],

            'liquidaciones' => [
                'title' => 'Liquidaciones semanales',
                'model' => M\Liquidacion::class,
                'permission' => 'settlements.manage',
                'columns' => [
                    'productor_id' => 'Productor',
                    'periodo_inicio' => 'Desde (jueves)',
                    'periodo_fin' => 'Hasta (miércoles)',
                    'litros' => 'Litros',
                    'tarifa' => 'Tarifa S/',
                    'subtotal' => 'Subtotal',
                    'descuentos' => 'Descuentos',
                    'penalizaciones' => 'Penalizaciones',
                    'total' => 'Total S/',
                    'estado' => 'Estado'
                ],
                'fields' => [
                    'productor_id' => ['Productor', 'todos_productores', 'required|exists:productores,id'],
                    'periodo' => ['Fecha dentro de la semana a liquidar', 'date', 'required|date|before:today']
                ]
            ],

            'comunicados' => [
                'title' => 'Comunicados de planta',
                'model' => M\Comunicado::class,
                'permission' => 'announcements.manage',
                'columns' => [
                    'titulo' => 'Título',
                    'mensaje' => 'Mensaje',
                    'fecha' => 'Fecha',
                    'activo' => 'Activo'
                ],
                'fields' => [
                    'titulo' => ['Título', 'text', 'required|string|max:180'],
                    'mensaje' => ['Mensaje', 'textarea', 'required|string|max:5000'],
                    'fecha' => ['Fecha', 'date', 'required|date'],
                    'activo' => ['Estado', 'activo', 'required|boolean']
                ]
            ],

            'rotaciones' => [
                'title' => 'Rotación estacional',
                'model' => M\RotacionProductor::class,
                'permission' => 'rotation.request',
                'columns' => [
                    'productor_id' => 'Productor',
                    'zona_anterior_id' => 'Zona anterior',
                    'zona_nueva_id' => 'Zona nueva',
                    'fecha_efectiva' => 'Fecha efectiva',
                    'referencia' => 'Referencia',
                    'estado' => 'Estado'
                ],
                'fields' => [
                    'productor_id' => ['Productor', 'todos_productores', 'required|exists:productores,id'],
                    'sector_nuevo_id' => ['Nuevo sector / zona', 'sectores', 'required|exists:sectores,id'],
                    'fecha_efectiva' => ['Fecha efectiva', 'date', 'required|date|after_or_equal:today'],
                    'referencia' => ['Referencia / motivo', 'textarea', 'required|string|max:500']
                ]
            ],

            'audit' => [
                'title' => 'Auditoría',
                'model' => M\Auditoria::class,
                'permission' => 'audit.view',
                'columns' => [
                    'usuario_id' => 'Usuario',
                    'accion' => 'Acción',
                    'modelo' => 'Modelo',
                    'registro' => 'Registro',
                    'fecha' => 'Fecha',
                    'datos_anteriores' => 'Datos anteriores',
                    'datos_nuevos' => 'Datos nuevos'
                ],
                'fields' => []
            ],
        ];
    }

    public static function get(string $key): array
    {
        return self::definitions()[$key] ?? abort(404);
    }

    public static function editable(string $key): bool
    {
        return in_array($key, [
            'producers',
            'acopiadores',
            'zones',
            'sectors',
            'routes',
            'problemas',
            'capacitaciones',
            'comunicados'
        ]);
    }




    public static function options(string $type): array
    {
        /** @var User|null $u */
        $u = Auth::user();

        return match ($type) {

            'activo' => [
                1 => 'Activo',
                0 => 'Inactivo',
            ],

            'sectores', 'sectores_multi' =>
            M\Sector::with('zona')
                ->where('activo', true)
                ->get()
                ->mapWithKeys(fn($s) => [
                    $s->id => $s->zona->nombre . ' / ' . $s->nombre,
                ])
                ->all(),

            'zonas' =>
            M\Zona::where('activo', true)
                ->pluck('nombre', 'id')
                ->all(),

            'productor_users', 'acopiador_users' =>
            M\User::where('status', 'ACTIVO')
                ->whereHas(
                    'roles',
                    fn($q) => $q->where(
                        'slug',
                        $type === 'productor_users'
                            ? 'productor'
                            : 'acopiador'
                    )
                )
                ->pluck('name', 'id')
                ->all(),

            'acopiadores' =>
            M\Acopiador::where('activo', true)
                ->pluck('nombre', 'id')
                ->all(),

            'productores', 'todos_productores' =>
            M\Productor::when(
                $type === 'productores',
                fn($q) => $q
                    ->where('activo', true)
                    ->whereNull('expulsado_at')
            )
                ->when(
                    $u !== null && $u->hasRole('productor'),
                    fn($q) => $q->where('user_id', $u->id)
                )
                ->pluck('nombre', 'id')
                ->all(),

            'rutas' =>
            M\Ruta::where('activo', true)
                ->when(
                    $u !== null && $u->hasRole('acopiador'),
                    fn($q) => $q->where(
                        'acopiador_id',
                        $u->acopiador?->id ?? 0
                    )
                )
                ->pluck('nombre', 'id')
                ->all(),

            'tipos_entrega' =>
            $u !== null && $u->hasRole('acopiador')
                ? [
                    'RECOGIDA' => 'Recogida',
                ]
                : [
                    'DIRECTA' => 'Directa',
                    'RECOGIDA' => 'Recogida',
                ],

            'entregas', 'entregas_pendientes' =>
            M\Entrega::when(
                $type === 'entregas_pendientes',
                fn($q) => $q->whereDoesntHave('pruebaCalidad')
            )
                ->with('productor')
                ->latest()
                ->limit(1000)
                ->get()
                ->mapWithKeys(fn($e) => [
                    $e->id =>
                    $e->productor->nombre .
                        ' · ' .
                        $e->litros .
                        ' L · ' .
                        $e->uuid,
                ])
                ->all(),

            'productos' =>
            array_combine(
                [
                    'Queso para fresco',
                    'Queso para pasteurizado',
                    'Modulado',
                    'Yogurt',
                ],
                [
                    'Queso para fresco',
                    'Queso para pasteurizado',
                    'Modulado',
                    'Yogurt',
                ]
            ),

            'clientes' => [
                'Mayorista' =>
                'Mayorista · S/ 20.00',

                'Productor / Socio' =>
                'Productor / Socio · S/ 18.00',

                'Público General' =>
                'Público General · S/ 21.00',
            ],

            'stock' =>
            M\StockQueso::all()
                ->mapWithKeys(fn($s) => [
                    $s->id =>
                    $s->tipo_producto .
                        ' · ' .
                        $s->cantidad .
                        ' disponibles',
                ])
                ->all(),

            'tipos_problema' =>
            array_combine(
                [
                    'ACIDEZ',
                    'ADULTERACION',
                    'HIGIENE',
                    'OTRO',
                ],
                [
                    'Acidez',
                    'Adulteración',
                    'Higiene',
                    'Otro',
                ]
            ),

            'severidad' => [
                'BAJA' => 'Baja',
                'MEDIA' => 'Media',
                'ALTA' => 'Alta',
            ],

            'problema_estado' => [
                'ABIERTO' => 'Abierto',
                'RESUELTO' => 'Resuelto',
            ],

            'capacitacion_estado' => [
                'PENDIENTE' => 'Pendiente',
                'REALIZADA' => 'Realizada',
            ],

            default => [],
        };
    }
}
