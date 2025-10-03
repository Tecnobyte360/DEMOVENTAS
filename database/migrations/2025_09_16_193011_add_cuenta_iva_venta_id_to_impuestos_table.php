<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('impuestos', function (Blueprint $table) {
            $table->unsignedBigInteger('cuenta_iva_venta_id')
                ->nullable()
                ->after('aplica_sobre');

            $table->foreign('cuenta_iva_venta_id')
                ->references('id')->on('plan_cuentas');
            $table->index('cuenta_iva_venta_id');
        });
    }

    public function down(): void
    {
        Schema::table('impuestos', function (Blueprint $table) {
            $table->dropForeign(['cuenta_iva_venta_id']);
            $table->dropIndex(['cuenta_iva_venta_id']);
            $table->dropColumn('cuenta_iva_venta_id');
        });
    }
};
