<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * La compra deja de ser un ajuste de almacén sin memoria.
 *
 * Antes, meter 500 botellas al almacén era escribir «+500» y una nota suelta:
 * no quedaba a quién se le compró, a qué precio ni con qué documento. Ahora la
 * compra es un hecho con cabecera y detalle, y el costo unitario del insumo
 * pasa a salir de ahí en vez de ser un número tipeado a mano.
 */
return new class extends Migration
{
    public function up(): void
    {
        // El proveedor comercial (quien vende botellas o cuajo) no es el
        // productor de leche: ese vive en `users` con rol productor.
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('document', 20)->nullable(); // RUC o DNI
            $table->string('phone', 30)->nullable();
            $table->string('address')->nullable();
            $table->string('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('is_active');
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->date('purchase_date');
            $table->string('document_number', 50)->nullable(); // boleta o factura
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('notes')->nullable();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['purchase_date', 'supplier_id']);
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('supply_id')->constrained('supplies')->cascadeOnDelete();
            $table->decimal('quantity', 12, 4);
            $table->decimal('unit_cost', 10, 4);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();

            $table->index('supply_id');
        });

        Schema::table('supply_movements', function (Blueprint $table) {
            // A qué precio entró esa cantidad, para poder auditar el promedio.
            $table->decimal('unit_cost', 10, 4)->nullable()->after('quantity');
            // Si la compra se borra el movimiento sobrevive: el kardex no miente
            // borrando historia, la nota conserva el documento.
            $table->foreignId('purchase_id')->nullable()->after('production_order_id')
                ->constrained('purchases')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('supply_movements', function (Blueprint $table) {
            $table->dropForeign(['purchase_id']);
            $table->dropColumn(['unit_cost', 'purchase_id']);
        });

        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('suppliers');
    }
};
