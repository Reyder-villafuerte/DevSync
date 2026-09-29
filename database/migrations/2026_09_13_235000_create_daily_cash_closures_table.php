<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_cash_closures', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->foreignId('closed_by')->constrained('users')->cascadeOnDelete();
            $table->decimal('total_cash', 10, 2)->default(0);
            $table->decimal('total_milk_discount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->integer('cheese_molds_quantity')->default(0);
            $table->integer('sales_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('closure_id')->nullable()->after('payment_method')->constrained('daily_cash_closures')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('closure_id');
        });

        Schema::dropIfExists('daily_cash_closures');
    }
};
