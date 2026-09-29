<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Zonas de Huata (1 a 4)
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // ZONA_1, ZONA_2, ZONA_3, ZONA_4
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Extender users o perfiles de rol
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('productor')->after('email');
            // productor, acopiador, admin, jefe_produccion, personal_pago, inspector_calidad, personal_venta, jefe_general
            $table->string('phone', 30)->nullable()->after('role');
            $table->string('dni', 20)->nullable()->index()->after('phone');
            $table->foreignId('zone_id')->nullable()->constrained('zones')->nullOnDelete()->after('dni');
            $table->boolean('is_active')->default(true)->after('zone_id');
        });

        // 3. Solicitud de cambio de zona para productores
        Schema::create('zone_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('current_zone_id')->constrained('zones')->cascadeOnDelete();
            $table->foreignId('requested_zone_id')->constrained('zones')->cascadeOnDelete();
            $table->string('status', 20)->default('pendiente'); // pendiente, aprobado, rechazado
            $table->text('reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        // 4. Asignación diaria de ruta a acopiadores (4:30 AM)
        Schema::create('collection_routes', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('zone_id')->constrained('zones')->cascadeOnDelete();
            $table->foreignId('collector_id')->constrained('users')->cascadeOnDelete();
            $table->time('start_time')->default('04:30:00');
            $table->string('status', 30)->default('asignada'); // asignada, en_ruta, descargada_planta, verificada
            $table->decimal('total_collected_liters', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['date', 'zone_id'], 'unique_date_zone_route');
        });

        // 5. Entregas individuales de cada productor en la ruta
        Schema::create('collection_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_route_id')->constrained('collection_routes')->cascadeOnDelete();
            $table->foreignId('producer_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('liters', 8, 2);
            $table->time('collected_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        // 6. Recepción y Verificación en Planta (Caudalímetro) por Jefe de Producción
        Schema::create('plant_receptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_route_id')->constrained('collection_routes')->cascadeOnDelete();
            $table->foreignId('verifier_id')->constrained('users')->cascadeOnDelete(); // Jefe de producción
            $table->decimal('collector_declared_liters', 10, 2); // lo que dijo el acopiador
            $table->decimal('flowmeter_liters', 10, 2); // lo que midió el caudalímetro / jefe
            $table->decimal('difference_liters', 10, 2)->default(0);
            $table->string('verification_status', 30)->default('verificado'); // verificado, incompleto, con_observacion
            $table->text('observation')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        // 7. Inventario / Stock Global (Leche y Queso)
        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->id();
            $table->string('item_code', 50)->unique(); // MILK_RAW_LITERS, CHEESE_MOLD_UNITS
            $table->string('item_name', 100);
            $table->decimal('current_stock', 12, 2)->default(0);
            $table->string('unit', 20); // litros, moldes
            $table->timestamps();
        });

        // 8. Producción de Queso (1 molde = 10 L de leche)
        Schema::create('cheese_productions', function (Blueprint $table) {
            $table->id();
            $table->date('production_date');
            $table->foreignId('supervisor_id')->constrained('users')->cascadeOnDelete(); // Jefe producción
            $table->integer('cheese_molds_produced'); // cantidad de moldes de queso
            $table->decimal('milk_liters_used', 10, 2); // moldes * 10
            $table->string('batch_number', 50)->nullable();
            $table->string('status', 30)->default('completado');
            $table->timestamps();
        });

        // 9. Clientes y Compradores de Queso
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name')->index();
            $table->string('dni_ruc', 20)->nullable()->index();
            $table->string('phone', 30)->nullable();
            $table->string('type', 30)->default('local'); // proveedor, mayorista, local
            $table->foreignId('linked_user_id')->nullable()->constrained('users')->nullOnDelete(); // si es proveedor
            $table->boolean('is_wholesale_approved')->default(false);
            $table->timestamps();
        });

        // 10. Ventas de Queso (Solo Efectivo, Recibo Formal y Tarifas)
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number', 50)->unique(); // REC-2026-0001
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete(); // Personal de venta
            $table->integer('cheese_molds_quantity');
            $table->decimal('unit_price', 8, 2); // 18.00 (proveedor), 19.00 (mayorista o >10), 20.00 (local)
            $table->decimal('total_amount', 10, 2);
            $table->string('payment_method', 20)->default('efectivo'); // solo efectivo
            $table->timestamp('sold_at');
            $table->timestamps();
        });

        // 11. Análisis de Calidad con Lactoscan
        Schema::create('lactoscan_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('inspector_id')->constrained('users')->cascadeOnDelete();
            $table->date('analysis_date');
            $table->decimal('fat_percentage', 5, 2)->nullable(); // Grasa %
            $table->decimal('snf_percentage', 5, 2)->nullable(); // Sólidos no grasos %
            $table->decimal('density', 6, 2)->nullable(); // Densidad
            $table->decimal('protein_percentage', 5, 2)->nullable(); // Proteína %
            $table->decimal('water_addition_percentage', 5, 2)->nullable(); // Agua adicionada %
            $table->decimal('temperature', 5, 2)->nullable(); // Temperatura °C
            $table->decimal('ph_or_acidity', 5, 2)->nullable(); // Acidez Dornic o pH
            $table->string('verdict', 30)->default('conforme'); // conforme, acidez_alta, adulterada, sospechosa
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 12. Citas para Visitas Técnicas (en caso de acidez u observaciones)
        Schema::create('technical_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lactoscan_analysis_id')->constrained('lactoscan_analyses')->cascadeOnDelete();
            $table->foreignId('producer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('inspector_id')->constrained('users')->cascadeOnDelete();
            $table->date('scheduled_date');
            $table->time('scheduled_time')->nullable();
            $table->string('status', 30)->default('programada'); // programada, realizada, cancelada
            $table->text('reason');
            $table->text('resolution_report')->nullable();
            $table->timestamps();
        });

        // 13. Anuncios y Avisos en Login con Rango de Fechas
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('target_role', 30)->nullable(); // null = todos, o rol específico
            $table->foreignId('target_user_id')->nullable()->constrained('users')->nullOnDelete(); // si es a un usuario puntual
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('technical_visits');
        Schema::dropIfExists('lactoscan_analyses');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('cheese_productions');
        Schema::dropIfExists('inventory_stocks');
        Schema::dropIfExists('plant_receptions');
        Schema::dropIfExists('collection_records');
        Schema::dropIfExists('collection_routes');
        Schema::dropIfExists('zone_change_requests');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['zone_id']);
            $table->dropColumn(['role', 'phone', 'dni', 'zone_id', 'is_active']);
        });

        Schema::dropIfExists('zones');
    }
};
