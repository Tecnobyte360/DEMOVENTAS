<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('producto_costo_movimientos', function (Blueprint $table) {
            $table->id();

            // Fecha del evento de costo
            $table->dateTime('fecha')->index();

            // Alcance por bodega (tu costo promedio está en producto_bodega)
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('bodega_id')->constrained('bodegas')->cascadeOnDelete();

            // Origen / documento
            $table->foreignId('tipo_documento_id')->nullable()->constrained('tipo_documentos')->nullOnDelete();
            $table->string('doc_id', 100)->nullable();   // número/id del doc en tu sistema
            $table->string('ref', 255)->nullable();      // texto legible adicional

            // Movimiento que afectó el costo (para análisis)
            $table->decimal('cantidad', 18, 6)->default(0);       // cantidad de la ENTRADA/ajuste
            $table->decimal('valor_mov', 18, 6)->default(0);      // cantidad * costo_unit_mov
            $table->decimal('costo_unit_mov', 18, 6)->default(0); // costo unitario del movimiento

            // Método de costeo vigente
            $table->string('metodo_costeo', 30)->default('PROMEDIO');

            // Costo promedio (antes/después)
            $table->decimal('costo_prom_anterior', 18, 6)->nullable();
            $table->decimal('costo_prom_nuevo',    18, 6)->nullable();

            // Último costo (opcional) antes/después
            $table->decimal('ultimo_costo_anterior', 18, 6)->nullable();
            $table->decimal('ultimo_costo_nuevo',    18, 6)->nullable();

            // Clasificación del evento: ENTRADA, AJUSTE_POS, REVERSION, MANUAL, etc.
            $table->string('tipo_evento', 30)->nullable();

            // Quién lo causó (opcional)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['producto_id', 'bodega_id', 'fecha']);
            $table->index(['tipo_documento_id', 'doc_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_costo_movimientos');
    }
};
