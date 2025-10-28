<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        switch ($driver) {
            // ---------------------- SQL Server ----------------------
            case 'sqlsrv':
                DB::statement("
                    IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_series_default_por_documento' AND object_id = OBJECT_ID('series'))
                        DROP INDEX IX_series_default_por_documento ON series;
                ");
                DB::statement("
                    IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_series_un_default_por_tipo' AND object_id = OBJECT_ID('series'))
                        DROP INDEX IX_series_un_default_por_tipo ON series;
                ");
                DB::statement("
                    CREATE UNIQUE INDEX IX_series_un_default_por_tipo
                    ON series (tipo_documento_id)
                    WHERE es_default = 1;
                ");
                break;

            // ---------------------- PostgreSQL ----------------------
            case 'pgsql':
                DB::statement("DROP INDEX IF EXISTS IX_series_default_por_documento;");
                DB::statement("DROP INDEX IF EXISTS IX_series_un_default_por_tipo;");
                DB::statement("
                    CREATE UNIQUE INDEX IF NOT EXISTS IX_series_un_default_por_tipo
                    ON series (tipo_documento_id)
                    WHERE es_default = true;
                ");
                break;

            // ---------------------- SQLite ----------------------
            case 'sqlite':
                DB::statement("DROP INDEX IF EXISTS IX_series_default_por_documento;");
                DB::statement("DROP INDEX IF EXISTS IX_series_un_default_por_tipo;");
                DB::statement("
                    CREATE UNIQUE INDEX IF NOT EXISTS IX_series_un_default_por_tipo
                    ON series (tipo_documento_id)
                    WHERE es_default = 1;
                ");
                break;

            // ---------------------- MySQL / MariaDB ----------------------
            case 'mysql':
            default:
                // Limpia índices antiguos si existen
                try { DB::statement("DROP INDEX IX_series_default_por_documento ON series"); } catch (\Throwable $e) {}
                try { DB::statement("DROP INDEX IX_series_un_default_por_tipo ON series"); } catch (\Throwable $e) {}

                // Desactiva checks mientras se altera (evita error 1215 al reconstruir tabla)
                DB::statement("SET FOREIGN_KEY_CHECKS=0");
                try {
                    // Columna generada con MISMO tipo que la FK (foreignId = BIGINT UNSIGNED)
                    if (!Schema::hasColumn('series', 'default_key')) {
                        DB::statement("
                            ALTER TABLE series
                            ADD COLUMN default_key BIGINT UNSIGNED
                            GENERATED ALWAYS AS (
                                CASE WHEN es_default = 1 THEN tipo_documento_id ELSE NULL END
                            ) STORED
                        ");
                    }

                    // Crea índice único si no existe (MySQL no acepta IF NOT EXISTS aquí)
                    $exists = DB::table('information_schema.statistics')
                        ->where('table_schema', DB::getDatabaseName())
                        ->where('table_name', 'series')
                        ->where('index_name', 'IX_series_un_default_por_tipo')
                        ->exists();

                    if (!$exists) {
                        DB::statement("
                            CREATE UNIQUE INDEX IX_series_un_default_por_tipo
                            ON series (default_key)
                        ");
                    }
                } finally {
                    DB::statement("SET FOREIGN_KEY_CHECKS=1");
                }
                break;
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        switch ($driver) {
            case 'sqlsrv':
                DB::statement("
                    IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_series_un_default_por_tipo' AND object_id = OBJECT_ID('series'))
                        DROP INDEX IX_series_un_default_por_tipo ON series;
                ");
                break;

            case 'pgsql':
                DB::statement("DROP INDEX IF EXISTS IX_series_un_default_por_tipo;");
                break;

            case 'sqlite':
                DB::statement("DROP INDEX IF EXISTS IX_series_un_default_por_tipo;");
                break;

            case 'mysql':
            default:
                DB::statement("SET FOREIGN_KEY_CHECKS=0");
                try {
                    try { DB::statement("DROP INDEX IX_series_un_default_por_tipo ON series"); } catch (\Throwable $e) {}
                    if (Schema::hasColumn('series', 'default_key')) {
                        DB::statement("ALTER TABLE series DROP COLUMN default_key");
                    }
                } finally {
                    DB::statement("SET FOREIGN_KEY_CHECKS=1");
                }
                break;
        }
    }
};
