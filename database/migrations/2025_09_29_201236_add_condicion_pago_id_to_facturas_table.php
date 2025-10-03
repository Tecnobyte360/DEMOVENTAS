<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            // Solo la crea si aún no existe
            if (! Schema::hasColumn('facturas', 'condicion_pago_id')) {
                $table->unsignedBigInteger('condicion_pago_id')->nullable()->after('tipo_pago');
            }

            // Foreign key hacia condicion_pagos (sin admin)
            $table->foreign('condicion_pago_id', 'fk_facturas_condicion_pago')
                ->references('id')
                ->on('condicion_pagos') // ✅ nombre correcto de la tabla
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            try {
                $table->dropForeign('fk_facturas_condicion_pago');
            } catch (\Throwable $e) {
                // por si ya no existe
            }

            if (Schema::hasColumn('facturas', 'condicion_pago_id')) {
                $table->dropColumn('condicion_pago_id');
            }
        });
    }
};
