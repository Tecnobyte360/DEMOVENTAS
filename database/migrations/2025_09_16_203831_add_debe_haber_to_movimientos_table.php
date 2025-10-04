<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function indexExists(string $indexName, string $table): bool
    {
        return DB::selectOne(
            "SELECT 1
             FROM information_schema.STATISTICS
             WHERE table_schema = DATABASE()
               AND table_name   = ?
               AND index_name   = ?
             LIMIT 1",
            [$table, $indexName]
        ) !== null;
    }

    public function up(): void
    {
        // columnas (solo si no existen)
        Schema::table('movimientos', function (Blueprint $table) {
            if (!Schema::hasColumn('movimientos', 'debe')) {
                $table->decimal('debe', 18, 2)->default(0);
            }
            if (!Schema::hasColumn('movimientos', 'haber')) {
                $table->decimal('haber', 18, 2)->default(0);
            }
            if (!Schema::hasColumn('movimientos', 'detalle')) {
                $table->text('detalle')->nullable(); // usa TEXT para no quedar corto
            }
        });

        // índices (solo si no existen y la columna existe)
        if (Schema::hasColumn('movimientos', 'asiento_id') &&
            !$this->indexExists('movimientos_asiento_id_index', 'movimientos')) {
            Schema::table('movimientos', fn (Blueprint $t) => $t->index('asiento_id', 'movimientos_asiento_id_index'));
        }

        if (Schema::hasColumn('movimientos', 'cuenta_id') &&
            !$this->indexExists('movimientos_cuenta_id_index', 'movimientos')) {
            Schema::table('movimientos', fn (Blueprint $t) => $t->index('cuenta_id', 'movimientos_cuenta_id_index'));
        }
    }

    public function down(): void
    {
        // quita índices si existen
        if ($this->indexExists('movimientos_cuenta_id_index', 'movimientos')) {
            Schema::table('movimientos', fn (Blueprint $t) => $t->dropIndex('movimientos_cuenta_id_index'));
        }
        if ($this->indexExists('movimientos_asiento_id_index', 'movimientos')) {
            Schema::table('movimientos', fn (Blueprint $t) => $t->dropIndex('movimientos_asiento_id_index'));
        }

        // quita columnas si existen
        Schema::table('movimientos', function (Blueprint $table) {
            if (Schema::hasColumn('movimientos', 'detalle')) $table->dropColumn('detalle');
            if (Schema::hasColumn('movimientos', 'haber'))   $table->dropColumn('haber');
            if (Schema::hasColumn('movimientos', 'debe'))    $table->dropColumn('debe');
        });
    }
};
