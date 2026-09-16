<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\User;
use App\Models\Zone;
use App\Models\InventoryStock;
use App\Models\Customer;
use App\Models\Announcement;
use App\Models\CollectionRoute;
use App\Models\CollectionRecord;
use App\Models\ProducerSettlement;
use App\Models\ProducerDeduction;
use App\Models\LactoscanAnalysis;
use App\Models\TechnicalVisit;
use App\Models\ZoneChangeRequest;
use App\Models\OperationalExpense;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class MilkFlowHuataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Crear las 4 zonas de Huata
        $z1 = Zone::updateOrCreate(['code' => 'ZONA_1'], [
            'name' => 'Zona 1 (Zona Urbana Central)',
            'description' => 'Núcleo principal de la ciudad o capital de Huata.',
            'is_active' => true,
        ]);

        $z2 = Zone::updateOrCreate(['code' => 'ZONA_2'], [
            'name' => 'Zona 2 (Sectores del Sur)',
            'description' => 'Sectores al sur de la localidad principal: Ñuñure, Joche y Sancachi.',
            'is_active' => true,
        ]);

        $z3 = Zone::updateOrCreate(['code' => 'ZONA_3'], [
            'name' => 'Zona 3 (Sectores Agropecuarios Norte/Interior)',
            'description' => 'Sectores de Yasin, Juchuy Moro, Moro Viejo y Llankako.',
            'is_active' => true,
        ]);

        $z4 = Zone::updateOrCreate(['code' => 'ZONA_4'], [
            'name' => 'Zona 4 (Sectores Faón, Jhochi y Karata)',
            'description' => 'Comunidades y sectores circundantes de Faón, Jhochi y Karata.',
            'is_active' => true,
        ]);

        // 2. Usuarios del sistema según los 8 roles
        $password = Hash::make('password');

        // Rol 8: Jefe General / Dueño
        $jefeGeneral = User::updateOrCreate(['email' => 'jefe@milkflow.com'], [
            'name' => 'Jefe General Huata',
            'password' => $password,
            'role' => 'jefe_general',
            'phone' => '951000001',
            'dni' => '70000001',
            'is_active' => true,
        ]);

        // Rol 3: Administrador
        $admin = User::updateOrCreate(['email' => 'admin@milkflow.com'], [
            'name' => 'Admin Sistema Huata',
            'password' => $password,
            'role' => 'admin',
            'phone' => '951000002',
            'dni' => '70000002',
            'is_active' => true,
        ]);

        // Rol 4: Jefe de Producción
        $jefeProduccion = User::updateOrCreate(['email' => 'produccion@milkflow.com'], [
            'name' => 'Jefe Producción Planta',
            'password' => $password,
            'role' => 'jefe_produccion',
            'phone' => '951000003',
            'dni' => '70000003',
            'is_active' => true,
        ]);

        // Rol 5: Personal de Pago
        $personalPago = User::updateOrCreate(['email' => 'pagos@milkflow.com'], [
            'name' => 'Encargado Liquidación y Pagos',
            'password' => $password,
            'role' => 'personal_pago',
            'phone' => '951000004',
            'dni' => '70000004',
            'is_active' => true,
        ]);

        // Rol 6: Inspector de Calidad (Lactoscan)
        $inspectorCalidad = User::updateOrCreate(['email' => 'calidad@milkflow.com'], [
            'name' => 'Inspector Control Lactoscan',
            'password' => $password,
            'role' => 'inspector_calidad',
            'phone' => '951000005',
            'dni' => '70000005',
            'is_active' => true,
        ]);

        // Rol 7: Personal de Venta o Despacho
        $personalVenta = User::updateOrCreate(['email' => 'ventas@milkflow.com'], [
            'name' => 'Personal Venta y Despacho',
            'password' => $password,
            'role' => 'personal_venta',
            'phone' => '951000006',
            'dni' => '70000006',
            'is_active' => true,
        ]);

        // Rol 9: Pagador de Campo (Sueldos en efectivo / viernes en ruta con el acopiador)
        $pagadorCampo = User::updateOrCreate(['email' => 'pagador@milkflow.com'], [
            'name' => 'Pagador de Sueldos en Ruta',
            'password' => $password,
            'role' => 'pagador_campo',
            'phone' => '951000009',
            'dni' => '70000009',
            'is_active' => true,
        ]);

        // Rol 2: 5 Acopiadores (con descanso rotativo para 4 zonas)
        $acopiadoresData = [
            ['email' => 'acopiador1@milkflow.com', 'name' => 'Carlos Quispe Acopiador', 'phone' => '951111001', 'dni' => '71110001'],
            ['email' => 'acopiador2@milkflow.com', 'name' => 'Manuel Mamani Acopiador', 'phone' => '951111002', 'dni' => '71110002'],
            ['email' => 'acopiador3@milkflow.com', 'name' => 'Raul Condori Acopiador', 'phone' => '951111003', 'dni' => '71110003'],
            ['email' => 'acopiador4@milkflow.com', 'name' => 'Pedro Flores Acopiador', 'phone' => '951111004', 'dni' => '71110004'],
            ['email' => 'acopiador5@milkflow.com', 'name' => 'David Choque (Turno Descanso)', 'phone' => '951111005', 'dni' => '71110005'],
        ];

        foreach ($acopiadoresData as $ac) {
            User::updateOrCreate(['email' => $ac['email']], [
                'name' => $ac['name'],
                'password' => $password,
                'role' => 'acopiador',
                'phone' => $ac['phone'],
                'dni' => $ac['dni'],
                'is_active' => true,
            ]);
        }

        // Rol 1: Productores / Proveedores asignados por zona
        $productoresData = [
            // Zona 1
            ['name' => 'Juan Perez Huanca', 'email' => 'productor@milkflow.com', 'zone_id' => $z1->id, 'dni' => '40010001', 'phone' => '952000001'],
            ['name' => 'Rosa Calsin Yana', 'email' => 'rosa.calsin@huata.pe', 'zone_id' => $z1->id, 'dni' => '40010002', 'phone' => '952000002'],
            // Zona 2
            ['name' => 'Marcos Nunure Ramos', 'email' => 'marcos.nunure@huata.pe', 'zone_id' => $z2->id, 'dni' => '40020001', 'phone' => '952000003'],
            ['name' => 'Elena Sancachi Ticona', 'email' => 'elena.sancachi@huata.pe', 'zone_id' => $z2->id, 'dni' => '40020002', 'phone' => '952000004'],
            // Zona 3
            ['name' => 'Nestor Yasin Apaza', 'email' => 'nestor.yasin@huata.pe', 'zone_id' => $z3->id, 'dni' => '40030001', 'phone' => '952000005'],
            ['name' => 'Silvia Moro Vilca', 'email' => 'silvia.moro@huata.pe', 'zone_id' => $z3->id, 'dni' => '40030002', 'phone' => '952000006'],
            // Zona 4
            ['name' => 'Esteban Faon Pari', 'email' => 'esteban.faon@huata.pe', 'zone_id' => $z4->id, 'dni' => '40040001', 'phone' => '952000007'],
            ['name' => 'Teresa Karata Coila', 'email' => 'teresa.karata@huata.pe', 'zone_id' => $z4->id, 'dni' => '40040002', 'phone' => '952000008'],
        ];

        foreach ($productoresData as $prod) {
            $user = User::updateOrCreate(['email' => $prod['email']], [
                'name' => $prod['name'],
                'password' => $password,
                'role' => 'productor',
                'zone_id' => $prod['zone_id'],
                'dni' => $prod['dni'],
                'phone' => $prod['phone'],
                'is_active' => true,
            ]);

            // Vincular productor también como cliente con tarifa proveedor (S/ 18.00)
            $nameParts = explode(' ', $prod['name']);
            Customer::updateOrCreate(['linked_user_id' => $user->id], [
                'first_name' => $nameParts[0],
                'last_name' => implode(' ', array_slice($nameParts, 1)),
                'dni_ruc' => $prod['dni'],
                'phone' => $prod['phone'],
                'type' => 'proveedor',
                'is_wholesale_approved' => false,
            ]);
        }

        // 3. Clientes adicionales (Mayoristas y Locales)
        Customer::updateOrCreate(['dni_ruc' => '20601234567'], [
            'first_name' => 'Comercializadora',
            'last_name' => 'Lacteos del Altiplano EIRL',
            'phone' => '951888999',
            'type' => 'mayorista',
            'is_wholesale_approved' => true,
        ]);

        Customer::updateOrCreate(['dni_ruc' => '45678901'], [
            'first_name' => 'Lucia',
            'last_name' => 'Gutierrez Gomez',
            'phone' => '951333444',
            'type' => 'local',
            'is_wholesale_approved' => false,
        ]);

        // 4. Stock inicial en almacén
        InventoryStock::updateOrCreate(['item_code' => 'MILK_RAW_LITERS'], [
            'item_name' => 'Leche Fresca Verificada en Planta',
            'current_stock' => 500.00,
            'unit' => 'litros',
        ]);

        InventoryStock::updateOrCreate(['item_code' => 'CHEESE_MOLD_UNITS'], [
            'item_name' => 'Moldes de Queso Madurado Huata',
            'current_stock' => 45.00,
            'unit' => 'moldes',
        ]);

        // 5. Anuncio inicial del Administrador
        Announcement::updateOrCreate(['title' => 'Rotación Semanal de Zonas y Rutas 4:30 AM'], [
            'message' => 'Estimados acopiadores, verificar su asignación de zona antes de la salida a las 4:30 AM. Recuerden descargar en caudalímetro.',
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+7 days')),
            'target_role' => null, // para todos
            'created_by' => $admin->id,
            'is_active' => true,
        ]);

        // 6. Asegurar usuario productor@milkflow.com como alias de Juan Perez Huanca
        $juan = User::updateOrCreate(['email' => 'productor@milkflow.com'], [
            'name' => 'Juan Perez Huanca',
            'password' => $password,
            'role' => 'productor',
            'zone_id' => $z1->id,
            'dni' => '40010001',
            'phone' => '952000001',
            'is_active' => true,
        ]);

        // Actualizar también juan.perez@huata.pe si existiera
        User::where('email', 'juan.perez@huata.pe')->update(['name' => 'Juan Perez Huanca', 'zone_id' => $z1->id]);

        // Acopiador 1 para asignarle las rutas
        $acopiador1 = User::where('email', 'acopiador1@milkflow.com')->first();

        // 7. Generar ciclo anterior pagado (Semana 36: 01 Sep - 07 Sep)
        $pastRoute = CollectionRoute::firstOrCreate(
            ['date' => '2026-09-05', 'zone_id' => $z1->id],
            [
                'collector_id' => $acopiador1->id,
                'start_time' => '04:30:00',
                'status' => 'descargada_planta',
                'total_collected_liters' => 210.00
            ]
        );
        CollectionRecord::firstOrCreate(
            ['collection_route_id' => $pastRoute->id, 'producer_id' => $juan->id],
            ['liters' => 210.00, 'collected_at' => '05:15:00', 'notes' => 'Acopio ciclo semana 36']
        );

        // Liquidación pagada para cerrar el ciclo anterior
        ProducerSettlement::updateOrCreate(
            ['settlement_code' => 'LIQ-2026-W36-001'],
            [
                'producer_id' => $juan->id,
                'start_date' => '2026-09-01',
                'end_date' => '2026-09-07',
                'total_liters' => 210.00,
                'price_per_liter' => 1.40,
                'gross_total' => 294.00,
                'deductions_total' => 0.00,
                'net_total' => 294.00,
                'status' => 'pagado',
                'paid_at' => Carbon::parse('2026-09-08 10:30:00'),
                'payment_method' => 'efectivo',
                'paid_by' => $personalPago->id,
                'notes' => 'Liquidación semanal cerrada y pagada puntualmente en planta.',
            ]
        );

        // 8. Generar entregas para la semana activa actual (08 Sep al 13 Sep)
        $fechasSemanaActiva = [
            '2026-09-08' => 42.00,
            '2026-09-09' => 45.50,
            '2026-09-10' => 44.00,
            '2026-09-11' => 47.00,
            '2026-09-12' => 46.50,
            date('Y-m-d') => 48.50, // Entrega de hoy
        ];

        foreach ($fechasSemanaActiva as $f => $l) {
            $r = CollectionRoute::firstOrCreate(
                ['date' => $f, 'zone_id' => $z1->id],
                [
                    'collector_id' => $acopiador1->id,
                    'start_time' => '04:30:00',
                    'status' => 'descargada_planta',
                    'total_collected_liters' => $l
                ]
            );

            CollectionRecord::updateOrCreate(
                ['collection_route_id' => $r->id, 'producer_id' => $juan->id],
                [
                    'liters' => $l,
                    'collected_at' => '05:18:00',
                    'notes' => 'Leche fresca de primera calidad en porongo limpio'
                ]
            );
        }

        // 9. Descuento de prueba: Únicamente compra de queso (1 molde a tarifa de proveedor S/ 18.00)
        ProducerDeduction::updateOrCreate(
            ['producer_id' => $juan->id, 'concept' => 'Compra de 1 molde(s) de queso (descuento en leche)'],
            [
                'date' => Carbon::now()->subDays(2)->format('Y-m-d'),
                'amount' => 18.00,
                'status' => 'pendiente',
                'created_by' => $personalPago->id,
                'notes' => 'Queso fresco Paria adquirido con cargo a liquidación semanal de leche',
            ]
        );

        // 10. Análisis de Calidad Lactoscan de prueba
        $lacto = LactoscanAnalysis::updateOrCreate(
            ['producer_id' => $juan->id, 'analysis_date' => Carbon::now()->subDays(3)->format('Y-m-d')],
            [
                'inspector_id' => $inspectorCalidad->id,
                'fat_percentage' => 3.70,
                'snf_percentage' => 8.60,
                'density' => 1.029,
                'protein_percentage' => 3.25,
                'water_addition_percentage' => 0.00,
                'ph_or_acidity' => 16.5,
                'verdict' => 'conforme',
                'notes' => 'Muestra tomada en ruta 4:30 AM. Parámetros óptimos para quesería.',
            ]
        );

        // 11. Solicitud de cambio de zona de prueba
        ZoneChangeRequest::updateOrCreate(
            ['producer_id' => $juan->id, 'requested_zone_id' => $z2->id],
            [
                'current_zone_id' => $z1->id,
                'reason' => 'Rotación de pasturas al sector Joche por temporada seca.',
                'status' => 'pendiente',
            ]
        );

        // 12. Egresos Operativos y Pagos de Personal (Flujo de Caja)
        $acopiador1 = User::where('email', 'acopiador1@milkflow.com')->first();
        OperationalExpense::firstOrCreate(
            ['description' => 'Pago de honorarios semanales acopio ruta Zona 1'],
            [
                'category' => 'pago_personal',
                'amount' => 350.00,
                'expense_date' => Carbon::now()->subDays(2)->format('Y-m-d'),
                'user_id' => $acopiador1 ? $acopiador1->id : null,
                'beneficiary_name' => $acopiador1 ? $acopiador1->name : 'Carlos Quispe Acopiador',
                'payment_method' => 'efectivo',
                'receipt_number' => 'HON-00101',
                'registered_by' => $admin->id,
                'notes' => 'Pago semanal de ruta 4:30 AM Huata centro.',
            ]
        );

        OperationalExpense::firstOrCreate(
            ['description' => 'Combustible diésel para camión de acopio rutas 1 y 2'],
            [
                'category' => 'combustible_ruta',
                'amount' => 140.00,
                'expense_date' => Carbon::now()->subDay()->format('Y-m-d'),
                'beneficiary_name' => 'Grifo San Salvador Huata',
                'payment_method' => 'efectivo',
                'receipt_number' => 'FAC-0941',
                'registered_by' => $admin->id,
                'notes' => 'Abastecimiento de 35 galones de diésel.',
            ]
        );
    }
}
