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
        // Liquidaciones de pago semanales/periódicas al productor
        Schema::create('producer_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('settlement_code', 50)->unique(); // LIQ-2026-W37-001
            $table->foreignId('producer_id')->constrained('users')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_liters', 10, 2)->default(0);
            $table->decimal('price_per_liter', 6, 2)->default(1.40); // S/ 1.40 por litro en Huata
            $table->decimal('gross_total', 10, 2)->default(0);
            $table->decimal('deductions_total', 10, 2)->default(0);
            $table->decimal('net_total', 10, 2)->default(0);
            $table->string('status', 30)->default('pendiente'); // pendiente, pagado, procesando
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method', 30)->default('efectivo');
            $table->foreignId('paid_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Descuentos y deducciones aplicadas al productor (anticipos, insumos, etc.)
        Schema::create('producer_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('settlement_id')->nullable()->constrained('producer_settlements')->nullOnDelete();
            $table->date('date');
            $table->string('concept'); // Ej: Anticipo de pago en efectivo, Alimento concentrado balanceado, Sales minerales, Medicamento veterinario
            $table->decimal('amount', 10, 2);
            $table->string('status', 30)->default('pendiente'); // pendiente, descontado
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('producer_deductions');
        Schema::dropIfExists('producer_settlements');
    }
};
