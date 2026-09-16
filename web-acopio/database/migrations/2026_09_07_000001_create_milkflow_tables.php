<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->string('nombres')->default('');
            $t->string('apellidos')->default('');
            $t->string('documento', 30)->nullable()->unique();
            $t->string('telefono', 25)->nullable();
            $t->string('username')->nullable()->unique();
            $t->string('status')->default('PENDIENTE')->index();
            $t->timestamp('approved_at')->nullable();
            $t->foreignId('approved_by')->nullable()->constrained('users');
            $t->timestamp('rejected_at')->nullable();
            $t->foreignId('rejected_by')->nullable()->constrained('users');
            $t->text('rejection_reason')->nullable();
        });
        Schema::create('roles', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->string('slug')->unique();
            $t->timestamps();
        });
        Schema::create('permissions', function (Blueprint $t) {
            $t->id();
            $t->string('name')->unique();
            $t->timestamps();
        });
        Schema::create('role_user', function (Blueprint $t) {
            $t->foreignId('user_id')->constrained();
            $t->foreignId('role_id')->constrained();
            $t->primary(['user_id', 'role_id']);
        });
        Schema::create('permission_role', function (Blueprint $t) {
            $t->foreignId('permission_id')->constrained();
            $t->foreignId('role_id')->constrained();
            $t->primary(['permission_id', 'role_id']);
        });
        Schema::create('distritos', function (Blueprint $t) {
            $t->id();
            $t->string('nombre')->unique();
            $t->timestamps();
        });
        Schema::create('zonas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('distrito_id')->constrained('distritos');
            $t->string('nombre')->unique();
            $t->boolean('activo')->default(true);
            $t->timestamps();
        });
        Schema::create('sectores', function (Blueprint $t) {
            $t->id();
            $t->foreignId('zona_id')->constrained('zonas');
            $t->string('nombre');
            $t->boolean('activo')->default(true);
            $t->timestamps();
            $t->unique(['zona_id', 'nombre']);
        });
        Schema::create('productores', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->unique()->constrained();
            $t->string('nombre');
            $t->string('documento', 30)->unique();
            $t->string('telefono', 25);
            $t->foreignId('distrito_id')->constrained('distritos');
            $t->foreignId('zona_id')->constrained('zonas');
            $t->foreignId('sector_id')->constrained('sectores');
            $t->boolean('activo')->default(true)->index();
            $t->timestamp('expulsado_at')->nullable();
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('acopiadores', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained();
            $t->string('nombre');
            $t->string('telefono', 25)->nullable();
            $t->boolean('activo')->default(true);
            $t->timestamps();
        });
        Schema::create('rutas', function (Blueprint $t) {
            $t->id();
            $t->string('nombre');
            $t->string('codigo')->unique();
            $t->string('vehiculo');
            $t->foreignId('acopiador_id')->constrained('acopiadores');
            $t->time('hora_inicio')->default('04:30:00');
            $t->time('hora_fin')->default('12:00:00');
            $t->boolean('activo')->default(true);
            $t->timestamps();
        });
        Schema::create('ruta_sectores', function (Blueprint $t) {
            $t->foreignId('ruta_id')->constrained('rutas');
            $t->foreignId('sector_id')->constrained('sectores');
            $t->primary(['ruta_id', 'sector_id']);
        });
        Schema::create('cierres_ruta', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ruta_id')->constrained('rutas');
            $t->date('fecha');
            $t->decimal('litros', 12, 3);
            $t->foreignId('usuario_id')->constrained('users');
            $t->timestamps();
            $t->unique(['ruta_id', 'fecha']);
        });
        Schema::create('entregas', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('productor_id')->constrained('productores');
            $t->foreignId('acopiador_id')->nullable()->constrained('acopiadores');
            $t->foreignId('ruta_id')->nullable()->constrained('rutas');
            $t->foreignId('sector_id')->constrained('sectores');
            $t->foreignId('zona_id')->constrained('zonas');
            $t->string('tipo');
            $t->decimal('litros', 12, 3);
            $t->decimal('litros_utilizados', 12, 3)->default(0);
            $t->timestamp('fecha_hora')->index();
            $t->string('estado')->default('PENDIENTE')->index();
            $t->text('observacion')->nullable();
            $t->string('sync_status')->default('ENVIADO');
            $t->foreignId('created_by')->constrained('users');
            $t->foreignId('updated_by')->constrained('users');
            $t->timestamps();
        });
        Schema::create('pruebas_calidad', function (Blueprint $t) {
            $t->id();
            $t->foreignId('entrega_id')->unique()->constrained('entregas');
            $t->decimal('ph', 4, 2);
            $t->decimal('temperatura', 5, 2);
            $t->decimal('agua_agregada_porcentaje', 5, 2);
            $t->string('resultado')->index();
            $t->text('observacion')->nullable();
            $t->timestamp('fecha_hora');
            $t->foreignId('usuario_id')->constrained('users');
            $t->timestamps();
        });
        Schema::create('problemas_leche', function (Blueprint $t) {
            $t->id();
            $t->foreignId('entrega_id')->constrained('entregas');
            $t->string('tipo');
            $t->text('descripcion');
            $t->string('severidad');
            $t->timestamp('fecha');
            $t->foreignId('usuario_id')->constrained('users');
            $t->string('estado')->default('ABIERTO');
            $t->timestamps();
        });
        Schema::create('sanciones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('productor_id')->constrained('productores');
            $t->foreignId('prueba_calidad_id')->unique()->constrained('pruebas_calidad');
            $t->string('tipo');
            $t->date('periodo_inicio');
            $t->date('periodo_fin');
            $t->decimal('tarifa', 8, 2);
            $t->decimal('descuento', 10, 2)->default(0);
            $t->timestamps();
        });
        Schema::create('capacitaciones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('productor_id')->constrained('productores');
            $t->foreignId('entrega_id')->constrained('entregas');
            $t->string('tema');
            $t->date('fecha');
            $t->string('estado')->default('PENDIENTE');
            $t->text('observacion')->nullable();
            $t->foreignId('usuario_id')->constrained('users');
            $t->timestamps();
        });
        Schema::create('lotes_produccion', function (Blueprint $t) {
            $t->id();
            $t->string('codigo_lote')->unique();
            $t->string('tipo_producto');
            $t->decimal('litros_leche', 12, 3);
            $t->unsignedInteger('moldes_obtenidos');
            $t->decimal('rendimiento', 10, 3);
            $t->text('observaciones')->nullable();
            $t->timestamp('fecha')->index();
            $t->foreignId('usuario_id')->constrained('users');
            $t->string('estado')->default('TERMINADO');
            $t->timestamps();
        });
        Schema::create('lote_entregas', function (Blueprint $t) {
            $t->id();
            $t->foreignId('lote_id')->constrained('lotes_produccion');
            $t->foreignId('entrega_id')->constrained('entregas');
            $t->decimal('litros', 12, 3);
            $t->unique(['lote_id', 'entrega_id']);
        });
        Schema::create('stock_quesos', function (Blueprint $t) {
            $t->id();
            $t->string('tipo_producto')->unique();
            $t->unsignedInteger('cantidad')->default(0);
            $t->timestamps();
        });
        Schema::create('ventas', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->string('cliente');
            $t->string('tipo_cliente');
            $t->unsignedInteger('cantidad');
            $t->decimal('precio_unitario', 8, 2);
            $t->decimal('total', 12, 2);
            $t->timestamp('fecha')->index();
            $t->foreignId('usuario_id')->constrained('users');
            $t->string('estado')->default('CONFIRMADA');
            $t->timestamps();
        });
        Schema::create('venta_detalles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('venta_id')->constrained('ventas');
            $t->foreignId('stock_queso_id')->constrained('stock_quesos');
            $t->unsignedInteger('cantidad');
            $t->decimal('precio_unitario', 8, 2);
            $t->decimal('subtotal', 12, 2);
            $t->timestamps();
        });
        Schema::create('stock_movimientos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('stock_queso_id')->constrained('stock_quesos');
            $t->integer('cantidad');
            $t->string('motivo');
            $t->foreignId('lote_id')->nullable()->constrained('lotes_produccion');
            $t->foreignId('venta_id')->nullable()->constrained('ventas');
            $t->foreignId('usuario_id')->constrained('users');
            $t->timestamps();
        });
        Schema::create('liquidaciones', function (Blueprint $t) {
            $t->id();
            $t->foreignId('productor_id')->constrained('productores');
            $t->date('periodo_inicio');
            $t->date('periodo_fin');
            $t->decimal('litros', 12, 3);
            $t->decimal('tarifa', 8, 2);
            $t->decimal('subtotal', 12, 2);
            $t->decimal('descuentos', 12, 2)->default(0);
            $t->decimal('penalizaciones', 12, 2)->default(0);
            $t->decimal('total', 12, 2);
            $t->string('estado')->default('Calculada');
            $t->foreignId('usuario_id')->constrained('users');
            $t->timestamp('pagada_at')->nullable();
            $t->timestamps();
            $t->unique(['productor_id', 'periodo_inicio']);
        });
        Schema::create('liquidacion_detalles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('liquidacion_id')->constrained('liquidaciones');
            $t->foreignId('entrega_id')->unique()->constrained('entregas');
            $t->decimal('litros', 12, 3);
            $t->decimal('tarifa', 8, 2);
            $t->decimal('subtotal', 12, 2);
            $t->timestamps();
        });
        Schema::create('comunicados', function (Blueprint $t) {
            $t->id();
            $t->string('titulo');
            $t->text('mensaje');
            $t->date('fecha');
            $t->boolean('activo')->default(true);
            $t->foreignId('autor')->constrained('users');
            $t->timestamps();
        });
        Schema::create('temporadas', function (Blueprint $t) {
            $t->id();
            $t->string('nombre');
            $t->date('inicio');
            $t->date('fin');
            $t->timestamps();
        });
        Schema::create('rotaciones_productores', function (Blueprint $t) {
            $t->id();
            $t->foreignId('productor_id')->constrained('productores');
            $t->foreignId('zona_anterior_id')->constrained('zonas');
            $t->foreignId('sector_anterior_id')->constrained('sectores');
            $t->foreignId('zona_nueva_id')->constrained('zonas');
            $t->foreignId('sector_nuevo_id')->constrained('sectores');
            $t->foreignId('temporada_id')->nullable()->constrained('temporadas');
            $t->date('fecha_efectiva');
            $t->string('referencia');
            $t->string('estado')->default('PENDIENTE');
            $t->foreignId('solicitado_por')->constrained('users');
            $t->foreignId('revisado_por')->nullable()->constrained('users');
            $t->text('motivo')->nullable();
            $t->timestamp('aplicada_at')->nullable();
            $t->timestamps();
        });
        Schema::create('auditoria', function (Blueprint $t) {
            $t->id();
            $t->foreignId('usuario_id')->nullable()->constrained('users');
            $t->string('accion');
            $t->string('modelo');
            $t->unsignedBigInteger('registro');
            $t->json('datos_anteriores')->nullable();
            $t->json('datos_nuevos')->nullable();
            $t->timestamp('fecha')->useCurrent();
            $t->index(['modelo', 'registro']);
        });
        Schema::create('sync_records', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->foreignId('user_id')->constrained();
            $t->foreignId('entrega_id')->nullable()->constrained('entregas');
            $t->string('estado');
            $t->text('error')->nullable();
            $t->timestamps();
        });
        Schema::create('notifications', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('type');
            $t->morphs('notifiable');
            $t->text('data');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });
        Schema::create('configuraciones', function (Blueprint $t) {
            $t->string('clave')->primary();
            $t->string('valor');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['configuraciones', 'notifications', 'sync_records', 'auditoria', 'rotaciones_productores', 'temporadas', 'comunicados', 'liquidacion_detalles', 'liquidaciones', 'stock_movimientos', 'venta_detalles', 'ventas', 'stock_quesos', 'lote_entregas', 'lotes_produccion', 'capacitaciones', 'sanciones', 'problemas_leche', 'pruebas_calidad', 'entregas', 'cierres_ruta', 'ruta_sectores', 'rutas', 'acopiadores', 'productores', 'sectores', 'zonas', 'distritos', 'permission_role', 'role_user', 'permissions', 'roles'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', function (Blueprint $t) {
            $t->dropConstrainedForeignId('approved_by');
            $t->dropConstrainedForeignId('rejected_by');
            $t->dropColumn(['nombres', 'apellidos', 'documento', 'telefono', 'username', 'status', 'approved_at', 'rejected_at', 'rejection_reason']);
        });
    }
};
