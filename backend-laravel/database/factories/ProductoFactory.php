<?php

namespace Database\Factories;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition(): array
    {
        return [
            'nombre' => 'Queso paria '.fake()->unique()->word(),
            'tipo' => 'queso_paria_fresco',
            'unidad_medida' => 'unidad',
            'rendimiento_min_por_100l' => 11.0,
            'rendimiento_max_por_100l' => 12.0,
            'controla_rendimiento' => true,
            'activo' => true,
        ];
    }
}
