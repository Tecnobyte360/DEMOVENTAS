<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            // Crea la columna (nullable)
            if (!Schema::hasColumn('facturas', 'cuenta_cobro_id')) {
                $table->unsignedBigInteger('cuenta_cobro_id')->nullable()->after('socio_negocio_id');
            }

            // Crea la FK sin "on delete restrict" (default = NO ACTION en SQL Server)
            $table->foreign('cuenta_cobro_id', 'facturas_cuenta_cobro_id_foreign')
                ->references('id')->on('plan_cuentas')
                ->cascadeOnUpdate(); 
        });
    }

    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            // El nombre de la FK debe coincidir con el definido arriba
            if (Schema::hasColumn('facturas', 'cuenta_cobro_id')) {
                $table->dropForeign('facturas_cuenta_cobro_id_foreign');
                $table->dropColumn('cuenta_cobro_id');
            }
        });
    }
};
