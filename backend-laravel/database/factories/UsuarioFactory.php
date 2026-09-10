<?php

namespace Database\Factories;

use App\Enums\RolUsuario;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Factories\Factory;

class UsuarioFactory extends Factory
{
    protected $model = Usuario::class;

    public function definition(): array
    {
        return [
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName().' '.fake()->lastName(),
            'dni' => (string) fake()->unique()->numberBetween(70000000, 79999999),
            'email' => fake()->unique()->safeEmail(),
            'telefono' => '9'.fake()->numerify('########'),
            'password' => 'milkflow2026',
            'rol' => RolUsuario::ADMINISTRACION->value,
            'activo' => true,
        ];
    }

    public function rol(RolUsuario $rol): static
    {
        return $this->state(fn () => ['rol' => $rol->value]);
    }
}
