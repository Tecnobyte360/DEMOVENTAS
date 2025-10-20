<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ✅ Verificamos si la columna NO existe antes de crearla
        if (!Schema::hasColumn('factura_pagos', 'turno_id')) {
            Schema::table('factura_pagos', function (Blueprint $table) {
                $table->unsignedBigInteger('turno_id')->nullable()->after('monto');

                // ⚙️ SQL Server no permite constrained() directamente a veces,
                // así que agregamos la foreign key manualmente:
                $table->foreign('turno_id')
                      ->references('id')
                      ->on('turnos_caja')
                      ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('factura_pagos', function (Blueprint $table) {
            // Eliminamos la FK antes de borrar la columna
            if (Schema::hasColumn('factura_pagos', 'turno_id')) {
                $table->dropForeign(['turno_id']);
                $table->dropColumn('turno_id');
            }
        });
    }
};
