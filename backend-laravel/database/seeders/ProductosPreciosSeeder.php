<?php

namespace Database\Seeders;

use App\Enums\TipoCliente;
use App\Models\PrecioVenta;
use App\Models\Producto;
use Illuminate\Database\Seeder;

// 3 productos con su lista de precios por tipo de cliente. El queso paria
// controla rendimiento (RN-08: 11-12 por 100 L).
class ProductosPreciosSeeder extends Seeder
{
    public function run(): void
    {
        $desde = now()->startOfYear()->toDateString();

        $catalogo = [
            [
                'nombre' => 'Queso paria fresco', 'tipo' => 'queso_paria_fresco', 'unidad' => 'unidad',
                'rend_min' => 11.0, 'rend_max' => 12.0, 'controla' => true,
                'precios' => [TipoCliente::MAYORISTA->value => 16.00, TipoCliente::SOCIO->value => 15.00, TipoCliente::PUBLICO->value => 18.00],
            ],
            [
                'nombre' => 'Leche pasteurizada 1 L', 'tipo' => 'pasteurizado', 'unidad' => 'litro',
                'rend_min' => null, 'rend_max' => null, 'controla' => false,
                'precios' => [TipoCliente::MAYORISTA->value => 3.20, TipoCliente::SOCIO->value => 3.00, TipoCliente::PUBLICO->value => 4.00],
            ],
            [
                'nombre' => 'Yogur 1 L', 'tipo' => 'yogur', 'unidad' => 'litro',
                'rend_min' => null, 'rend_max' => null, 'controla' => false,
                'precios' => [TipoCliente::MAYORISTA->value => 7.50, TipoCliente::SOCIO->value => 7.00, TipoCliente::PUBLICO->value => 9.00],
            ],
        ];

        foreach ($catalogo as $item) {
            $producto = Producto::updateOrCreate(
                ['nombre' => $item['nombre']],
                [
                    'tipo' => $item['tipo'],
                    'unidad_medida' => $item['unidad'],
                    'rendimiento_min_por_100l' => $item['rend_min'],
                    'rendimiento_max_por_100l' => $item['rend_max'],
                    'controla_rendimiento' => $item['controla'],
                    'activo' => true,
                ],
            );

            foreach ($item['precios'] as $tipoCliente => $precio) {
                PrecioVenta::updateOrCreate(
                    ['producto_id' => $producto->id, 'tipo_cliente' => $tipoCliente, 'vigente_desde' => $desde],
                    ['precio' => $precio, 'vigente_hasta' => null],
                );
            }
        }
    }
}
