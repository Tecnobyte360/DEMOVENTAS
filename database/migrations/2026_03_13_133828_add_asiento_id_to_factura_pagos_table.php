<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factura_pagos', function (Blueprint $table) {
            if (!Schema::hasColumn('factura_pagos', 'asiento_id')) {
                $table->unsignedBigInteger('asiento_id')->nullable();
            }
        });

        // Agrega la FK solo si la columna ya existe
        Schema::table('factura_pagos', function (Blueprint $table) {
            try {
                $table->foreign('asiento_id')
                    ->references('id')
                    ->on('asientos')
                    ->nullOnDelete();
            } catch (\Throwable $e) {
                // Si ya existe la foreign key, no rompe
            }
        });
    }

    public function down(): void
    {
        Schema::table('factura_pagos', function (Blueprint $table) {
            try {
                $table->dropForeign(['asiento_id']);
            } catch (\Throwable $e) {
            }

            if (Schema::hasColumn('factura_pagos', 'asiento_id')) {
                $table->dropColumn('asiento_id');
            }
        });
    }
};