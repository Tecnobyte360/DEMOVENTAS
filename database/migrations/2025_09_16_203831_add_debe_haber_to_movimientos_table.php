<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function indexExists(string $indexName, string $table): bool
    {
        $res = DB::connection('sqlsrv')->select(
            'SELECT 1 FROM sys.indexes WHERE name = ? AND object_id = OBJECT_ID(?)',
            [$indexName, $table]
        );
        return !empty($res);
    }

    public function up(): void
    {
        // === columnas ===
        Schema::connection('sqlsrv')->table('movimientos', function (Blueprint $table) {
            if (!Schema::connection('sqlsrv')->hasColumn('movimientos', 'debe')) {
                $table->decimal('debe', 18, 2)->default(0);
            }
            if (!Schema::connection('sqlsrv')->hasColumn('movimientos', 'haber')) {
                $table->decimal('haber', 18, 2)->default(0);
            }
            if (!Schema::connection('sqlsrv')->hasColumn('movimientos', 'detalle')) {
                $table->string('detalle', 255)->nullable();
            }
        });

        // === índices (idempotentes) ===
        if (!$this->indexExists('movimientos_asiento_id_index', 'movimientos')) {
            DB::connection('sqlsrv')->statement(
                'CREATE INDEX movimientos_asiento_id_index ON movimientos (asiento_id)'
            );
        }
        if (!$this->indexExists('movimientos_cuenta_id_index', 'movimientos')) {
            DB::connection('sqlsrv')->statement(
                'CREATE INDEX movimientos_cuenta_id_index ON movimientos (cuenta_id)'
            );
        }
    }

    public function down(): void
    {
        // elimina índices si existen
        if ($this->indexExists('movimientos_cuenta_id_index', 'movimientos')) {
            DB::connection('sqlsrv')->statement('DROP INDEX movimientos_cuenta_id_index ON movimientos');
        }
        if ($this->indexExists('movimientos_asiento_id_index', 'movimientos')) {
            DB::connection('sqlsrv')->statement('DROP INDEX movimientos_asiento_id_index ON movimientos');
        }

        // elimina columnas si existen
        Schema::connection('sqlsrv')->table('movimientos', function (Blueprint $table) {
            if (Schema::connection('sqlsrv')->hasColumn('movimientos', 'detalle')) {
                $table->dropColumn('detalle');
            }
            if (Schema::connection('sqlsrv')->hasColumn('movimientos', 'haber')) {
                $table->dropColumn('haber');
            }
            if (Schema::connection('sqlsrv')->hasColumn('movimientos', 'debe')) {
                $table->dropColumn('debe');
            }
        });
    }
};
