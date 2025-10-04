<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // (opcional) fuerza esta migración a usar MySQL
    // protected $connection = 'mysql';

    private function indexExists(string $indexName, string $table): bool
    {
        return DB::table('information_schema.STATISTICS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $indexName)
            ->exists();
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

        // === índices (idempotentes) ===
        // nombres por convención de Laravel si usas $table->index('asiento_id'):
        //   movimientos_asiento_id_index / movimientos_cuenta_id_index
        if (!$this->indexExists('movimientos_asiento_id_index', 'movimientos') && Schema::hasColumn('movimientos', 'asiento_id')) {
            Schema::table('movimientos', function (Blueprint $table) {
                $table->index('asiento_id');
            });
        }
        if (!$this->indexExists('movimientos_cuenta_id_index', 'movimientos') && Schema::hasColumn('movimientos', 'cuenta_id')) {
            Schema::table('movimientos', function (Blueprint $table) {
                $table->index('cuenta_id');
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
