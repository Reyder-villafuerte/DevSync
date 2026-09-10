<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesUsuariosSeeder::class,
            SuperUsuarioSeeder::class,
            RutasZonasSeeder::class,
            ProductosPreciosSeeder::class,
            CatalogoInicialSeeder::class,
            MostradorWebSeeder::class,
        ]);
    }
}
