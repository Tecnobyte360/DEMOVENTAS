<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration {
    public function up(): void
    {
        Schema::table('factura_detalles', function (Blueprint $table) {
            // 👇 Campo nuevo
            $table->unsignedBigInteger('cuenta_ingreso_id')
                  ->nullable()
                  ->after('producto_id');

            $table->foreign('cuenta_ingreso_id')
                  ->references('id')->on('plan_cuentas');
        });
    }

    public function down(): void
    {
        Schema::table('factura_detalles', function (Blueprint $table) {
            $table->dropForeign(['cuenta_ingreso_id']);
            $table->dropColumn('cuenta_ingreso_id');
        });
    }
};

