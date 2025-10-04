<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) Columna (sin depender de AFTER si no existe 'email')
        Schema::table('socio_negocios', function (Blueprint $table) {
            if (!Schema::hasColumn('socio_negocios', 'cuenta_cxc_id')) {
                // si existe 'email', la posicionamos; si no, la dejamos al final
                $col = $table->unsignedBigInteger('cuenta_cxc_id')->nullable();
                if (Schema::hasColumn('socio_negocios', 'email')) {
                    $col->after('email');
                }
            }
        });

        // 2) FK: crear solo si no hay ya una FK para esa columna
        $hasFk = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'socio_negocios')
            ->where('COLUMN_NAME', 'cuenta_cxc_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();

        if (!$hasFk && Schema::hasColumn('socio_negocios', 'cuenta_cxc_id')) {
            Schema::table('socio_negocios', function (Blueprint $table) {
                // nombre por defecto de Laravel: socio_negocios_cuenta_cxc_id_foreign
                $table->foreign('cuenta_cxc_id')
                      ->references('id')->on('plan_cuentas')
                      ->nullOnDelete();
            });
        }

        // 3) Índice: crear solo si no existe (Laravel lo nombrará socio_negocios_cuenta_cxc_id_index)
        $hasIndex = DB::table('information_schema.STATISTICS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'socio_negocios')
            ->where('INDEX_NAME', 'socio_negocios_cuenta_cxc_id_index')
            ->exists();

        if (!$hasIndex && Schema::hasColumn('socio_negocios', 'cuenta_cxc_id')) {
            Schema::table('socio_negocios', function (Blueprint $table) {
                $table->index('cuenta_cxc_id');
            });
        }
    }

    public function down(): void
    {
        // soltar FK si existe
        $fkName = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->select('CONSTRAINT_NAME')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', 'socio_negocios')
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->first();

        Schema::table('socio_negocios', function (Blueprint $table) use ($fkName) {
            try {
                if ($fkName && isset($fkName->CONSTRAINT_NAME)) {
                    $table->dropForeign($fkName->CONSTRAINT_NAME);
                } else {
                    // nombre por defecto
                    $table->dropForeign(['cuenta_cxc_id']);
                }
            } catch (\Throwable $e) {
                // ignorar si ya no existe
            }

            // soltar índice si existe
            try { $table->dropIndex('socio_negocios_cuenta_cxc_id_index'); } catch (\Throwable $e) {}

            // soltar columna si existe
            if (Schema::hasColumn('socio_negocios', 'cuenta_cxc_id')) {
                $table->dropColumn('cuenta_cxc_id');
            }
        });
    }
};
