<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('gastos_ruta', function (Blueprint $table) {
            if (!Schema::hasColumn('gastos_ruta', 'caja_movimiento_id')) {
                $table->unsignedBigInteger('caja_movimiento_id')->nullable()->after('concepto_documento_id');
                $table->index('caja_movimiento_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('gastos_ruta', function (Blueprint $table) {
            if (Schema::hasColumn('gastos_ruta', 'caja_movimiento_id')) {
                $table->dropIndex(['caja_movimiento_id']);
                $table->dropColumn('caja_movimiento_id');
            }
        });
    }
};
