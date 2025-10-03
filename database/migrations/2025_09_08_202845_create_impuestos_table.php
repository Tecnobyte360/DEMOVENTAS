<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('impuestos', function (Blueprint $table) {
            $table->id();

            // Básicos
            $table->string('codigo', 20)->unique();
            $table->string('nombre');

            // Relación con catálogo de tipos
            $table->unsignedBigInteger('tipo_id');

            // Dónde aplica
            $table->enum('aplica_sobre', ['VENTAS','COMPRAS','AMBOS'])->default('AMBOS');

            // Valor (uno u otro)
            $table->decimal('porcentaje', 9, 4)->nullable();
            $table->decimal('monto_fijo', 18, 2)->nullable();

            // Comportamiento
            $table->boolean('incluido_en_precio')->default(false);
            $table->enum('regla_redondeo', ['NORMAL','ARRIBA','ABAJO'])->default('NORMAL');

            // Vigencia y estado
            $table->date('vigente_desde')->nullable();
            $table->date('vigente_hasta')->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedTinyInteger('prioridad')->default(1);

            // Cuentas contables
            $table->unsignedBigInteger('cuenta_id');
            $table->unsignedBigInteger('contracuenta_id')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['tipo_id','aplica_sobre','activo']);
            $table->index('cuenta_id');
        });

        // FKs (fuera del closure, SQL Server friendly)
        Schema::table('impuestos', function (Blueprint $table) {
            $table->foreign('tipo_id', 'impuestos_tipo_fk')
                ->references('id')->on('impuesto_tipos')
                ->onDelete('no action')->onUpdate('cascade');

            $table->foreign('cuenta_id', 'impuestos_cuenta_fk')
                ->references('id')->on('plan_cuentas')
                ->onDelete('no action')->onUpdate('no action');

            $table->foreign('contracuenta_id', 'impuestos_contracuenta_fk')
                ->references('id')->on('plan_cuentas')
                ->onDelete('no action')->onUpdate('no action');
        });
    }

    public function down(): void
    {
        Schema::table('impuestos', function (Blueprint $table) {
            $table->dropForeign('impuestos_tipo_fk');
            $table->dropForeign('impuestos_cuenta_fk');
            $table->dropForeign('impuestos_contracuenta_fk');
        });

        Schema::dropIfExists('impuestos');
    }
};
