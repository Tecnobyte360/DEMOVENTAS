<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('gastos_ruta', function (Blueprint $table) {
            if (!Schema::hasColumn('gastos_ruta','serie_id')) {
                $table->unsignedBigInteger('serie_id')->nullable()->after('id');
                $table->index('serie_id');
            }
            if (!Schema::hasColumn('gastos_ruta','numero')) {
                $table->integer('numero')->nullable()->after('serie_id');
            }
            if (!Schema::hasColumn('gastos_ruta','prefijo')) {
                $table->string('prefijo', 10)->nullable()->after('numero');
            }
        });

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlsrv') {
            // ✅ SQL Server: UNIQUE filtrado para evitar choque por (NULL,NULL)
            if (!$this->sqlsrvIndexExists('gastos_ruta', 'uq_gastos_serie_numero')) {
                $schema = $this->sqlsrvDefaultSchema(); // ej: dbo o admin
                DB::statement("
                    CREATE UNIQUE INDEX uq_gastos_serie_numero
                    ON [{$schema}].[gastos_ruta] ([serie_id],[numero])
                    WHERE [serie_id] IS NOT NULL AND [numero] IS NOT NULL
                ");
            }
        } else {
            // ✅ MySQL / PostgreSQL / SQLite: UNIQUE normal (múltiples NULL no chocan)
            Schema::table('gastos_ruta', function (Blueprint $table) {
                $table->unique(['serie_id','numero'], 'uq_gastos_serie_numero');
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlsrv') {
            $schema = $this->sqlsrvDefaultSchema();
            if ($this->sqlsrvIndexExists('gastos_ruta', 'uq_gastos_serie_numero')) {
                DB::statement("DROP INDEX uq_gastos_serie_numero ON [{$schema}].[gastos_ruta]");
            }
        } else {
            Schema::table('gastos_ruta', function (Blueprint $table) {
                // En algunos motores, dropUnique por nombre funciona perfecto
                $table->dropUnique('uq_gastos_serie_numero');
            });
        }

        Schema::table('gastos_ruta', function (Blueprint $table) {
            if (Schema::hasColumn('gastos_ruta','prefijo')) $table->dropColumn('prefijo');
            if (Schema::hasColumn('gastos_ruta','numero')) $table->dropColumn('numero');
            if (Schema::hasColumn('gastos_ruta','serie_id')) $table->dropColumn('serie_id');
        });
    }

    /* ================= Helpers SQL Server ================= */

    private function sqlsrvIndexExists(string $table, string $index): bool
    {
        $db = DB::getDatabaseName();
        $row = DB::selectOne("
            SELECT 1 as ok
            FROM sys.indexes i
            INNER JOIN sys.objects o ON o.object_id = i.object_id
            INNER JOIN sys.schemas s ON s.schema_id = o.schema_id
            WHERE i.name = ? AND o.name = ? AND DB_NAME() = ?
        ", [$index, $table, $db]);

        return (bool) $row;
    }

    private function sqlsrvDefaultSchema(): string
    {
        // Si tu tabla está en 'admin', lo detecta. Si no, cae en dbo.
        $row = DB::selectOne("
            SELECT TOP 1 s.name AS schema_name
            FROM sys.objects o
            INNER JOIN sys.schemas s ON s.schema_id = o.schema_id
            WHERE o.name = ?
        ", ['gastos_ruta']);

        return $row?->schema_name ?: 'dbo';
    }
};
