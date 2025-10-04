<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('socio_negocios', function (Blueprint $table) {
            // Solo crear la columna si no existe
            if (!Schema::hasColumn('socio_negocios', 'cuenta_cxc_id')) {
                $col = $table->unsignedBigInteger('cuenta_cxc_id')->nullable();

                // Solo usar AFTER si existe 'email'
                if (Schema::hasColumn('socio_negocios', 'email')) {
                    $col->after('email');
                }
            }
        });

        // Crear la foreign key y el índice de forma segura
        Schema::table('socio_negocios', function (Blueprint $table) {
            if (Schema::hasColumn('socio_negocios', 'cuenta_cxc_id')) {
                // Evita duplicar la foreign key
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = array_map('strtolower', array_keys($sm->listTableIndexes('socio_negocios')));

                if (!in_array('socio_negocios_cuenta_cxc_id_foreign', $indexes)) {
                    $table->foreign('cuenta_cxc_id')
                          ->references('id')
                          ->on('plan_cuentas')
                          ->nullOnDelete();
                }

                if (!in_array('cuenta_cxc_id', $indexes)) {
                    $table->index('cuenta_cxc_id');
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('socio_negocios', function (Blueprint $table) {
            if (Schema::hasColumn('socio_negocios', 'cuenta_cxc_id')) {
                try { $table->dropForeign(['cuenta_cxc_id']); } catch (\Throwable $e) {}
                try { $table->dropIndex(['cuenta_cxc_id']); } catch (\Throwable $e) {}
                $table->dropColumn('cuenta_cxc_id');
            }
        });
    }
};
