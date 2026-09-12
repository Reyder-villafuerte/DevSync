<?php

namespace Database\Seeders;

use App\Enums\TipoCliente;
use App\Models\Cliente;
use App\Models\Correlativo;
use App\Models\PrecioCompraLeche;
use App\Models\Productor;
use App\Models\Usuario;
use App\Models\Zona;
use Illuminate\Database\Seeder;

class CatalogoInicialSeeder extends Seeder
{
    public function run(): void
    {
        // --- Tarifa base de compra de leche (RN-05) ---
        // Normal S/ 1.70; mínima degradada S/ 0.65 (rango 0.60-0.70).
        PrecioCompraLeche::updateOrCreate(
            ['vigente_desde' => now()->startOfYear()->toDateString()],
            ['precio_litro' => 1.7000, 'precio_litro_minimo' => 0.6500, 'vigente_hasta' => null],
        );

        // --- Correlativos de facturación ---
        Correlativo::updateOrCreate(['tipo_comprobante' => 'boleta', 'serie' => 'B001'], ['numero_maximo_asignado' => 0, 'tamano_bloque_default' => 50]);
        Correlativo::updateOrCreate(['tipo_comprobante' => 'factura', 'serie' => 'F001'], ['numero_maximo_asignado' => 0, 'tamano_bloque_default' => 50]);

        // --- Clientes de ejemplo, uno por tipo ---
        foreach ([
            ['Distribuidora Altiplano SAC', TipoCliente::MAYORISTA, '20123456789'],
            ['Bodega del Socio Julio Cutipa', TipoCliente::SOCIO, '70000003'],
            ['Venta mostrador', TipoCliente::PUBLICO, null],
        ] as [$nombre, $tipo, $doc]) {
            Cliente::updateOrCreate(['nombre' => $nombre], [
                'tipo_cliente' => $tipo->value,
                'documento_identidad' => $doc,
                'activo' => true,
            ]);
        }

        // --- Productores por zona (4 zonas, 2 por cada ruta) ---
        $zonaCabana = Zona::where('codigo', 'cabana')->first();
        $zonaManazo = Zona::where('codigo', 'manazo')->first();
        $zonaAcora = Zona::where('codigo', 'acora')->first();
        $zonaChucuito = Zona::where('codigo', 'chucuito')->first();

        $usuarioProductor = Usuario::where('dni', '70000003')->first();
        if ($zonaCabana && $usuarioProductor) {
            Productor::updateOrCreate(
                ['dni' => '70000003'],
                [
                    'usuario_id' => $usuarioProductor->id,
                    'codigo_padron' => 'P-0001',
                    'nombres' => 'Julio',
                    'apellidos' => 'Cutipa Huanca',
                    'zona_id' => $zonaCabana->id,
                    'telefono' => '951000003',
                    'estado' => 'activo',
                    'fecha_ingreso' => now()->subYears(2)->toDateString(),
                ],
            );
        }

        $otrosProductores = [
            ['71000002', 'P-0002', 'Marcos', 'Mamani Flores', $zonaManazo?->id],
            ['71000003', 'P-0003', 'Juana', 'Tito Canaza', $zonaAcora?->id],
            ['71000004', 'P-0004', 'Tomas', 'Huanca Coila', $zonaChucuito?->id],
            ['71000005', 'P-0005', 'Elena', 'Quispe Apaza', $zonaAcora?->id],
            ['71000006', 'P-0006', 'Fermin', 'Condori Larico', $zonaChucuito?->id],
        ];

        foreach ($otrosProductores as [$dni, $codigo, $nombres, $apellidos, $zonaId]) {
            if ($zonaId) {
                Productor::updateOrCreate(
                    ['dni' => $dni],
                    [
                        // Si existe un usuario con el mismo DNI, el socio entra a
                        // la app; si no, queda solo en el padrón (usuario_id null).
                        'usuario_id' => Usuario::where('dni', $dni)->value('id'),
                        'codigo_padron' => $codigo,
                        'nombres' => $nombres,
                        'apellidos' => $apellidos,
                        'zona_id' => $zonaId,
                        'estado' => 'activo',
                        'fecha_ingreso' => now()->subYear()->toDateString(),
                    ],
                );
            }
        }
    }
}
