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
       Schema::create('turnos_caja', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');            // quien abre/cierra
            $t->unsignedBigInteger('bodega_id')->nullable(); // caja/sede/bodega
            $t->timestamp('fecha_inicio');
            $t->timestamp('fecha_cierre')->nullable();

            $t->decimal('base_inicial', 14, 2)->default(0);

            // Totales calculados al cerrar
            $t->decimal('total_ventas', 14, 2)->default(0);
            $t->decimal('ventas_efectivo', 14, 2)->default(0);
            $t->decimal('ventas_debito', 14, 2)->default(0);
            $t->decimal('ventas_credito_tarjeta', 14, 2)->default(0);
            $t->decimal('ventas_transferencias', 14, 2)->default(0);
            $t->decimal('ventas_a_credito', 14, 2)->default(0); // facturas tipo crédito

            $t->decimal('devoluciones', 14, 2)->default(0);     // NC reembolso caja
            $t->decimal('ingresos_efectivo', 14, 2)->default(0); // ingresos manuales
            $t->decimal('retiros_efectivo', 14, 2)->default(0);  // retiros manuales (negativo)

            $t->string('estado')->default('abierto'); // abierto | cerrado
            $t->json('resumen')->nullable();          // snapshot detallado por método

            $t->timestamps();

            $t->index(['user_id','estado']);
            $t->foreign('bodega_id')->references('id')->on('bodegas')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('turnos_cajas');
    }
};
