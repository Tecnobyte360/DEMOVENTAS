<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function indexExists(string $indexName, string $table): bool
    {
        // Verifica índice en MySQL usando information_schema
        $res = DB::select(
            "SELECT 1 
             FROM information_schema.STATISTICS 
             WHERE table_schema = DATABASE() 
             AND table_name = ? 
             AND index_name = ? 
             LIMIT 1",
            [$table, $indexName]
        );
        return !empty($res);
    }

    public function up(): void
    {
        // === columnas ===
        Schema::table('movimientos', function (Blueprint $table) {
            if (!Schema::hasColumn('movimientos', 'debe')) {
                $table->decimal('debe', 18, 2)->default(0);
            }
            if (!Schema::hasColumn('movimientos', 'haber')) {
                $table->decimal('haber', 18, 2)->default(0);
            }
            if (!Schema::hasColumn('movimientos', 'detalle')) {
                $table->string('detalle', 255)->nullable();
            }
        });

        // === índices ===
        if (!$this->indexExists('movimientos_asiento_id_index', 'movimientos') &&
            Schema::hasColumn('movimientos', 'asiento_id')) {
            Schema::table('movimientos', function (Blueprint $table) {
                $table->index('asiento_id', 'movimientos_asiento_id_index');
            });
        }

        if (!$this->indexExists('movimientos_cuenta_id_index', 'movimientos') &&
            Schema::hasColumn('movimientos', 'cuenta_id')) {
            Schema::table('movimientos', function (Blueprint $table) {
                $table->index('cuenta_id', 'movimientos_cuenta_id_index');
            });
        }
    }

    public function down(): void
    {
        // elimina índices si existen
        if ($this->indexExists('movimientos_cuenta_id_index', 'movimientos')) {
            Schema::table('movimientos', function (Blueprint $table) {
                $table->dropIndex('movimientos_cuenta_id_index');
            });
        }

        if ($this->indexExists('movimientos_asiento_id_index', 'movimientos')) {
            Schema::table('movimientos', function (Blueprint $table) {
                $table->dropIndex('movimientos_asiento_id_index');
            });
        }

        // elimina columnas si existen
        Schema::table('movimientos', function (Blueprint $table) {
            if (Schema::hasColumn('movimientos', 'detalle')) {
                $table->dropColumn('detalle');
            }
            if (Schema::hasColumn('movimientos', 'haber')) {
                $table->dropColumn('haber');
            }
            if (Schema::hasColumn('movimientos', 'debe')) {
                $table->dropColumn('debe');
            }
        });
    }
};
