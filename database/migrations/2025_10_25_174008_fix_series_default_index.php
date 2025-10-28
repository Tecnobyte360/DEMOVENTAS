<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        // --- Elimina índices previos si existen (ambos nombres posibles) ---
        switch ($driver) {
            case 'sqlsrv': // SQL Server
                DB::statement("
                    IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_series_default_por_documento' AND object_id = OBJECT_ID('series'))
                        DROP INDEX IX_series_default_por_documento ON series;
                ");
                DB::statement("
                    IF EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_series_un_default_por_tipo' AND object_id = OBJECT_ID('series'))
                        DROP INDEX IX_series_un_default_por_tipo ON series;
                ");
                // Crear índice único filtrado
                DB::statement("
                    CREATE UNIQUE INDEX IX_series_un_default_por_tipo
                    ON series (tipo_documento_id)
                    WHERE es_default = 1;
                ");
                break;

            case 'pgsql': // PostgreSQL
                DB::statement("DROP INDEX IF EXISTS IX_series_default_por_documento;");
                DB::statement("DROP INDEX IF EXISTS IX_series_un_default_por_tipo;");
                DB::statement("
                    CREATE UNIQUE INDEX IF NOT EXISTS IX_series_un_default_por_tipo
                    ON series (tipo_documento_id)
                    WHERE es_default = true;
                ");
                break;

            case 'sqlite': // SQLite 3.8+ soporta índices parciales
                DB::statement("DROP INDEX IF EXISTS IX_series_default_por_documento;");
                DB::statement("DROP INDEX IF EXISTS IX_series_un_default_por_tipo;");
                DB::statement("
                    CREATE UNIQUE INDEX IF NOT EXISTS IX_series_un_default_por_tipo
                    ON series (tipo_documento_id)
                    WHERE es_default = 1;
                ");
                break;

            case 'mysql': // MySQL/MariaDB: usar columna generada + índice único
            default:
                // Borra índices si existen (ignora error si no están)
                try { DB::statement("DROP INDEX IX_series_default_por_documento ON series"); } catch (\Throwable $e) {}
                try { DB::statement("DROP INDEX IX_series_un_default_por_tipo ON series"); } catch (\Throwable $e) {}

                // Agrega columna generada si no existe (⚠️ sin 'NULL' antes de GENERATED)
                if (!Schema::hasColumn('series', 'default_key')) {
                    DB::statement("
                        ALTER TABLE series
                        ADD COLUMN default_key INT
                        GENERATED ALWAYS AS (
                            CASE WHEN es_default = 1 THEN tipo_documento_id ELSE NULL END
                        ) STORED
                    ");
                }

                // Crea índice único sobre la columna generada (sin IF NOT EXISTS; verificamos antes)
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
                // Quita el índice único
                try { DB::statement("DROP INDEX IX_series_un_default_por_tipo ON series"); } catch (\Throwable $e) {}
                // Quita la columna generada si existe
                if (Schema::hasColumn('series', 'default_key')) {
                    DB::statement("ALTER TABLE series DROP COLUMN default_key");
                }
                break;
        }
    }
};
