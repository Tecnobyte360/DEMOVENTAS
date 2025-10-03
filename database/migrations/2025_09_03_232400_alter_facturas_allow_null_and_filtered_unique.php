<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) Asegura que la tabla exista
        if (!Schema::hasTable('facturas')) {
            return;
        }

        // 2) (Opcional) Volver columnas a NULL por motor, evitando doctrine/dbal
        $driver = DB::getDriverName();

        if ($driver === 'sqlsrv') {
            // SQL Server
            // Detecta schema de la tabla (dbo, admin, etc.)
            $schema = $this->sqlsrvSchemaFor('facturas') ?? 'dbo';
            // Vuelve NULLable si las columnas existen
            if (Schema::hasColumn('facturas', 'serie_id')) {
                DB::statement("ALTER TABLE [$schema].[facturas] ALTER COLUMN [serie_id] BIGINT NULL");
            }
            if (Schema::hasColumn('facturas', 'numero')) {
                DB::statement("ALTER TABLE [$schema].[facturas] ALTER COLUMN [numero] BIGINT NULL");
            }
        } elseif ($driver === 'mysql') {
            // MySQL / MariaDB
            // OJO: ajusta tipos si no coinciden (BIGINT UNSIGNED)
            $mods = [];
            if (Schema::hasColumn('facturas', 'serie_id')) {
                $mods[] = "MODIFY `serie_id` BIGINT UNSIGNED NULL";
            }
            if (Schema::hasColumn('facturas', 'numero')) {
                $mods[] = "MODIFY `numero` BIGINT UNSIGNED NULL";
            }
            if ($mods) {
                DB::statement("ALTER TABLE `facturas` " . implode(", ", $mods));
            }
        } elseif ($driver === 'pgsql') {
            // PostgreSQL
            if (Schema::hasColumn('facturas', 'serie_id')) {
                DB::statement('ALTER TABLE "facturas" ALTER COLUMN "serie_id" DROP NOT NULL');
            }
            if (Schema::hasColumn('facturas', 'numero')) {
                DB::statement('ALTER TABLE "facturas" ALTER COLUMN "numero" DROP NOT NULL');
            }
        } else {
            // SQLite / otros: normalmente permiten ->change() menos; lo dejamos así.
            // Si necesitas garantizarlo en SQLite, hay que recrear la tabla (Laravel suele manejarlo en versiones recientes).
        }

        // 3) Crear el índice único de forma idempotente por motor
        $index = 'facturas_serie_id_numero_unique';

        if ($driver === 'sqlsrv') {
            $schema = $this->sqlsrvSchemaFor('facturas') ?? 'dbo';

            // Si existe, dropear
            if ($this->sqlsrvIndexExists($schema, 'facturas', $index)) {
                DB::statement("DROP INDEX [$index] ON [$schema].[facturas]");
            }

            // Índice único filtrado (permite múltiples NULL, aplica solo cuando ambos NO son NULL)
            DB::statement("
                CREATE UNIQUE INDEX [$index]
                ON [$schema].[facturas] ([serie_id], [numero])
                WHERE [serie_id] IS NOT NULL AND [numero] IS NOT NULL
            ");

        } elseif ($driver === 'pgsql') {
            // Drop si existe
            DB::statement("DROP INDEX IF EXISTS \"$index\"");
            // Índice único parcial (filtrado)
            DB::statement("
                CREATE UNIQUE INDEX \"$index\"
                ON \"facturas\" (\"serie_id\", \"numero\")
                WHERE (\"serie_id\" IS NOT NULL AND \"numero\" IS NOT NULL)
            ");

        } elseif ($driver === 'mysql') {
            // Verifica por information_schema
            if ($this->mysqlIndexExists('facturas', $index)) {
                DB::statement("ALTER TABLE `facturas` DROP INDEX `$index`");
            }
            // UNIQUE compuesto (MySQL permite múltiples NULLs)
            DB::statement("
                ALTER TABLE `facturas`
                ADD UNIQUE `$index` (`serie_id`, `numero`)
            ");

        } else {
            // SQLite (permite múltiples NULLs en UNIQUE, así que está ok)
            if ($this->sqliteIndexExists('facturas', $index)) {
                DB::statement("DROP INDEX \"$index\"");
            }
            DB::statement("
                CREATE UNIQUE INDEX \"$index\"
                ON \"facturas\" (\"serie_id\", \"numero\")
            ");
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();
        $index  = 'facturas_serie_id_numero_unique';

        try {
            if ($driver === 'sqlsrv') {
                $schema = $this->sqlsrvSchemaFor('facturas') ?? 'dbo';
                DB::statement("
                    IF EXISTS (
                        SELECT 1
                        FROM sys.indexes
                        WHERE name = N'$index'
                          AND object_id = OBJECT_ID(N'[$schema].[facturas]')
                    )
                    DROP INDEX [$index] ON [$schema].[facturas];
                ");
            } elseif ($driver === 'pgsql') {
                DB::statement("DROP INDEX IF EXISTS \"$index\"");
            } elseif ($driver === 'mysql') {
                DB::statement("ALTER TABLE `facturas` DROP INDEX `$index`");
            } else {
                DB::statement("DROP INDEX IF EXISTS \"$index\"");
            }
        } catch (\Throwable $e) {
            // Ignorar si no existe o el motor no soporta IF EXISTS
        }

        // Si quieres revertir las columnas a NOT NULL, hazlo aquí por motor (inverso del up()).
    }

    /* ===================== Helpers ===================== */

    private function sqlsrvSchemaFor(string $table): ?string
    {
        // Retorna el schema de la tabla si existe
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
        $rows = DB::select("PRAGMA index_list('$table')");
        foreach ($rows as $row) {
            if (isset($row->name) && $row->name === $index) {
                return true;
            }
        }
        return false;
    }
};
