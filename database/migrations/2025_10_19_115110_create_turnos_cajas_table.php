<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('turnos_caja', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');          // quien abre/cierra el turno
            $t->timestamp('fecha_inicio');
            $t->timestamp('fecha_cierre')->nullable();

            $t->decimal('base_inicial', 14, 2)->default(0);

            // Totales calculados al cerrar
            $t->decimal('total_ventas',           14, 2)->default(0);
            $t->decimal('ventas_efectivo',        14, 2)->default(0);
            $t->decimal('ventas_debito',          14, 2)->default(0);
            $t->decimal('ventas_credito_tarjeta', 14, 2)->default(0);
            $t->decimal('ventas_transferencias',  14, 2)->default(0);
            $t->decimal('ventas_a_credito',       14, 2)->default(0);

            $t->decimal('devoluciones',     14, 2)->default(0); // NC o reembolsos
            $t->decimal('ingresos_efectivo',14, 2)->default(0); // ingresos manuales
            $t->decimal('retiros_efectivo', 14, 2)->default(0); // retiros manuales

            $t->string('estado')->default('abierto'); // abierto | cerrado
            $t->json('resumen')->nullable();          // snapshot detallado por método

            $t->timestamps();

            $t->index(['user_id','estado']);
            // opcional: relación a tabla users
            // $t->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turnos_caja');
    }
};
