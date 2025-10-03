<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // --- 1) Asegurar columnas ---
        if (Schema::hasTable('series')) {
            Schema::table('series', function (Blueprint $table) {
                // Nota: "after('prefijo')" es ignorado por algunos drivers; no afecta.
                if (!Schema::hasColumn('series', 'documento')) {
                    $table->string('documento', 40)->default('factura')->after('prefijo');
                }
                if (!Schema::hasColumn('series', 'es_default')) {
                    $table->boolean('es_default')->default(false)->after('documento');
                }
            });
        } else {
            // Si la tabla no existe, la puedes crear aquí si quieres:
            // Schema::create('series', function (Blueprint $table) {
            //     $table->id();
            //     $table->string('prefijo', 10)->nullable();
            //     $table->string('documento', 40)->default('factura');
            //     $table->boolean('es_default')->default(false);
            //     $table->timestamps();
            // });
            return; // No continuamos si no hay tabla
        }

        // --- 2) Crear restricción de unicidad según el driver ---
        $driver = DB::getDriverName();
        $index  = 'IX_series_default_por_documento';

        if ($driver === 'sqlsrv') {
            // SQL Server: índice único filtrado (soporta WHERE)
            $schema = $this->sqlsrvSchemaFor('series') ?? 'dbo';

            // Eliminar si existe
            if ($this->sqlsrvIndexExists($schema, 'series', $index)) {
                DB::statement("DROP INDEX [$index] ON [$schema].[series]");
            }

            // Crear (uno default por documento)
            DB::statement("
                CREATE UNIQUE INDEX [$index]
                ON [$schema].[series] ([documento])
                WHERE [es_default] = 1
            ");

        } elseif ($driver === 'pgsql') {
            // PostgreSQL: índice único parcial (filtrado)
            // Borrar si existe
            if ($this->pgsqlIndexExists($index)) {
                DB::statement("DROP INDEX IF EXISTS \"$index\"");
            }

            // Crear (uno default por documento)
            DB::statement("
                CREATE UNIQUE INDEX \"$index\"
                ON \"series\" (\"documento\")
                WHERE (\"es_default\" IS TRUE)
            ");

        } elseif ($driver === 'mysql') {
            // MySQL: no hay índices filtrados → UNIQUE compuesto (documento, es_default)
            // Quitar índice previo si existe
            if ($this->mysqlIndexExists('series', $index)) {
                DB::statement("ALTER TABLE `series` DROP INDEX `$index`");
            }

            // Crear UNIQUE compuesto
            DB::statement("
                ALTER TABLE `series`
                ADD UNIQUE `$index` (`documento`, `es_default`)
            ");

        } else {
            // SQLite u otros: usar UNIQUE compuesto (documento, es_default)
            // En SQLite, los índices suelen llamarse automáticamente; comprobamos por pragma.
            if ($this->sqliteIndexExists('series', $index)) {
                DB::statement("DROP INDEX \"$index\"");
            }

            // Para SQLite: crear índice único compuesto (sin filtro)
            DB::statement("
                CREATE UNIQUE INDEX \"$index\"
                ON \"series\" (\"documento\", \"es_default\")
            ");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        $index  = 'IX_series_default_por_documento';

        try {
            if ($driver === 'sqlsrv') {
                $schema = $this->sqlsrvSchemaFor('series') ?? 'dbo';
                DB::statement("
                    IF EXISTS (
                        SELECT 1
                        FROM sys.indexes
                        WHERE name = N'$index'
                          AND object_id = OBJECT_ID(N'[$schema].[series]')
                    )
                    DROP INDEX [$index] ON [$schema].[series];
                ");
            } elseif ($driver === 'pgsql') {
                DB::statement("DROP INDEX IF EXISTS \"$index\"");
            } elseif ($driver === 'mysql') {
                DB::statement("ALTER TABLE `series` DROP INDEX `$index`");
            } else {
                DB::statement("DROP INDEX IF EXISTS \"$index\"");
            }
        } catch (\Throwable $e) {
            // Ignorar si no existe o el motor no soporta el IF EXISTS
        }

        // (Opcional) revertir columnas si quieres:
        // Schema::table('series', function (Blueprint $table) {
        //     if (Schema::hasColumn('series', 'es_default')) $table->dropColumn('es_default');
        //     if (Schema::hasColumn('series', 'documento')) $table->dropColumn('documento');
        // });
    }

    /* ===================== Helpers por driver ===================== */

    private function sqlsrvSchemaFor(string $table): ?string
    {
        // Retorna el schema de la tabla si existe; requiere SQL Server 2005+
        return DB::scalar("
            SELECT TOP 1 s.name
            FROM sys.objects o
            JOIN sys.schemas s ON o.schema_id = s.schema_id
            WHERE o.name = ?
        ", [$table]);
    }

    private function sqlsrvIndexExists(string $schema, string $table, string $index): bool
    {
        return (bool) DB::scalar("
            SELECT TOP 1 1
            FROM sys.indexes i
            JOIN sys.objects o  ON i.object_id = o.object_id
            JOIN sys.schemas s  ON o.schema_id = s.schema_id
            WHERE s.name = ? AND o.name = ? AND i.name = ?
        ", [$schema, $table, $index]);
    }

    private function pgsqlIndexExists(string $index): bool
    {
        // Busca por nombre de índice en el search_path actual
        return (bool) DB::scalar("
            SELECT 1
            FROM pg_indexes
            WHERE indexname = ?
            LIMIT 1
        ", [$index]);
    }

    private function mysqlIndexExists(string $table, string $index): bool
    {
        return (bool) DB::selectOne("
            SELECT 1
            FROM information_schema.statistics
            WHERE table_schema = DATABASE()
              AND table_name   = ?
              AND index_name   = ?
            LIMIT 1
        ", [$table, $index]);
    }

    private function sqliteIndexExists(string $table, string $index): bool
    {
        // PRAGMA index_list devuelve el listado de índices
        $rows = DB::select("PRAGMA index_list('$table')");
        foreach ($rows as $row) {
            // Campos típicos: seq, name, unique, origin, partial
            if (isset($row->name) && $row->name === $index) {
                return true;
            }
        }
        return false;
    }
};
