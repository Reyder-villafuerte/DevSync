<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('category'); // pago_personal, combustible_ruta, insumos_planta, mantenimiento, otros
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->date('expense_date');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // si es pago a un personal registrado
            $table->string('beneficiary_name')->nullable();
            $table->string('payment_method')->default('efectivo'); // efectivo, transferencia, etc.
            $table->string('receipt_number')->nullable();
            $table->foreignId('registered_by')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_expenses');
    }
};
