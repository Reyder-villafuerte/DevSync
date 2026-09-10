<?php

namespace Database\Seeders;

use App\Enums\RolUsuario;
use App\Models\Acopiador;
use App\Models\Ruta;
use App\Models\Usuario;
use App\Models\Zona;
use Illuminate\Database\Seeder;

// 2 rutas de camión, 4 zonas (2 por ruta). Se asigna el acopiador de ejemplo
// a la Ruta 01 (Norte). El `codigo` es el valor que usa ?ambito=ruta:01.
class RutasZonasSeeder extends Seeder
{
    public function run(): void
    {
        $norte = Ruta::updateOrCreate(['nombre' => 'Ruta Norte'], ['codigo' => '01', 'descripcion' => 'Salida madrugada por el altiplano norte']);
        $sur = Ruta::updateOrCreate(['nombre' => 'Ruta Sur'], ['codigo' => '02', 'descripcion' => 'Salida madrugada por la ribera del lago']);

        $zonas = [
            ['Zona Cabana', 'cabana', $norte],
            ['Zona Mañazo', 'manazo', $norte],
            ['Zona Acora', 'acora', $sur],
            ['Zona Chucuito', 'chucuito', $sur],
        ];
        foreach ($zonas as [$nombre, $codigo, $ruta]) {
            Zona::updateOrCreate(['nombre' => $nombre], ['codigo' => $codigo, 'ruta_id' => $ruta->id, 'activa' => true]);
        }

        $acopiadorNorte = Usuario::where('dni', '70000001')->first();
        if ($acopiadorNorte) {
            Acopiador::updateOrCreate(
                ['usuario_id' => $acopiadorNorte->id, 'vigente_hasta' => null],
                ['ruta_id' => $norte->id, 'vigente_desde' => now()->toDateString()],
            );
        }

        $acopiadorSur = Usuario::where('dni', '70000007')->first();
        if ($acopiadorSur) {
            Acopiador::updateOrCreate(
                ['usuario_id' => $acopiadorSur->id, 'vigente_hasta' => null],
                ['ruta_id' => $sur->id, 'vigente_desde' => now()->toDateString()],
            );
        }
    }
}
