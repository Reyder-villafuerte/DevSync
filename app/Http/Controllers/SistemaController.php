<?php

namespace App\Http\Controllers;

use App\Models\ClientType;
use App\Models\CollectionPriceRule;
use App\Models\Customer;
use App\Models\InventoryCategory;
use App\Models\MeasurementUnit;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Las dos vistas de consulta del sistema: los roles y el catálogo.
 *
 * Los usuarios se administran aparte, en {@see UsuarioController}.
 */
class SistemaController extends Controller
{
    public const ROLES_PERMITIDOS = ['admin', 'jefe_general'];

    /**
     * Los nueve roles, qué hace cada uno y cuánta gente tiene.
     *
     * Es de solo lectura a propósito: el rol no es un dato suelto, cada uno
     * está cableado a los permisos de las pantallas. Inventar uno nuevo desde
     * la web dejaría a esa persona sin poder entrar a nada.
     */
    public function roles(Request $request)
    {
        abort_unless(in_array(Auth::user()->role, self::ROLES_PERMITIDOS, true), 403);

        $roles = collect(config('huata.roles'))->map(fn ($etiqueta, $rol) => [
            'clave' => $rol,
            'etiqueta' => $etiqueta,
            'descripcion' => $this->queHace($rol),
            'total' => User::where('role', $rol)->count(),
            'activos' => User::where('role', $rol)->where('is_active', true)->count(),
            'tipo_cliente' => ClientType::whereHas('roles', fn ($q) => $q->where('role', $rol))->first(),
        ]);

        if ($request->filled('buscar')) {
            $termino = mb_strtolower($request->buscar);

            $roles = $roles->filter(
                fn ($rol) => str_contains(mb_strtolower($rol['etiqueta'].' '.$rol['clave']), $termino)
            );
        }

        if ($request->filled('estado')) {
            $roles = $request->estado === 'con_gente'
                ? $roles->filter(fn ($rol) => $rol['total'] > 0)
                : $roles->filter(fn ($rol) => $rol['total'] === 0);
        }

        return view('admin.roles.index', compact('roles'));
    }

    /** Qué tiene cargado el sistema, con el enlace a donde se administra. */
    public function catalogo()
    {
        abort_unless(in_array(Auth::user()->role, self::ROLES_PERMITIDOS, true), 403);

        $fichas = [
            [
                'titulo' => 'Categorías de almacén',
                'total' => InventoryCategory::count(),
                'detalle' => InventoryCategory::where('is_active', true)->count().' activas',
                'icono' => 'fa-tags',
                'ruta' => route('produccion.categorias.index'),
            ],
            [
                'titulo' => 'Unidades de medida',
                'total' => MeasurementUnit::count(),
                'detalle' => 'Litros, kilos, unidades…',
                'icono' => 'fa-ruler',
                'ruta' => route('produccion.categorias.index'),
            ],
            [
                'titulo' => 'Insumos',
                'total' => Supply::where('is_active', true)->count(),
                'detalle' => Supply::where('entry_mode', Supply::ENTRADA_ACOPIO)->count().' entran por acopio',
                'icono' => 'fa-boxes-stacked',
                'ruta' => route('produccion.almacen.index'),
            ],
            [
                'titulo' => 'Productos terminados',
                'total' => Product::where('is_active', true)->count(),
                'detalle' => 'Con receta y tarifa por tipo',
                'icono' => 'fa-flask',
                'ruta' => route('produccion.productos.index'),
            ],
            [
                'titulo' => 'Tipos de cliente',
                'total' => ClientType::where('is_active', true)->count(),
                'detalle' => ClientType::has('roles')->count().' reconocen roles',
                'icono' => 'fa-user-tag',
                'ruta' => route('admin.tipos-cliente.index'),
            ],
            [
                'titulo' => 'Tarifas de acopio',
                'total' => CollectionPriceRule::where('is_active', true)->count(),
                'detalle' => 'Lo que se le paga al productor',
                'icono' => 'fa-bucket',
                'ruta' => route('admin.precios.index'),
            ],
            [
                'titulo' => 'Compras de insumos',
                'total' => Purchase::count(),
                'detalle' => Supplier::count().' nombres distintos en las boletas',
                'icono' => 'fa-truck-field',
                'ruta' => route('produccion.compras.index'),
            ],
            [
                'titulo' => 'Clientes',
                'total' => Customer::count(),
                'detalle' => 'Compradores registrados en caja',
                'icono' => 'fa-users',
                'ruta' => route('ventas.receipts'),
            ],
        ];

        return view('admin.catalogo.index', compact('fichas'));
    }

    /** Para qué sirve cada rol, en una línea. */
    private function queHace(string $rol): string
    {
        return match ($rol) {
            'productor' => 'Entrega leche en la ruta y cobra su liquidación semanal.',
            'acopiador' => 'Sale a las 4:30 AM, recoge en su zona y descarga en planta.',
            'jefe_produccion' => 'Verifica la carga con el caudalímetro y maneja el catálogo de la planta.',
            'inspector_calidad' => 'Corre el Lactoscan y agenda las visitas técnicas.',
            'personal_venta' => 'Atiende el mostrador y cierra la caja del día.',
            'personal_pago' => 'Prepara los sobres de la liquidación.',
            'pagador_campo' => 'Entrega el efectivo al productor en la ruta del viernes.',
            'admin' => 'Administra tarifas, tipos de cliente, usuarios y zonas.',
            'jefe_general' => 'Supervisa todo sin operar el día a día.',
            default => '—',
        };
    }
}
