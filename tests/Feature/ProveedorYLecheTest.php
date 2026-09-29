<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\ProducerSettlement;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Supply;
use App\Models\User;
use App\Services\Produccion\ComprasService;
use Database\Seeders\MilkFlowHuataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El poblador de Huata que entrega leche también es un proveedor, pero su leche
 * no se compra por la pantalla de Compras.
 *
 * Entra por el caudalímetro de planta y se le paga por liquidación semanal. Si
 * además se pudiera comprar, entraría dos veces al stock y se pagaría dos veces.
 */
class ProveedorYLecheTest extends TestCase
{
    use RefreshDatabase;

    private User $jefePlanta;

    private ComprasService $compras;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MilkFlowHuataSeeder::class);

        $this->jefePlanta = User::where('role', 'jefe_produccion')->firstOrFail();
        $this->compras = app(ComprasService::class);
    }

    public function test_la_leche_queda_marcada_como_entrada_por_acopio(): void
    {
        $leche = Supply::where('item_code', 'MILK_RAW_LITERS')->firstOrFail();

        $this->assertSame(Supply::ENTRADA_ACOPIO, $leche->entry_mode);
        $this->assertFalse($leche->seCompra());

        // Lo demás sí se compra.
        $this->assertTrue(Supply::where('item_code', 'BOTELLA_1L')->firstOrFail()->seCompra());
    }

    public function test_no_se_puede_registrar_una_compra_de_leche(): void
    {
        $proveedor = $this->proveedorDeFruta();
        $leche = Supply::where('item_code', 'MILK_RAW_LITERS')->firstOrFail();
        $stockAntes = $leche->stock();

        $this->expectException(ReglaNegocioException::class);
        $this->expectExceptionMessage('entra por acopio y se paga en la liquidación semanal');

        try {
            $this->compras->registrar([
                'new_supplier_name' => $proveedor->name,
                'purchase_date' => now()->format('Y-m-d'),
                'items' => [
                    ['supply_id' => $leche->id, 'quantity' => 200, 'unit_cost' => 1.40],
                ],
            ], $this->jefePlanta);
        } finally {
            $this->assertEquals($stockAntes, $leche->fresh()->stock());
            $this->assertSame(0, Purchase::count());
        }
    }

    public function test_la_pantalla_de_compras_no_ofrece_la_leche_entre_los_insumos(): void
    {
        $this->actingAs($this->jefePlanta)
            ->get(route('produccion.compras.index'))
            ->assertOk()
            ->assertViewHas('insumos', fn ($insumos) => $insumos->contains('item_code', 'BOTELLA_1L')
                && ! $insumos->contains('item_code', 'MILK_RAW_LITERS'))
            ->assertSee('Esto no es el padrón de proveedores')
            ->assertSee('Los proveedores de la asociación son los productores de leche');
    }

    public function test_comprarle_fruta_a_un_poblador_no_toca_su_liquidacion_de_leche(): void
    {
        $productor = User::where('role', 'productor')->firstOrFail();

        // En la boleta figura su nombre, como el de cualquier otro proveedor.
        $fresa = Supply::where('item_code', 'FRESA_KG')->firstOrFail();
        $stockAntes = $fresa->stock();
        $liquidacionesAntes = ProducerSettlement::where('producer_id', $productor->id)->count();

        $compra = $this->compras->registrar([
            'new_supplier_name' => 'Fruta de don '.$productor->name,
            'purchase_date' => now()->format('Y-m-d'),
            'items' => [
                ['supply_id' => $fresa->id, 'quantity' => 8, 'unit_cost' => 5.00],
            ],
        ], $this->jefePlanta);

        $this->assertEquals(40.00, (float) $compra->total_amount);
        $this->assertEquals($stockAntes + 8, $fresa->fresh()->stock());

        // Su fruta se paga con la compra; su leche sigue cobrándose aparte.
        $this->assertSame(
            $liquidacionesAntes,
            ProducerSettlement::where('producer_id', $productor->id)->count()
        );
    }

    private function proveedorDeFruta(): Supplier
    {
        return $this->compras->crearProveedor([
            'name' => 'Acopiador de fruta del altiplano',
        ], $this->jefePlanta);
    }
}
