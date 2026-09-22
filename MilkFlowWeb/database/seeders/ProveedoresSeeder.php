<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * 52 proveedores (productores de leche) escritos en código: 13 por cada una
 * de las 4 zonas de Huata.
 *
 * Credencial estándar (web y móvil): DNI + contraseña `password`.
 * DNI = 400{zona}{correlativo de 4 dígitos} → Zona 1: 40010001..40010013,
 * Zona 2: 40020001..40020013, y así hasta la Zona 4.
 *
 * Cada proveedor queda además vinculado como `Customer` tipo `proveedor`
 * para que pueda comprar queso con cargo a su liquidación semanal.
 */
class ProveedoresSeeder extends Seeder
{
    /** 13 nombres por zona, en el orden en que se numeran los DNI. */
    public const POR_ZONA = [
        1 => [
            'Juan Perez Huanca',
            'Rosa Calsin Yana',
            'Mario Apaza Quispe',
            'Lucia Ccama Turpo',
            'Fermin Huanca Laqui',
            'Nelly Sucasaire Mamani',
            'Gregorio Ticona Pari',
            'Basilia Chino Coila',
            'Alberto Cutipa Larico',
            'Marcelina Hancco Puma',
            'Isidro Machaca Quenta',
            'Felicitas Ramos Cahui',
            'Eusebio Pacori Ala',
        ],
        2 => [
            'Marcos Nunure Ramos',
            'Elena Sancachi Ticona',
            'Ruben Joche Mamani',
            'Angelica Quilla Choque',
            'Teofilo Ayamamani Vilca',
            'Juana Cauna Supo',
            'Ignacio Yupanqui Arapa',
            'Dionisia Suaña Colque',
            'Wilfredo Catacora Nina',
            'Bernardina Ito Huarahuara',
            'Santiago Calcina Aroapaza',
            'Modesta Velasquez Luque',
            'Ciriaco Chambi Anco',
        ],
        3 => [
            'Nestor Yasin Apaza',
            'Silvia Moro Vilca',
            'Leoncio Llankako Puma',
            'Hilda Zapana Quispe',
            'Rufino Alanoca Ccapa',
            'Margarita Huisa Tito',
            'Sabino Paricahua Mendoza',
            'Victoria Cruz Yucra',
            'Adrian Ticahuanca Roque',
            'Paulina Maquera Chuquija',
            'Emiliano Condorena Layme',
            'Herminia Salas Achata',
            'Teodoro Quispe Charca',
        ],
        4 => [
            'Esteban Faon Pari',
            'Teresa Karata Coila',
            'Jacinto Jhochi Mamani',
            'Norma Aguilar Checalla',
            'Bonifacio Huaquisto Quispe',
            'Feliciana Llanos Tarqui',
            'Damaso Incacutipa Pilco',
            'Rosalia Vargas Choquehuanca',
            'Justino Umiña Sarmiento',
            'Gladys Chuquimamani Hilasaca',
            'Pascual Arocutipa Ancco',
            'Eulogia Callata Sosa',
            'Zacarias Huarsaya Quiñones',
        ],
    ];

    /**
     * El primer proveedor de la Zona 1 conserva el correo histórico
     * `productor@milkflow.com` porque es el productor de demostración.
     */
    public const CORREOS_FIJOS = [
        '40010001' => 'productor@milkflow.com',
    ];

    public function run(): void
    {
        $password = Hash::make(UsuariosRolesSeeder::PASSWORD);
        $telefono = 952000000;

        foreach (self::POR_ZONA as $numeroZona => $nombres) {
            $zona = Zone::where('code', 'ZONA_'.$numeroZona)->firstOrFail();

            foreach ($nombres as $indice => $nombre) {
                $correlativo = $indice + 1;
                $dni = sprintf('400%d%04d', $numeroZona, $correlativo);
                $telefono++;

                $usuario = User::updateOrCreate(['dni' => $dni], [
                    'name' => $nombre,
                    'email' => self::CORREOS_FIJOS[$dni] ?? $this->correo($nombre, $dni),
                    'password' => $password,
                    'role' => 'productor',
                    'zone_id' => $zona->id,
                    'phone' => (string) $telefono,
                    'is_active' => true,
                ]);

                $partes = explode(' ', $nombre);

                Customer::updateOrCreate(['linked_user_id' => $usuario->id], [
                    'first_name' => $partes[0],
                    'last_name' => implode(' ', array_slice($partes, 1)),
                    'dni_ruc' => $dni,
                    'phone' => (string) $telefono,
                    'type' => 'proveedor',
                    'is_wholesale_approved' => false,
                ]);
            }
        }
    }

    /** Correo derivado del nombre; el DNI lo desempata si dos proveedores coinciden. */
    private function correo(string $nombre, string $dni): string
    {
        $partes = explode(' ', $nombre);
        $base = Str::slug($partes[0].' '.($partes[1] ?? ''), '.');
        $correo = $base.'@huata.pe';

        if (User::where('email', $correo)->where('dni', '!=', $dni)->exists()) {
            $correo = $base.'.'.substr($dni, -4).'@huata.pe';
        }

        return $correo;
    }
}
